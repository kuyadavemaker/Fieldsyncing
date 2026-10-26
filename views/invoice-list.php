<?php
// Invoice registry view.
declare(strict_types=1);

$totalBilled = 0.0;
$totalCollected = 0.0;
$totalUnpaid = 0.0;

foreach ($invoiceRows as $inv) {
    $amt = (float)$inv['total_amount'];
    $totalBilled += $amt;
    $status = strtolower($inv['payment_status'] ?? 'unpaid');
    if ($status === 'paid') {
        $totalCollected += $amt;
    } else {
        $totalUnpaid += $amt;
    }
}
?>

<!-- Financial Telemetry Metrics -->
<section class="stats-grid invoice-stats" aria-label="Financial telemetry">
    <div class="stat-card stat-cyan">
        <div class="stat-label">Total Receivables <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6m-6 4h6"/></svg></div>
        <div class="stat-value">$<?= number_format($totalBilled, 2) ?></div>
        <div class="stat-subtext"><?= count($invoiceRows) ?> invoices generated</div>
    </div>
    <div class="stat-card stat-emerald">
        <div class="stat-label">Settled Revenue <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></div>
        <div class="stat-value">$<?= number_format($totalCollected, 2) ?></div>
        <div class="stat-subtext">Collected and reconciled</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-label">Outstanding Balance <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 3h8M8 21h8m-4-18v4l3 3-3 3v4"/></svg></div>
        <div class="stat-value">$<?= number_format($totalUnpaid, 2) ?></div>
        <div class="stat-subtext">Pending client settlement</div>
    </div>
</section>

<!-- Action Toolbar -->
<div class="toolbar-container">
    <div style="flex: 1;">
        <span style="font-size: 13px; color: var(--text-muted); font-family: var(--font-mono);">
            Ledger Scope: <?= count($invoiceRows) ?> Commercial Invoices
        </span>
    </div>
</div>

<!-- Master Financial Matrix Table -->
<section class="card-matrix invoice-ledger">
    <div class="card-matrix-header">
        <h2>
            Billing & Invoicing Ledger
            <span class="count-badge"><?= count($invoiceRows) ?> Invoices</span>
        </h2>
        <span style="font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);">
            Outstanding: $<?= number_format($totalUnpaid, 2) ?>
        </span>
    </div>

    <?php if (empty($invoiceRows)): ?>
        <div style="padding: 50px 20px; text-align: center; color: var(--text-muted);">
            <svg class="empty-state-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18m-14 5h4"/></svg>
            <strong style="color: var(--text-secondary); display: block; font-size: 15px;">No billing invoices recorded</strong>
            <p style="font-size: 13px; margin-top: 6px;">Issue invoices for completed service dispatches or generate them directly from work orders.</p>
        </div>
    <?php else: ?>
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Work Order Ticket</th>
                        <th>Customer / Client</th>
                        <th>Issue Date</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th style="text-align: right;">Action Matrix</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoiceRows as $invoice):
                        $statusClass = match (strtolower($invoice['payment_status'])) {
                            'paid' => 'badge-paid',
                            'partial' => 'badge-partial',
                            default => 'badge-unpaid',
                        };
                    ?>
                        <tr>
                            <td class="col-mono" style="color: var(--color-cyan); font-weight: 700;">
                                #<?= (int)$invoice['invoice_id'] ?>
                            </td>
                            <td>
                                <a href="index.php?page=jobs&search=<?= (int)$invoice['job_id'] ?>" style="color: #ffffff; text-decoration: none; font-weight: 600;">
                                    Ticket #<?= (int)$invoice['job_id'] ?>
                                </a>
                                <small style="display: block; color: var(--text-muted); font-size: 11px;">
                                    <?= htmlspecialchars($invoice['equipment_type'] ?? 'Asset', ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($invoice['customer_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($invoice['customer_phone'])): ?>
                                    <small style="display: block; color: var(--text-muted); font-size: 11px;">
                                        <?= htmlspecialchars($invoice['customer_phone'], ENT_QUOTES, 'UTF-8') ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="col-mono">
                                <?= date('M d, Y', strtotime($invoice['issued_date'])) ?>
                            </td>
                            <td class="col-mono" style="font-size: 15px; font-weight: 700; color: #ffffff;">
                                $<?= number_format((float)$invoice['total_amount'], 2) ?>
                            </td>
                            <td>
                                <form method="POST" action="index.php?page=invoices" style="display:inline-flex; align-items:center;">
                                    <input type="hidden" name="action" value="update_invoice_status">
                                    <input type="hidden" name="invoice_id" value="<?= (int)$invoice['invoice_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                    <select
                                        name="payment_status"
                                        class="form-select"
                                        style="padding: 4px 8px; font-size: 12px; width: auto;"
                                        onchange="this.form.submit()"
                                    >
                                        <option value="Unpaid" <?= $invoice['payment_status'] === 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                        <option value="Paid" <?= $invoice['payment_status'] === 'Paid' ? 'selected' : '' ?>>Paid</option>
                                        <option value="Partial" <?= $invoice['payment_status'] === 'Partial' ? 'selected' : '' ?>>Partial</option>
                                        <option value="Cancelled" <?= $invoice['payment_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-matrix" style="justify-content: flex-end;">
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        onclick="editInvoice(<?= htmlspecialchars(json_encode($invoice), ENT_QUOTES, 'UTF-8') ?>)"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/></svg> Edit
                                    </button>
                                    <form method="POST" action="index.php?page=invoices" onsubmit="return confirm('Delete Invoice #<?= (int)$invoice['invoice_id'] ?>?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_invoice">
                                        <input type="hidden" name="invoice_id" value="<?= (int)$invoice['invoice_id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Invoice" aria-label="Delete invoice"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 0V4h4v3m4 0-1 14H7L6 7m3 4v6m6-6v6"/></svg></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Issue Invoice Modal -->
<div id="invoiceModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="add-inv-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="add-inv-title">Generate Billing Invoice</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('invoiceModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=invoices">
            <input type="hidden" name="action" value="generate_invoice">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="create_inv_job_id">Select Work Order Ticket</label>
                <select name="job_id" id="create_inv_job_id" class="form-select" required>
                    <option value="">-- Choose Work Order --</option>
                    <?php foreach ($invoiceJobs as $j): ?>
                        <option value="<?= (int)$j['job_id'] ?>">
                            Ticket #<?= (int)$j['job_id'] ?> — <?= htmlspecialchars($j['customer_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($j['status'], ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="create_inv_amount">Total Amount ($)</label>
                    <input type="number" name="total_amount" id="create_inv_amount" step="0.01" min="0" class="form-input" placeholder="185.00" required>
                </div>
                <div class="form-group">
                    <label for="create_inv_date">Billing Date</label>
                    <input type="date" name="issued_date" id="create_inv_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="create_inv_status">Initial Payment Status</label>
                <select name="payment_status" id="create_inv_status" class="form-select" required>
                    <option value="Unpaid" selected>Unpaid</option>
                    <option value="Paid">Paid</option>
                    <option value="Partial">Partial</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('invoiceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Issue Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Invoice Modal -->
<div id="editInvoiceModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-inv-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="edit-inv-title">Edit Invoice Ledger Entry</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editInvoiceModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=invoices">
            <input type="hidden" name="action" value="update_invoice">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="invoice_id" id="edit_inv_id">

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_inv_amount">Total Amount ($)</label>
                    <input type="number" name="total_amount" id="edit_inv_amount" step="0.01" min="0" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_inv_date">Issued Date</label>
                    <input type="date" name="issued_date" id="edit_inv_date" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_inv_status">Payment Status</label>
                <select name="payment_status" id="edit_inv_status" class="form-select" required>
                    <option value="Unpaid">Unpaid</option>
                    <option value="Paid">Paid</option>
                    <option value="Partial">Partial</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editInvoiceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Invoice</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editInvoice(item) {
        document.getElementById('edit_inv_id').value = item.invoice_id;
        document.getElementById('edit_inv_amount').value = item.total_amount || 0.00;
        document.getElementById('edit_inv_date').value = item.issued_date || '';
        document.getElementById('edit_inv_status').value = item.payment_status || 'Unpaid';
        openModal('editInvoiceModal');
    }
</script>
