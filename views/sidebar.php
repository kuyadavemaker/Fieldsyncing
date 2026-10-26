<?php // Workstation navigation rail. ?>
<aside class="sidebar" id="workstation-sidebar" aria-label="Workstation primary navigation">
    <div class="sidebar-top">
        <a class="brand" href="index.php?page=dashboard" aria-label="FieldSync overview">
            <span class="brand-mark">FS</span>
            <div class="brand-text">
                <strong>FieldSync</strong>
                <small>Operations</small>
            </div>
        </a>
        <button type="button" class="sidebar-toggle" aria-label="Collapse navigation" aria-expanded="true" title="Collapse navigation">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Workstation sections">
        <section class="nav-group" aria-labelledby="nav-group-workspace">
            <h2 class="nav-group-title" id="nav-group-workspace">Workspace</h2>
            <a class="nav-link <?= ($page === 'dashboard') ? 'active' : '' ?>" href="index.php?page=dashboard" title="Overview">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/></svg></span>
                <span>Overview</span>
            </a>
        </section>
        <section class="nav-group" aria-labelledby="nav-group-management">
            <h2 class="nav-group-title" id="nav-group-management">Management</h2>
            <a class="nav-link <?= ($page === 'jobs') ? 'active' : '' ?>" href="index.php?page=jobs" title="Dispatch">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h3m-3 3h7"/></svg></span>
                <span>Dispatch</span>
                <span class="badge-count"><?= (int)($stats['total_jobs'] ?? 0) ?></span>
            </a>
            <a class="nav-link <?= ($page === 'inventory') ? 'active' : '' ?>" href="index.php?page=inventory" title="Inventory">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16v14H4zM3 3h18v4H3zM8 11h8M8 15h5"/></svg></span>
                <span>Inventory</span>
                <?php if (!empty($stats['low_stock_parts'])): ?>
                    <span class="badge-count" style="color: var(--color-amber); background: rgba(245, 158, 11, 0.2);"><?= (int)$stats['low_stock_parts'] ?> low</span>
                <?php else: ?>
                    <span class="badge-count"><?= (int)($stats['total_parts'] ?? 0) ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-link <?= ($page === 'customers') ? 'active' : '' ?>" href="index.php?page=customers" title="Customers">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2.5 21a6.5 6.5 0 0 1 13 0M16 4.5a4 4 0 0 1 0 7.5m1 3a6 6 0 0 1 4.5 5.5"/></svg></span><span>Customers</span>
            </a>
            <a class="nav-link <?= ($page === 'equipment') ? 'active' : '' ?>" href="index.php?page=equipment" title="Assets">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11 12 4l9 7v9H3zM9 20v-6h6v6"/></svg></span><span>Assets</span>
            </a>
            <a class="nav-link <?= ($page === 'technicians') ? 'active' : '' ?>" href="index.php?page=technicians" title="Team">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m1-13h5m-2.5-2.5v5"/></svg></span><span>Team</span>
            </a>
            <a class="nav-link <?= ($page === 'invoices') ? 'active' : '' ?>" href="index.php?page=invoices" title="Invoices">
                <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6m-6 4h6"/></svg></span><span>Invoices</span>
            </a>
        </section>
    </nav>

    <section class="sidebar-footer" aria-labelledby="nav-group-account">
        <h2 class="nav-group-title" id="nav-group-account">Account</h2>
        <div class="sidebar-account-row">
            <div class="operator-avatar" title="<?= htmlspecialchars((string)($_SESSION['operator_role'] ?? 'Head Dispatcher'), ENT_QUOTES, 'UTF-8') ?>">HD</div>
            <div class="operator-meta">
                <strong><?= htmlspecialchars((string)($_SESSION['operator_name'] ?? 'Dispatcher'), ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars((string)($_SESSION['operator_company_name'] ?? 'Company'), ENT_QUOTES, 'UTF-8') ?></small>
            </div>
            <form method="POST" action="index.php" class="sidebar-logout">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" title="Sign out" aria-label="Sign out">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3m9-9h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg>
                    <span class="sidebar-logout-label">Sign out</span>
                </button>
            </form>
        </div>
    </section>
</aside>
