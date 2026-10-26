<?php
declare(strict_types=1);
$dashboardStatusCounts = ['Scheduled' => 0, 'In Progress' => 0, 'Completed' => 0, 'Cancelled' => 0];
$activeCustomerIds = [];
foreach ($allJobs as $dashboardJob) {
    $jobStatus = (string)($dashboardJob['status'] ?? '');
    if (array_key_exists($jobStatus, $dashboardStatusCounts)) {
        $dashboardStatusCounts[$jobStatus]++;
    }
    if (in_array($jobStatus, ['Scheduled', 'In Progress'], true)) {
        $activeCustomerIds[(int)$dashboardJob['customer_id']] = true;
    }
}
$lowStockRows = array_values(array_filter(
    $inventoryRows,
    static fn(array $part): bool => (int)$part['quantity_in_stock'] <= 5
));
$jobTotal = count($allJobs);
$customerTotal = (int)($stats['total_customers'] ?? 0);
$partTotal = (int)($stats['total_parts'] ?? 0);
$openJobs = $dashboardStatusCounts['Scheduled'] + $dashboardStatusCounts['In Progress'];
$openJobRate = $jobTotal > 0 ? (int)round(($openJobs / $jobTotal) * 100) : 0;
$activeCustomerRate = $customerTotal > 0 ? (int)round((count($activeCustomerIds) / $customerTotal) * 100) : 0;
$lowStockRate = $partTotal > 0 ? (int)round((count($lowStockRows) / $partTotal) * 100) : 0;
?>
<section class="stats-grid dashboard-stats" aria-label="Overview">
    <a class="stat-card stat-cyan dashboard-stat" href="index.php?page=jobs">
        <div class="stat-label"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h3"/></svg>Open jobs</div>
        <div class="stat-value"><?= $openJobs ?><small> / <?= $jobTotal ?></small></div>
        <div class="stat-progress" role="img" aria-label="<?= $openJobRate ?> percent open">
            <span style="width: <?= $openJobRate ?>%"></span>
        </div>
    </a>
    <a class="stat-card stat-violet dashboard-stat" href="index.php?page=customers">
        <div class="stat-label"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2.5 21a6.5 6.5 0 0 1 13 0M16 4.5a4 4 0 0 1 0 7.5m1 3a6 6 0 0 1 4.5 5.5"/></svg>Active customers</div>
        <div class="stat-value"><?= count($activeCustomerIds) ?><small> / <?= $customerTotal ?></small></div>
        <div class="stat-progress" role="img" aria-label="<?= $activeCustomerRate ?> percent active">
            <span style="width: <?= $activeCustomerRate ?>%"></span>
        </div>
    </a>
    <a class="stat-card stat-amber dashboard-stat" href="index.php?page=inventory">
        <div class="stat-label"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v14H4zM3 3h18v4H3zM8 11h8M8 15h5"/></svg>Low stock</div>
        <div class="stat-value"><?= count($lowStockRows) ?><small> / <?= $partTotal ?></small></div>
        <div class="stat-progress" role="img" aria-label="<?= $lowStockRate ?> percent of parts low stock">
            <span style="width: <?= $lowStockRate ?>%"></span>
        </div>
    </a>
</section>

<section class="card-matrix dashboard-dispatch">
    <div class="card-matrix-header">
        <h2>Recent jobs</h2>
        <a href="index.php?page=jobs" class="btn btn-secondary btn-sm">All jobs</a>
    </div>
    <?php if (empty($allJobs)): ?>
        <div class="dashboard-empty">No jobs yet.</div>
    <?php else: ?>
        <div class="dashboard-job-list">
            <?php foreach (array_slice($allJobs, 0, 5) as $dashboardJob):
                $statusClass = match (strtolower((string)$dashboardJob['status'])) {
                    'in progress' => 'badge-inprogress',
                    'completed' => 'badge-completed',
                    'cancelled' => 'badge-cancelled',
                    default => 'badge-scheduled',
                };
            ?>
                <a class="dashboard-job" href="index.php?page=jobs">
                    <span class="dashboard-job-id">#<?= (int)$dashboardJob['job_id'] ?></span>
                    <span class="dashboard-job-customer">
                        <strong><?= htmlspecialchars($dashboardJob['customer_name'] ?? 'Unknown customer', ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars($dashboardJob['equipment_type'] ?? 'Service', ENT_QUOTES, 'UTF-8') ?></small>
                    </span>
                    <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($dashboardJob['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="dashboard-job-date"><?= htmlspecialchars(date('M d · H:i', strtotime($dashboardJob['scheduled_datetime'])), ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
