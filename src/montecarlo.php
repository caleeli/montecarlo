<?php

/**
 * Calcula los multiplicadores a partir de tickets históricos,
 * usando SOLO días laborables entre start_date y end_date.
 *
 * multiplier = actual_business_days / estimate_days
 */
function buildMultipliersFromHistory(array $historicalTickets, $maxMultiplier = 5): array
{
    $multipliers = [];

    foreach ($historicalTickets as $ticket) {
        if (empty($ticket['estimate_days']) || $ticket['estimate_days'] <= 0) {
            // Saltamos estimaciones inválidas
            continue;
        }

        try {
            $start = new DateTime($ticket['start_date']);
            $end   = new DateTime($ticket['end_date']);
        } catch (Exception $e) {
            // Si una fecha es inválida, saltamos el ticket
            continue;
        }

        // Días HÁBILES reales dedicados al ticket
        $actualDays = businessDaysBetween($start, $end);

        $multiplier = $actualDays / $ticket['estimate_days'];
        if ($multiplier < $maxMultiplier) {
            $multipliers[] = $multiplier;
        }
    }

    if (empty($multipliers)) {
        throw new RuntimeException("No se pudieron calcular multiplicadores válidos a partir del histórico.");
    }

    return $multipliers;
}

/**
 * Run Monte Carlo simulation for project duration.
 *
 * @param array $projectTickets  List of tickets with estimate_days
 * @param array $multipliers     Historical multipliers
 * @param int   $iterations      Number of simulation runs
 *
 * @return array                 Simulation results and statistics
 */
function simulateProjectDuration(array $projectTickets, array $multipliers, int $iterations = 10000): array
{
    $numMultipliers = count($multipliers);
    $simulatedDurations = [];

    for ($i = 0; $i < $iterations; $i++) {
        $totalDays = 0.0;

        foreach ($projectTickets as $ticket) {
            $estimate = $ticket['estimate_days'];
            if ($estimate <= 0) {
                continue;
            }

            // Pick a random multiplier from history (bootstrap sampling)
            $randomIndex = mt_rand(0, $numMultipliers - 1);
            $multiplier = $multipliers[$randomIndex];

            $duration = $estimate * $multiplier;
            $totalDays += $duration;
        }

        $simulatedDurations[] = $totalDays;
    }

    sort($simulatedDurations);

    $stats = [
        'iterations' => $iterations,
        'mean'       => array_sum($simulatedDurations) / $iterations,
        'min'        => $simulatedDurations[0],
        'max'        => $simulatedDurations[$iterations - 1],
        'p50'        => percentile($simulatedDurations, 50),
        'p80'        => percentile($simulatedDurations, 80),
        'p90'        => percentile($simulatedDurations, 90),
        'p95'        => percentile($simulatedDurations, 95),
    ];

    return [
        'stats'      => $stats,
        'samples'    => $simulatedDurations, // full distribution if you want to plot it
    ];
}

/**
 * Helper to compute a percentile from a sorted array
 */
function percentile(array $sortedValues, float $percent): float
{
    $n = count($sortedValues);
    if ($n === 0) {
        return 0.0;
    }

    $index = ($percent / 100) * ($n - 1);
    $lower = floor($index);
    $upper = ceil($index);

    if ($lower === $upper) {
        return $sortedValues[$lower];
    }

    // Linear interpolation between closest ranks
    $weight = $index - $lower;
    return $sortedValues[$lower] * (1 - $weight) + $sortedValues[$upper] * $weight;
}

/**
 * Cuenta días laborables (lunes–viernes) entre dos fechas.
 * 
 * - Incluye el día de inicio.
 * - Excluye el día de fin (igual que ->diff()->days en el ejemplo anterior),
 *   es decir, cuenta cada día de trabajo en el intervalo [start, end).
 */
function businessDaysBetween(DateTime $start, DateTime $end): int
{
    // Si el end es antes que start, los intercambiamos por seguridad
    if ($end < $start) {
        $tmp   = $start;
        $start = $end;
        $end   = $tmp;
    }

    $interval = new DateInterval('P1D');
    $period   = new DatePeriod($start, $interval, $end); // end EXCLUIDO

    $businessDays = 0;

    foreach ($period as $date) {
        $dayOfWeek = (int)$date->format('N'); // 1 (lunes) ... 7 (domingo)
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $businessDays++;
        }
    }

    // Evitar 0 días para no generar multiplicadores absurdos (división entre 0)
    return max(1, $businessDays);
}
