<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar constantes desde el archivo .env
loadEnvFile(__DIR__ . '/../.env');

// 1. Definir el JQL (ejemplo)
$jql = 'project = "ProcessMaker 4" AND issuetype in (Story, Task) AND status in (Done, Closed, Resolved) AND created >= "2024-01-01" AND created <= "2024-12-31" and "Story Points[Number]">1';

$inProgressStatus = ['In Progress', 'Scheduled', 'Research'],
$doneStatuses = ['Done', 'Closed', 'Resolved', 'Wont Fix']

// 2. Traer issues desde Jira
$issues = fetchIssuesFromJql($jql, 125);
// 3. Convertirlos en tickets históricos (con fechas IN PROGRESS / CLOSED)
$historicalTickets = extractTicketHistory($issues, $inProgressStatus, $doneStatuses);

// 4. Print historical tickets
foreach ($historicalTickets as $ticket) {
    echo $ticket['key'] . ' - ' . $ticket['estimate_days'] . ' - ' . $ticket['start_date'] . ' - ' . $ticket['end_date'] . ' - ' . $ticket['business_days'] . "\n";
}

/**
 * Example structure for upcoming project tickets
 * Only estimates are known.
 */
$projectTickets = [
    ['key' => 'NEW-1', 'estimate_days' => 2],
    /*['key' => 'NEW-2', 'estimate_days' => 3],
    ['key' => 'NEW-3', 'estimate_days' => 8],
    ['key' => 'NEW-4', 'estimate_days' => 5],*/
    // ... the rest of your project tickets
];


// -----------------
// Run Monte Carlo simulation
// -----------------

try {
    $multipliers = buildMultipliersFromHistory($historicalTickets);
    $result = simulateProjectDuration($projectTickets, $multipliers, 10000);

    echo "=== Project Duration Simulation (in days) ===\n";
    echo "Iterations:  " . $result['stats']['iterations'] . "\n";
    echo "Mean:        " . round($result['stats']['mean'], 1) . "\n";
    echo "Min:         " . round($result['stats']['min'], 1) . "\n";
    echo "P50 (Median):" . round($result['stats']['p50'], 1) . "\n";
    echo "P80:         " . round($result['stats']['p80'], 1) . "\n";
    echo "P90:         " . round($result['stats']['p90'], 1) . "\n";
    echo "P95:         " . round($result['stats']['p95'], 1) . "\n";
    echo "Max:         " . round($result['stats']['max'], 1) . "\n";

} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
