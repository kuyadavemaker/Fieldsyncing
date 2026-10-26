<?php
// Equipment registry view.
declare(strict_types=1);

$uniqueCustomers = count(array_unique(array_column($equipmentRows, 'customer_id')));
?>

<!-- Equipment Telemetry Metrics -->
<section class="stats-grid" aria-label="Equipment fleet telemetry">
    <div class="stat-card stat-cyan">
        <div class="stat-label">Fleet Equipment <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 4l9 7v9H3zM9 20v-6h6v6"/></svg></div>
        <div class="stat-value"><?= count($equipmentRows) ?></div>
        <div class="stat-subtext">Registered customer units</div>
    </div>
    <div class="stat-card stat-violet">
        <div class="stat-label">Client Sites Served <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V4h11v17M15 10h5v11M8 8h3m-3 4h3m-3 4h3m7-2h1m-1 4h1M2 21h20"/></svg></div>
        <div class="stat-value"><?= (int)$uniqueCustomers ?></div>
        <div class="stat-subtext">Distinct asset locations</div>
    </div>
    <div class="stat-card stat-emerald">
        <div class="stat-label">Hardware Integrity <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5-3.4 8.2-8 10-4.6-1.8-8-5-8-10V6l8-3Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg></div>
        <div class="stat-value">100%</div>
        <div class="stat-subtext">Serialized & tracked</div>
    </div>
</section>

<!-- Action Toolbar -->
<div class="toolbar-container">
    <div style="flex: 1;">
        <span style="font-size: 13px; color: var(--text-muted); font-family: var(--font-mono);">
            Fleet Inventory: <?= count($equipmentRows) ?> Active Assets
        </span>
    </div>
</div>

<!-- Equipment Grid -->
<section class="entity-grid">
    <?php if (empty($equipmentRows)): ?>
        <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; color: var(--text-muted); background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl);">
            <svg class="empty-state-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.5 2.6-1.7-.7a8 8 0 0 1-1.6.9l-.3 1.8h-3l-.3-1.8a8 8 0 0 1-1.6-.9l-1.7.7-1.5-2.6 1.4-1.1a7 7 0 0 1 0-1.9l-1.4-1.1 1.5-2.6 1.7.7a8 8 0 0 1 1.6-.9l.3-1.8h3l.3 1.8a8 8 0 0 1 1.6.9l1.7-.7 1.5 2.6-1.4 1.1a7 7 0 0 1 0 1.9Z"/></svg>
            <strong style="color: var(--text-secondary); display: block; font-size: 15px;">No equipment records found</strong>
            <p style="font-size: 13px; margin-top: 6px;">Register HVAC units, boilers, and field machines to attach them to service orders.</p>
        </div>
    <?php else: ?>
        <?php foreach ($equipmentRows as $item): ?>
            <article class="entity-card">
                <div>
                    <div class="entity-card-top">
                        <div class="entity-icon icon-violet"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 4l9 7v9H3zM9 20v-6h6v6"/></svg></div>
                        <div class="entity-info">
                            <h3><?= htmlspecialchars($item['equipment_type'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p style="color: var(--color-cyan); font-family: var(--font-mono); font-size: 12px;">
                                Model: <?= htmlspecialchars($item['model_number'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p style="font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);">
                                S/N: <?= htmlspecialchars($item['serial_number'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p style="margin-top: 6px;">
                                <svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/></svg> <strong>Owner:</strong> <?= htmlspecialchars($item['customer_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    </div>

                    <div class="entity-tags">
                        <span class="tag-pill">
                            <svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg> Installed: <?= htmlspecialchars(!empty($item['install_date']) ? date('M d, Y', strtotime($item['install_date'])) : 'Date Unspecified', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="tag-pill">Asset #<?= (int)$item['equipment_id'] ?></span>
                    </div>
                </div>

                <div class="action-matrix" style="border-top: 1px solid var(--border-subtle); padding-top: 14px;">
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        onclick="editEquipment(<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>)"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/></svg> Edit
                    </button>
                    <form method="POST" action="index.php?page=equipment" onsubmit="return confirm('Delete equipment record \'<?= htmlspecialchars(addslashes($item['equipment_type']), ENT_QUOTES, 'UTF-8') ?>\'?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete_equipment">
                        <input type="hidden" name="equipment_id" value="<?= (int)$item['equipment_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Asset" aria-label="Delete asset"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 0V4h4v3m4 0-1 14H7L6 7m3 4v6m6-6v6"/></svg></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Register Equipment Modal -->
<div id="equipmentModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="add-equip-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="add-equip-title">Register Customer Asset</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('equipmentModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=equipment">
            <input type="hidden" name="action" value="create_equipment">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="reg_customer_id">Customer Owner</label>
                <select name="customer_id" id="reg_customer_id" class="form-select" required>
                    <option value="">-- Select Customer Account --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="reg_equipment_type">Equipment Class / Type</label>
                <input type="text" name="equipment_type" id="reg_equipment_type" class="form-input" placeholder="e.g. Central AC Unit, Commercial Boiler" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="reg_model_number">Model Number</label>
                    <input type="text" name="model_number" id="reg_model_number" class="form-input" placeholder="e.g. Carrier-X800">
                </div>
                <div class="form-group">
                    <label for="reg_serial_number">Serial Number</label>
                    <input type="text" name="serial_number" id="reg_serial_number" class="form-input" placeholder="e.g. SN-AC-90812">
                </div>
            </div>

            <div class="form-group">
                <label for="reg_install_date">Commissioning / Install Date</label>
                <input type="date" name="install_date" id="reg_install_date" class="form-input" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('equipmentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Equipment Modal -->
<div id="editEquipmentModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-equip-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="edit-equip-title">Edit Equipment Specifications</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editEquipmentModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=equipment">
            <input type="hidden" name="action" value="update_equipment">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="equipment_id" id="edit_equipment_id">

            <div class="form-group">
                <label for="edit_equip_customer_id">Customer Owner</label>
                <select name="customer_id" id="edit_equip_customer_id" class="form-select" required>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_equip_type">Equipment Class / Type</label>
                <input type="text" name="equipment_type" id="edit_equip_type" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_equip_model">Model Number</label>
                    <input type="text" name="model_number" id="edit_equip_model" class="form-input">
                </div>
                <div class="form-group">
                    <label for="edit_equip_serial">Serial Number</label>
                    <input type="text" name="serial_number" id="edit_equip_serial" class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label for="edit_equip_install_date">Commissioning / Install Date</label>
                <input type="date" name="install_date" id="edit_equip_install_date" class="form-input">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editEquipmentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Asset</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editEquipment(item) {
        document.getElementById('edit_equipment_id').value = item.equipment_id;
        document.getElementById('edit_equip_customer_id').value = item.customer_id;
        document.getElementById('edit_equip_type').value = item.equipment_type || '';
        document.getElementById('edit_equip_model').value = item.model_number || '';
        document.getElementById('edit_equip_serial').value = item.serial_number || '';
        document.getElementById('edit_equip_install_date').value = item.install_date || '';
        openModal('editEquipmentModal');
    }
</script>
