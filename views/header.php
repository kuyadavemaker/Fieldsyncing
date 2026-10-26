<?php
// Shared workstation header.
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FieldSync Workstation | <?= htmlspecialchars($pageTitle ?? 'Dispatch Matrix', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/workstation.css?v=<?= (int)filemtime(__DIR__ . '/../assets/css/workstation.css') ?>">
</head>
<body>
    <div class="app-shell">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <button class="sidebar-backdrop" type="button" aria-label="Close navigation" tabindex="-1"></button>

        <main class="main-content">
            <!-- Page Title Header -->
            <header class="page-header">
                <div>
                    <button class="mobile-sidebar-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="workstation-sidebar">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1><?= htmlspecialchars($pageTitle ?? 'Dispatch Matrix', ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
                <div class="header-actions">
                    <?php if ($page === 'jobs'): ?>
                        <button class="btn btn-primary" onclick="openModal('createModal')">+ New Ticket</button>
                    <?php elseif ($page === 'customers'): ?>
                        <button class="btn btn-primary" onclick="openModal('customerModal')">+ Add Customer</button>
                    <?php elseif ($page === 'equipment'): ?>
                        <button class="btn btn-primary" onclick="openModal('equipmentModal')">+ Register Asset</button>
                    <?php elseif ($page === 'technicians'): ?>
                        <button class="btn btn-primary" onclick="openModal('technicianModal')">+ Add Technician</button>
                    <?php elseif ($page === 'inventory'): ?>
                        <button class="btn btn-primary" onclick="openModal('partModal')">+ Restock Part</button>
                    <?php elseif ($page === 'invoices'): ?>
                        <button class="btn btn-primary" onclick="openModal('invoiceModal')">+ Issue Invoice</button>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (!empty($_SESSION['recovery_code_once'])): ?>
                <section class="recovery-code-notice" role="alert">
                    <div>
                        <strong>Save your one-time recovery code now</strong>
                        <p>This code is shown only once. Store it somewhere safe; it is required to reset your passphrase.</p>
                    </div>
                    <code><?= htmlspecialchars((string)$_SESSION['recovery_code_once'], ENT_QUOTES, 'UTF-8') ?></code>
                    <button type="button" class="modal-close-btn" onclick="this.closest('.recovery-code-notice').remove()" aria-label="Dismiss recovery code">&times;</button>
                </section>
                <?php unset($_SESSION['recovery_code_once']); ?>
            <?php endif; ?>

            <!-- Diagnostic Toast Stack -->
            <?php if (!empty($flashMessage)): ?>
                <div class="toast-stack">
                    <div class="toast toast-<?= htmlspecialchars($flashType ?? 'success', ENT_QUOTES, 'UTF-8') ?>" role="alert">
                        <div>
                            <strong><?= $flashType === 'error' ? 'Operational Failure:' : ($flashType === 'warning' ? 'System Advisory:' : 'State Updated:') ?></strong>
                            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <button type="button" class="modal-close-btn" onclick="this.parentElement.remove()" aria-label="Dismiss alert">&times;</button>
                    </div>
                    <?php if (!empty($flashWarnings) && is_array($flashWarnings)): ?>
                        <?php foreach ($flashWarnings as $w): ?>
                            <div class="toast toast-warning" role="alert">
                                <div><strong>Savepoint Rollback Notice:</strong> <?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?></div>
                                <button type="button" class="modal-close-btn" onclick="this.parentElement.remove()">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>