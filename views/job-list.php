<?php
// Dispatch board view.
declare(strict_types=1);

$scheduledCount = count(array_filter($jobs, static fn($j) => strcasecmp($j['status'] ?? '', 'Scheduled') === 0));
$inProgressCount = count(array_filter($jobs, static fn($j) => strcasecmp($j['status'] ?? '', 'In Progress') === 0));
$completedCount = count(array_filter($jobs, static fn($j) => strcasecmp($j['status'] ?? '', 'Completed') === 0));
?>

<!-- Sector KPI Metrics Grid -->
<section class="stats-grid" aria-label="Dispatch telemetry">
    <div class="stat-card stat-cyan">
        <div class="stat-label">Total Work Orders <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="17" rx="2"/><path d="M8 2v4m8-4v4M4 10h16"/></svg></div>
        <div class="stat-value"><?= count($jobs) ?></div>
        <div class="stat-subtext">Active operational tickets</div>
    </div>
    <div class="stat-card stat-cyan">
        <div class="stat-label">Scheduled Dispatches <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <div class="stat-value"><?= $scheduledCount ?></div>
        <div class="stat-subtext">Pending field rollouts</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-label">In Progress <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7z"/></svg></div>
        <div class="stat-value"><?= $inProgressCount ?></div>
        <div class="stat-subtext">Currently underway on-site</div>
    </div>
    <div class="stat-card stat-emerald">
        <div class="stat-label">Completed <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></div>
        <div class="stat-value"><?= $completedCount ?></div>
        <div class="stat-subtext">Finished service tickets</div>
    </div>
</section>

<!-- Search and Operations Filter Bar -->
<div class="toolbar-container">
    <form method="GET" action="index.php" class="search-box">
        <input type="hidden" name="page" value="jobs">
        <span class="search-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></span>
        <input
            type="text"
            name="search"
            class="search-input"
            placeholder="Search tickets by customer, equipment, technician, part, or status..."
            value="<?= htmlspecialchars($searchQuery ?? '', ENT_QUOTES, 'UTF-8') ?>"
        >
    </form>
    <?php if (!empty($searchQuery)): ?>
        <a href="index.php?page=jobs" class="btn btn-secondary btn-sm">Clear Filter</a>
    <?php endif; ?>
</div>

<!-- Master Work Order Matrix -->
<section class="card-matrix">
    <div class="card-matrix-header">
        <h2>
            Operational Work Orders
            <span class="count-badge"><?= count($jobs) ?> Active</span>
        </h2>
        <span style="font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);">
            ACID State: Consistent
        </span>
    </div>

    <?php if (empty($jobs)): ?>
        <div style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
            <svg class="empty-state-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4H5v17h14V4h-3M9 3h6v4H9zM8 11h8m-8 4h8m-8 4h5"/></svg>
            <strong style="color: var(--text-secondary); display: block; font-size: 15px;">No work order tickets found</strong>
            <p style="font-size: 13px; margin-top: 6px;">Create a new ticket or adjust search parameters to inspect dispatch records.</p>
        </div>
    <?php else: ?>
        <div class="ticket-list">
            <?php foreach ($jobs as $row):
                $status = $row['status'] ?? 'Scheduled';
                $badgeClass = match (strtolower($status)) {
                    'in progress' => 'badge-inprogress',
                    'completed' => 'badge-completed',
                    'cancelled' => 'badge-cancelled',
                    default => 'badge-scheduled',
                };
            ?>
                <article class="ticket-item">
                    <div class="ticket-main">
                        <div class="ticket-title-row">
                            <span class="ticket-id">#<?= (int)$row['job_id'] ?></span>
                            <span class="ticket-customer"><?= htmlspecialchars($row['customer_name'] ?? 'Unknown Customer', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($row['invoice_id'])): ?>
                                <span class="badge badge-<?= strtolower($row['invoice_status'] ?? 'unpaid') ?>">
                                    Inv #<?= (int)$row['invoice_id'] ?>: $<?= number_format((float)$row['invoice_total'], 2) ?> (<?= htmlspecialchars($row['invoice_status'], ENT_QUOTES, 'UTF-8') ?>)
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="ticket-equipment">
                            <svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true" style="color:var(--color-cyan)"><path d="M3 11 12 4l9 7v9H3zM9 20v-6h6v6"/></svg>
                            <strong>Asset:</strong>
                            <?= htmlspecialchars(($row['equipment_type'] ?? 'Asset') . (!empty($row['model_number']) ? ' (' . $row['model_number'] . ')' : ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($row['serial_number'])): ?>
                                <span style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">[S/N: <?= htmlspecialchars($row['serial_number'], ENT_QUOTES, 'UTF-8') ?>]</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($row['description'])): ?>
                            <div class="ticket-desc">
                                <?= nl2br(htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        <?php endif; ?>

                        <div class="ticket-meta-grid">
                            <span>
                                <strong>Scheduled:</strong>
                                <?= date('M d, Y · h:i A', strtotime($row['scheduled_datetime'])) ?>
                            </span>
                            <span>
                                <strong>Phone:</strong>
                                <?= htmlspecialchars($row['customer_phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span>
                                <strong>Technicians:</strong>
                                <span style="color: <?= !empty($row['assigned_technicians']) ? 'var(--color-cyan)' : 'var(--text-muted)' ?>">
                                    <?= htmlspecialchars($row['assigned_technicians'] ?? 'None assigned', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                            <span>
                                <strong>Parts:</strong>
                                <span style="color: <?= !empty($row['parts_used']) ? 'var(--color-emerald)' : 'var(--text-muted)' ?>">
                                    <?= htmlspecialchars($row['parts_used'] ?? 'None logged', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                            <span>
                                <strong>Van / Truck:</strong>
                                <span style="color: <?= !empty($row['service_vehicles']) ? 'var(--color-cyan)' : 'var(--text-muted)' ?>">
                                    <?= htmlspecialchars($row['service_vehicles'] ?? 'Not logged', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Row Action Matrix -->
                    <div class="action-matrix">
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            onclick="openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)"
                            title="Edit Ticket Details"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/></svg> Edit
                        </button>
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            onclick="openAssignTechnicianModal(<?= (int)$row['job_id'] ?>)"
                            title="Assign Field Technician"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m1-13h5m-2.5-2.5v5"/></svg> Assign Tech
                        </button>
                        <button
                            type="button"
                            class="btn btn-secondary btn-sm"
                            onclick="openLogPartModal(<?= (int)$row['job_id'] ?>)"
                            title="Log Inventory Part Usage"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 7 9-4 9 4v10l-9 4-9-4V7Z"/><path d="m3 7 9 4 9-4M12 11v10"/></svg> Log Part
                        </button>
                        <?php if (empty($row['invoice_id'])): ?>
                            <button
                                type="button"
                                class="btn btn-cyan-subtle btn-sm"
                                onclick="openGenerateInvoiceModal(<?= (int)$row['job_id'] ?>)"
                                title="Generate Invoice"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15.5 8.5c-.7-.8-1.8-1.2-3.2-1.2-1.8 0-3 .8-3 2s1.2 1.8 3 2.2 3 1 3 2.2-1.2 2-3 2c-1.4 0-2.5-.4-3.2-1.2M12 5v14"/></svg> Bill
                            </button>
                        <?php endif; ?>
                        <form method="POST" action="index.php?page=jobs" onsubmit="return confirm('Confirm deletion of Work Order #<?= (int)$row['job_id'] ?>? All associated assignments and part allocations will be cleanly removed.');" style="display:inline;">
                            <input type="hidden" name="action" value="delete_job">
                            <input type="hidden" name="job_id" value="<?= (int)$row['job_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete Ticket" aria-label="Delete ticket"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 0V4h4v3m4 0-1 14H7L6 7m3 4v6m6-6v6"/></svg></button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>