<?php
// Inventory registry view.
declare(strict_types=1);

$totalStockCount = array_sum(array_column($inventoryRows, 'quantity_in_stock'));
$totalValuation = 0.0;
$lowStockCount = 0;
foreach ($inventoryRows as $p) {
    $qty = (int)$p['quantity_in_stock'];
    $cost = (float)$p['unit_cost'];
    $totalValuation += ($qty * $cost);
    if ($qty <= 5) {
        $lowStockCount++;
    }
}
?>

<!-- Inventory Telemetry Metrics -->
<section class="stats-grid" aria-label="Inventory telemetry">
    <div class="stat-card stat-cyan inventory-stat">
        <div class="stat-label">Stocked SKUs <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v14H4zM3 3h18v4H3zM8 11h8M8 15h5"/></svg></div>
        <div class="stat-value"><?= count($inventoryRows) ?></div>
        <div class="stat-subtext">Active part catalog items</div>
    </div>
    <div class="stat-card stat-<?= $lowStockCount > 0 ? 'amber' : 'emerald' ?> inventory-stat">
        <div class="stat-label">Low Stock Alerts <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 18H2L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg></div>
        <div class="stat-value"><?= $lowStockCount ?></div>
        <div class="stat-subtext">Items at or below 5 units</div>
    </div>
    <div class="stat-card stat-emerald inventory-stat">
        <div class="stat-label">Inventory Valuation <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15.5 8.5c-.7-.8-1.8-1.2-3.2-1.2-1.8 0-3 .8-3 2s1.2 1.8 3 2.2 3 1 3 2.2-1.2 2-3 2c-1.4 0-2.5-.4-3.2-1.2M12 5v14"/></svg></div>
        <div class="stat-value">$<?= number_format($totalValuation, 2) ?></div>
        <div class="stat-subtext"><?= (int)$totalStockCount ?> total physical units</div>
    </div>
</section>

<!-- Action Toolbar -->
<div class="toolbar-container">
    <div style="flex: 1;">
        <span class="inventory-summary">
            <?= count($inventoryRows) ?> managed <?= count($inventoryRows) === 1 ? 'SKU' : 'SKUs' ?>
        </span>
    </div>
</div>

<!-- Master Inventory Table -->
<section class="card-matrix">
    <div class="card-matrix-header">
        <h2>
            Warehouse Parts & Components
            <span class="count-badge"><?= count($inventoryRows) ?> SKUs</span>
        </h2>
        <span class="inventory-valuation">
            Valuation: $<?= number_format($totalValuation, 2) ?>
        </span>
    </div>

    <?php if (empty($inventoryRows)): ?>
        <div class="inventory-empty-state">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 7 9-4 9 4v10l-9 4-9-4V7Z"/><path d="m3 7 9 4 9-4M12 11v10M7.5 5l9 4"/></svg>
            <strong>Inventory catalog is empty</strong>
            <p>Add your first part to start tracking stock and field usage.</p>
        </div>
    <?php else: ?>
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Part / Component</th>
                        <th>SKU Code</th>
                        <th>Stock Level</th>
                        <th>Unit Cost</th>
                        <th>Total Asset Value</th>
                        <th style="text-align: right;">Action Matrix</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventoryRows as $part):
                        $qty = (int)$part['quantity_in_stock'];
                        $cost = (float)$part['unit_cost'];
                        $itemTotal = $qty * $cost;
                        $statusClass = match ($part['stock_status']) {
                            'Out of stock' => 'badge-cancelled',
                            'Low stock' => 'badge-inprogress',
                            default => 'badge-completed',
                        };
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($part['part_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small style="display:block; color: var(--text-muted); font-family: var(--font-mono); font-size: 11px;">
                                    Part ID #<?= (int)$part['part_id'] ?>
                                </small>
                            </td>
                            <td class="col-mono" style="color: var(--color-cyan);">
                                <?= htmlspecialchars($part['sku'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="col-mono" style="font-size: 15px; font-weight: 700;">
                                        <?= $qty ?>
                                    </span>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($part['stock_status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </td>
                            <td class="col-mono">
                                $<?= number_format($cost, 2) ?>
                            </td>
                            <td class="col-mono" style="color: var(--color-emerald);">
                                $<?= number_format($itemTotal, 2) ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="action-matrix" style="justify-content: flex-end;">
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm"
                                        onclick="editPart(<?= htmlspecialchars(json_encode($part), ENT_QUOTES, 'UTF-8') ?>)"
                                    >
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/></svg> Edit
                                    </button>
                                    <form method="POST" action="index.php?page=inventory" onsubmit="return confirm('Delete part \'<?= htmlspecialchars(addslashes($part['part_name']), ENT_QUOTES, 'UTF-8') ?>\'?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_part">
                                        <input type="hidden" name="part_id" value="<?= (int)$part['part_id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Part" aria-label="Delete part"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 0V4h4v3m4 0-1 14H7L6 7m3 4v6m6-6v6"/></svg></button>
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

<!-- Restock / Create Part Modal -->
<div id="partModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="add-part-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="add-part-title">Restock or Register Inventory Part</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('partModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=inventory">
            <input type="hidden" name="action" value="create_part">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="create_part_name">Part / Component Name</label>
                <input type="text" name="part_name" id="create_part_name" class="form-input" placeholder="e.g. Dual Run Capacitor 45/5 MFD" required>
            </div>

            <div class="form-group">
                <label for="create_part_sku">SKU / Stock Identifier</label>
                <input type="text" name="sku" id="create_part_sku" class="form-input" placeholder="e.g. CAP-45-5" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="create_part_quantity">Quantity to Add</label>
                    <input type="number" name="quantity" id="create_part_quantity" class="form-input" min="0" value="10" required>
                </div>
                <div class="form-group">
                    <label for="create_part_cost">Unit Cost ($)</label>
                    <input type="number" name="unit_cost" id="create_part_cost" class="form-input" min="0" step="0.01" placeholder="18.50" required>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('partModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save to Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Part Modal -->
<div id="editPartModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-part-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="edit-part-title">Edit Inventory Specifications</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editPartModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=inventory">
            <input type="hidden" name="action" value="update_part">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="part_id" id="edit_part_id">

            <div class="form-group">
                <label for="edit_part_name">Part Name</label>
                <input type="text" name="part_name" id="edit_part_name" class="form-input" required>
            </div>

            <div class="form-group">
                <label for="edit_part_sku">SKU Code</label>
                <input type="text" name="sku" id="edit_part_sku" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_part_quantity">Stock Level (Units)</label>
                    <input type="number" name="quantity" id="edit_part_quantity" class="form-input" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_part_cost">Unit Cost ($)</label>
                    <input type="number" name="unit_cost" id="edit_part_cost" class="form-input" min="0" step="0.01" required>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editPartModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Part</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editPart(item) {
        document.getElementById('edit_part_id').value = item.part_id;
        document.getElementById('edit_part_name').value = item.part_name || '';
        document.getElementById('edit_part_sku').value = item.sku || '';
        document.getElementById('edit_part_quantity').value = item.quantity_in_stock || 0;
        document.getElementById('edit_part_cost').value = item.unit_cost || 0.00;
        openModal('editPartModal');
    }
</script>
