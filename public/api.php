<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Cargar constantes desde el archivo .env
    loadEnvFile(__DIR__ . '/../.env');

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new RuntimeException('Invalid JSON input');
    }

    // Extract parameters
    $jql = $input['jql'] ?? '';
    $inProgressStatus = $input['inProgressStatus'] ?? ['In Progress', 'Scheduled', 'Research'];
    $doneStatuses = $input['doneStatuses'] ?? ['Done', 'Closed', 'Resolved', 'Wont Fix'];
    $projectTickets = $input['projectTickets'] ?? [];
    $iterations = $input['iterations'] ?? 10000;
    $maxResults = $input['maxResults'] ?? 125;
    $maxMultiplier = $input['maxMultiplier'] ?? null;
    $enabledSegments = $input['enabledSegments'] ?? ['S', 'M', 'L'];

    // Validate inputs
    if (empty($jql)) {
        throw new RuntimeException('JQL query is required');
    }

    if (empty($projectTickets)) {
        throw new RuntimeException('At least one project ticket is required');
    }

    // Validate project tickets
    foreach ($projectTickets as $ticket) {
        if (empty($ticket['key']) || empty($ticket['estimate_days'])) {
            throw new RuntimeException('Each ticket must have a key and estimate_days');
        }
    }

    // Step 1: Fetch issues from Jira
    $issues = fetchIssuesFromJql($jql, $maxResults);

    if (empty($issues)) {
        throw new RuntimeException('No issues found with the given JQL query');
    }

    // Step 2: Extract ticket history
    $historicalTickets = extractTicketHistory($issues, $inProgressStatus, $doneStatuses, $maxMultiplier);

    if (empty($historicalTickets)) {
        throw new RuntimeException('No valid historical tickets found. Make sure your tickets have complete status transitions.');
    }

    // Step 3: Build multipliers from history
    $multipliers = buildMultipliersFromHistory($historicalTickets, $maxMultiplier);

    // Step 4: Run Monte Carlo simulation with segmented multipliers
    $result = simulateProjectDuration($projectTickets, $multipliers, $iterations, $enabledSegments);

    // Prepare response
    $response = [
        'success' => true,
        'stats' => $result['stats'],
        'samples' => $result['samples'],
        'historicalTickets' => $historicalTickets,
        'segments' => $result['segments'],
        'enabledSegments' => $result['enabledSegments'],
        'activeMultipliersCount' => $result['activeMultipliersCount'],
        'multipliers' => [
            'count' => count($multipliers),
            'min' => round(min($multipliers), 2),
            'max' => round(max($multipliers), 2),
            'avg' => round(array_sum($multipliers) / count($multipliers), 2)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

