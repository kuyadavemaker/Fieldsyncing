<?php
// Customer registry view.
declare(strict_types=1);

$totalEquipCount = array_sum(array_column($customerRows, 'equipment_count'));
$totalJobCount = array_sum(array_column($customerRows, 'job_count'));
?>

<!-- Customer Telemetry Metrics -->
<section class="stats-grid" aria-label="Customer telemetry">
    <div class="stat-card stat-cyan">
        <div class="stat-label">Customer Accounts <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m1-13h5m-2.5-2.5v5"/></svg></div>
        <div class="stat-value"><?= count($customerRows) ?></div>
        <div class="stat-subtext">Active client accounts</div>
    </div>
    <div class="stat-card stat-violet">
        <div class="stat-label">Connected Assets <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 4l9 7v9H3zM9 20v-6h6v6"/></svg></div>
        <div class="stat-value"><?= (int)$totalEquipCount ?></div>
        <div class="stat-subtext">Registered customer equipment</div>
    </div>
    <div class="stat-card stat-emerald">
        <div class="stat-label">Service History <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4H5v17h14V4h-3M9 3h6v4H9zM8 11h8m-8 4h8m-8 4h5"/></svg></div>
        <div class="stat-value"><?= (int)$totalJobCount ?></div>
        <div class="stat-subtext">Cumulative work orders</div>
    </div>
</section>

<!-- Action Toolbar -->
<div class="toolbar-container">
    <div style="flex: 1;">
        <span style="font-size: 13px; color: var(--text-muted); font-family: var(--font-mono);">
            Registry Scope: <?= count($customerRows) ?> Customer Profiles
        </span>
    </div>
</div>

<!-- Customer Registry Grid -->
<section class="entity-grid">
    <?php if (empty($customerRows)): ?>
        <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; color: var(--text-muted); background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl);">
            <svg class="empty-state-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m1-13h5m-2.5-2.5v5"/></svg>
            <strong style="color: var(--text-secondary); display: block; font-size: 15px;">No customer records present</strong>
            <p style="font-size: 13px; margin-top: 6px;">Register customer accounts to begin linking equipment and scheduling service dispatches.</p>
        </div>
    <?php else: ?>
        <?php foreach ($customerRows as $c): ?>
            <article class="entity-card">
                <div>
                    <div class="entity-card-top">
                        <div class="entity-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></div>
                        <div class="entity-info">
                            <h3><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p style="color: var(--color-cyan); font-family: var(--font-mono); font-size: 12px;">
                                <svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h4l2 5-3 2a15 15 0 0 0 5 5l2-3 5 2v4c0 1.1-.9 2-2 2C10 20 4 14 4 5c0-1.1.9-2 2-2Z"/></svg> <?= htmlspecialchars($c['phone'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg> <?= htmlspecialchars($c['email'] ?: 'No email on record', ENT_QUOTES, 'UTF-8') ?></p>
                            <p><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg> <?= htmlspecialchars($c['billing_address'] ?: 'No billing address on record', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <div class="entity-tags">
                        <span class="tag-pill"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 4l9 7v9H3zM9 20v-6h6v6"/></svg> <?= (int)$c['equipment_count'] ?> Equipment Assets</span>
                        <span class="tag-pill"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="17" rx="2"/><path d="M8 2v4m8-4v4M4 10h16"/></svg> <?= (int)$c['job_count'] ?> Service Tickets</span>
                    </div>
                </div>

                <div class="action-matrix" style="border-top: 1px solid var(--border-subtle); padding-top: 14px;">
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        onclick="editCustomer(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/></svg> Edit
                    </button>
                    <button
                        type="button"
                        class="btn btn-cyan-subtle btn-sm"
                        onclick="quickDispatchForCustomer(<?= (int)$c['customer_id'] ?>)"
                    >
                        + Ticket
                    </button>
                    <form method="POST" action="index.php?page=customers" onsubmit="return confirm('Delete customer \'<?= htmlspecialchars(addslashes($c['full_name']), ENT_QUOTES, 'UTF-8') ?>\'? All linked equipment, jobs, and invoices will be purged under ACID cascade.');" style="display:inline;">
                        <input type="hidden" name="action" value="delete_customer">
                        <input type="hidden" name="customer_id" value="<?= (int)$c['customer_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Account" aria-label="Delete customer account"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 0V4h4v3m4 0-1 14H7L6 7m3 4v6m6-6v6"/></svg></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Add Customer Modal -->
<div id="customerModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="add-cust-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="add-cust-title">Register Customer Account</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('customerModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=customers">
            <input type="hidden" name="action" value="create_customer">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="create_cust_name">Full Name / Organization</label>
                <input type="text" name="full_name" id="create_cust_name" class="form-input" placeholder="e.g. Acme Corporation or Jane Doe" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="create_cust_phone">Phone Number</label>
                    <input type="text" name="phone" id="create_cust_phone" class="form-input" placeholder="+1 (555) 000-0000" required>
                </div>
                <div class="form-group">
                    <label for="create_cust_email">Email Address</label>
                    <input type="email" name="email" id="create_cust_email" class="form-input" placeholder="client@example.com">
                </div>
            </div>

            <div class="form-group">
                <label for="create_cust_address">Service & Billing Address</label>
                <input type="text" name="billing_address" id="create_cust_address" class="form-input" placeholder="123 Industrial Way, Suite 400">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('customerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div id="editCustomerModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-cust-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="edit-cust-title">Edit Customer Account</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editCustomerModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=customers">
            <input type="hidden" name="action" value="update_customer">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="customer_id" id="edit_cust_id">

            <div class="form-group">
                <label for="edit_cust_name">Full Name / Organization</label>
                <input type="text" name="full_name" id="edit_cust_name" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_cust_phone">Phone Number</label>
                    <input type="text" name="phone" id="edit_cust_phone" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_cust_email">Email Address</label>
                    <input type="email" name="email" id="edit_cust_email" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label for="edit_cust_address">Billing Address</label>
                <input type="text" name="billing_address" id="edit_cust_address" class="form-input">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCustomerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Account</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editCustomer(item) {
        document.getElementById('edit_cust_id').value = item.customer_id;
        document.getElementById('edit_cust_name').value = item.full_name || '';
        document.getElementById('edit_cust_phone').value = item.phone || '';
        document.getElementById('edit_cust_email').value = item.email || '';
        document.getElementById('edit_cust_address').value = item.billing_address || '';
        openModal('editCustomerModal');
    }

    function quickDispatchForCustomer(customerId) {
        window.location.href = 'index.php?page=jobs&open=create&customer_id=' + customerId;
    }
</script>
