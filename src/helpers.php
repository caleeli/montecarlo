<?php

/**
 * Carga variables de entorno desde un archivo .env y las define como constantes.
 *
 * @param string $envPath Ruta al archivo .env
 * @return void
 */
function loadEnvFile(string $envPath): void
{
    if (!file_exists($envPath)) {
        throw new RuntimeException("El archivo .env no existe: $envPath");
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar comentarios y líneas vacías
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }

        // Parsear líneas del formato KEY=VALUE
        if (str_contains($line, '=')) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remover comillas si existen
            if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Definir constante si no existe
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

/**
 * Ejecuta una petición HTTP a la API de Jira (REST v3).
 */
function jiraRequest(string $method, string $path, array $queryParams = [], array $body = null): array
{
    $url = JIRA_BASE_URL . $path;

    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }

    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Autenticación básica con email + API token
    curl_setopt($ch, CURLOPT_USERPWD, JIRA_USER_EMAIL . ':' . JIRA_API_TOKEN);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);

    if ($response === false) {
        throw new RuntimeException('Error al llamar Jira: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException("Respuesta no OK de Jira (HTTP $httpCode): $response");
    }

    $data = json_decode($response, true);

    if ($data === null) {
        throw new RuntimeException("No se pudo decodificar JSON de Jira: $response");
    }

    return $data;
}

/**
 * Obtiene issues desde Jira usando JQL, usando el NUEVO endpoint:
 *   POST /rest/api/3/search/jql
 *
 * Usa paginación por nextPageToken (no startAt).
 *
 * Devuelve el array crudo de issues tal como viene de Jira.
 */
function fetchIssuesFromJql(string $jql, $maxResults): array
{
    $allIssues     = [];
    $nextPageToken = null;     // para la paginación cursor-based
    $isLast        = false;

    do {
        // Body según la doc oficial del endpoint /search/jql
        // https://developer.atlassian.com/cloud/jira/platform/rest/v3/api-group-issue-search/ 
        $body = [
            'jql'        => $jql,
            'maxResults' => $maxResults,
            // solo pedimos campos mínimos
            'fields'     => ['key', 'timeoriginalestimate','customfield_10006'],
            // expand como string (no array) para evitar quejas del schema
            'expand'     => 'changelog',
        ];

        // Para la siguiente página, Jira devuelve un nextPageToken
        if ($nextPageToken !== null) {
            $body['nextPageToken'] = $nextPageToken;
        }

        $response = jiraRequest('POST', '/rest/api/3/search/jql', [], $body);

        // La nueva respuesta usa isLast + nextPageToken + issues
        $issues        = $response['issues']        ?? [];
        $isLast        = $response['isLast']        ?? true;
        $nextPageToken = $response['nextPageToken'] ?? null;

        $allIssues = array_merge($allIssues, $issues);

        // break loop
        break;
    } while (!$isLast && $nextPageToken);

    return $allIssues;
}

/**
 * Convierte issues crudos de Jira en tickets históricos para tu simulación.
 *
 * Cada ticket resultante tendrá:
 *  - key
 *  - estimate_days
 *  - start_date (cuando pasa a IN PROGRESS)
 *  - end_date   (cuando pasa a CLOSED / DONE)
 *
 * @param array       $issues             Issues devueltos por fetchIssuesFromJql
 * @param array      $inProgressStatus   Nombre del estado "en progreso" (ej: "In Progress")
 * @param string[]    $doneStatuses       Lista de estados considerados "cerrados"
 * @return array
 */
function extractTicketHistory(
    array $issues,
    array $inProgressStatus = ['In Progress', 'Scheduled', 'Research'],
    array $doneStatuses = ['Done', 'Closed', 'Resolved', 'Wont Fix'],
    int $maxMultiplier = 5
): array {
    $tickets = [];

    foreach ($issues as $issue) {
        $key  = $issue['key'] ?? null;
        $fields = $issue['fields'] ?? [];

        // Estimación original en medios dias laborables (1 story point = 1/2 day)
        $origEstimateSP = $fields['customfield_10006'] ?? null;
        if ($key == 'FOUR-20488') {
            var_dump($origEstimateSP, $fields);die;
        }
        if (empty($origEstimateSP) || $origEstimateSP <= 1) {
            // Saltamos issues sin estimación original
            continue;
        }

        // Convertimos a días
        $estimateDays = $origEstimateSP / 2;

        $changelog = $issue['changelog'] ?? null;
        if (!$changelog || empty($changelog['histories'])) {
            // Sin historial de cambios, difícil saber IN PROGRESS / CLOSED
            continue;
        }

        $startDate = null; // fecha primer cambio a IN PROGRESS
        $endDate   = null; // fecha primer cambio a estado cerrado

        // Reverse the changelog array
        $changelog['histories'] = array_reverse($changelog['histories']);
        if ($key == 'FOUR-20488') {
            var_dump($changelog);die;
        }
        $doneStatus = null;
        foreach ($changelog['histories'] as $history) {
            $created = $history['created'] ?? null;
            if (empty($created) || empty($history['items'])) {
                continue;
            }

            foreach ($history['items'] as $item) {
                if (($item['field'] ?? '') !== 'status') {
                    continue;
                }

                $toStatus   = $item['toString']   ?? '';
                //$fromStatus = $item['fromString'] ?? '';

                // Detectar IN PROGRESS
                if (($startDate === null || $created < $startDate) && in_array_case_insensitive($toStatus, $inProgressStatus)) {
                    $startDate = $created; // ejemplo: "2025-02-10T09:33:24.123+0000"
                }

                // Detectar cerrados
                if (($endDate === null || $created < $endDate) && in_array_case_insensitive($toStatus, $doneStatuses)) {
                    $endDate = $created;
                    $doneStatus = $toStatus;
                }
            }
        }

        // Si nunca pasó a IN PROGRESS o nunca se cerró, puedes decidir si lo saltas o no.
        if ($startDate === null || $endDate === null) {
            // Para el cálculo de multiplicadores suele ser mejor usar solo issues "completos"
            continue;
        }

        // Calculate business days between start date and end date
        $businessDays = businessDaysBetween(new DateTime($startDate), new DateTime($endDate));
        $multiplier = $businessDays / $estimateDays;
        if ($multiplier > $maxMultiplier) {
            continue;
        }

        $tickets[] = [
            'key'           => $key,
            'estimate_days' => $estimateDays,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'business_days' => $businessDays,
            'multiplier'    => $multiplier,
            'done_status'   => $doneStatus,
        ];
    }

    return $tickets;
}

function in_array_case_insensitive(string $value, array $array): bool
{
    return in_array(strtolower($value), array_map('strtolower', $array), true);
}
