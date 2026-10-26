<?php
// Job dialog templates.
declare(strict_types=1);
?>

<!-- 1. Standard Create Job Ticket Modal -->
<div id="createModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="create-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="create-title">New job</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('createModal')" aria-label="Close dialog">&times;</button>
        </div>
        <form method="POST" action="index.php?page=jobs">
            <input type="hidden" name="action" value="create_job">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="create_customer_id">Customer</label>
                <select
                    name="customer_id"
                    id="create_customer_id"
                    class="form-select"
                    required
                    onchange="filterEquipmentByCustomer('create_customer_id', 'create_equipment_id')"
                >
                    <option value="">Select customer</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['customer_id'] ?>">
                            <?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($c['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="create_equipment_id">Equipment</label>
                <select name="equipment_id" id="create_equipment_id" class="form-select" required>
                    <option value="">Select equipment</option>
                    <?php foreach ($equipment as $e): ?>
                        <option
                            value="<?= (int)$e['equipment_id'] ?>"
                            data-customer-id="<?= (int)$e['customer_id'] ?>"
                        >
                            <?= htmlspecialchars($e['equipment_type'] . (!empty($e['model_number']) ? ' [' . $e['model_number'] . ']' : ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="create_scheduled_datetime">Schedule</label>
                    <input
                        type="datetime-local"
                        name="scheduled_datetime"
                        id="create_scheduled_datetime"
                        class="form-input"
                        value="<?= date('Y-m-d\TH:i', strtotime('+2 hours')) ?>"
                        required
                    >
                </div>
                <div class="form-group">
                    <label for="create_status">Status</label>
                    <select name="status" id="create_status" class="form-select" required>
                        <option value="Scheduled" selected>Scheduled</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="create_description">Notes</label>
                <textarea
                    name="description"
                    id="create_description"
                    rows="3"
                    class="form-textarea"
                    placeholder="Add job notes"
                ></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Ticket</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Intermediate SAVEPOINT Compound Multi-Step Dispatch Modal -->
<div id="compoundDispatchModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="compound-title">
    <div class="modal-content" style="max-width: 640px;">
        <div class="modal-header">
            <div>
                <h3 id="compound-title"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7z"/></svg> Multi-Step Compound Dispatch</h3>
                <small style="color: var(--color-cyan); font-family: var(--font-mono); font-size: 11px;">
                    ACID Storage Sequence with Intermediate Savepoints
                </small>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeModal('compoundDispatchModal')" aria-label="Close dialog">&times;</button>
        </div>

        <form method="POST" action="index.php?page=jobs">
            <input type="hidden" name="action" value="compound_dispatch">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <!-- Step 1: Base Ticket -->
            <div style="padding: 12px; background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.2); border-radius: var(--radius-md); margin-bottom: 14px;">
                <strong style="color: var(--color-cyan); font-size: 12px; text-transform: uppercase; font-family: var(--font-mono); display: block; margin-bottom: 8px;">
                    Step 1: Work Order Core [Savepoint: sp_job_entry]
                </strong>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label>Customer</label>
                        <select name="customer_id" id="comp_customer_id" class="form-select" required onchange="filterEquipmentByCustomer('comp_customer_id', 'comp_equipment_id')">
                            <option value="">Select customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label>Equipment</label>
                        <select name="equipment_id" id="comp_equipment_id" class="form-select" required>
                            <option value="">Select equipment</option>
                            <?php foreach ($equipment as $e): ?>
                                <option value="<?= (int)$e['equipment_id'] ?>" data-customer-id="<?= (int)$e['customer_id'] ?>">
                                    <?= htmlspecialchars($e['equipment_type'] . (!empty($e['model_number']) ? ' [' . $e['model_number'] . ']' : ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label>Scheduled</label>
                        <input type="datetime-local" name="scheduled_datetime" class="form-input" value="<?= date('Y-m-d\TH:i', strtotime('+2 hours')) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label>Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Scheduled" selected>Scheduled</option>
                            <option value="In Progress">In Progress</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Description</label>
                    <input type="text" name="description" class="form-input" placeholder="Service notes...">
                </div>
            </div>

            <!-- Step 2: Immediate Technician Assignment -->
            <div style="padding: 12px; background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: var(--radius-md); margin-bottom: 14px;">
                <strong style="color: var(--color-violet); font-size: 12px; text-transform: uppercase; font-family: var(--font-mono); display: block; margin-bottom: 8px;">
                    Step 2: Field Technician Allocation [Savepoint: sp_tech_dispatch]
                </strong>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Assign Technician (Optional)</label>
                        <select name="technician_id" class="form-select">
                            <option value="">-- No Immediate Tech --</option>
                            <?php foreach ($technicianRows as $t): ?>
                                <option value="<?= (int)$t['technician_id'] ?>">
                                    <?= htmlspecialchars($t['full_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($t['license_number'], ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Role</label>
                        <input type="text" name="tech_role" class="form-input" value="Lead Technician">
                    </div>
                </div>
            </div>

            <!-- Step 3: Immediate Part Allocation -->
            <div style="padding: 12px; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: var(--radius-md); margin-bottom: 14px;">
                <strong style="color: var(--color-emerald); font-size: 12px; text-transform: uppercase; font-family: var(--font-mono); display: block; margin-bottom: 8px;">
                    Step 3: Inventory Part Allocation [Savepoint: sp_initial_part]
                </strong>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Inventory Part (Optional)</label>
                        <select name="part_id" id="comp_part_id" class="form-select" onchange="updateCompoundPartPrice()">
                            <option value="">-- No Immediate Part --</option>
                            <?php foreach ($inventoryRows as $p): ?>
                                <option
                                    value="<?= (int)$p['part_id'] ?>"
                                    data-cost="<?= (float)$p['unit_cost'] ?>"
                                    data-stock="<?= (int)$p['quantity_in_stock'] ?>"
                                >
                                    <?= htmlspecialchars($p['part_name'], ENT_QUOTES, 'UTF-8') ?> [Stock: <?= (int)$p['quantity_in_stock'] ?> @ $<?= number_format((float)$p['unit_cost'], 2) ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row" style="gap: 8px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Quantity</label>
                            <input type="number" name="part_quantity" id="comp_part_qty" class="form-input" value="1" min="1">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Price ($)</label>
                            <input type="number" name="part_price" id="comp_part_price" step="0.01" class="form-input" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin: 10px 0 0;">
                    <label for="comp_service_vehicle">Service Van / Truck ID (required when allocating a part)</label>
                    <input type="text" name="service_vehicle" id="comp_service_vehicle" class="form-input" maxlength="100" placeholder="e.g. Van 04 or Truck HVAC-2">
                </div>
            </div>

            <!-- Step 4: Immediate Invoice Initialization -->
            <div style="padding: 12px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: var(--radius-md); margin-bottom: 18px;">
                <strong style="color: var(--color-amber); font-size: 12px; text-transform: uppercase; font-family: var(--font-mono); display: block; margin-bottom: 8px;">
                    Step 4: Invoice Generation [Savepoint: sp_invoice_init]
                </strong>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Initial Invoice Amount ($ - Optional)</label>
                    <input type="number" name="invoice_amount" step="0.01" class="form-input" placeholder="Leave blank to defer billing...">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('compoundDispatchModal')">Cancel</button>
                <button type="submit" class="btn btn-emerald">Execute ACID Dispatch</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Edit Ticket Modal -->
<div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="edit-title">Edit Work Order Ticket</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editModal')" aria-label="Close dialog">&times;</button>
        </div>
        <form method="POST" action="index.php?page=jobs">
            <input type="hidden" name="action" value="update_job">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="job_id" id="edit_job_id">

            <div class="form-group">
                <label for="edit_customer_id">Customer</label>
                <select name="customer_id" id="edit_customer_id" class="form-select" required onchange="filterEquipmentByCustomer('edit_customer_id', 'edit_equipment_id')">
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int)$c['customer_id'] ?>"><?= htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="edit_equipment_id">Equipment</label>
                <select name="equipment_id" id="edit_equipment_id" class="form-select" required>
                    <?php foreach ($equipment as $e): ?>
                        <option value="<?= (int)$e['equipment_id'] ?>" data-customer-id="<?= (int)$e['customer_id'] ?>">
                            <?= htmlspecialchars($e['equipment_type'] . (!empty($e['model_number']) ? ' [' . $e['model_number'] . ']' : ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_scheduled_datetime">Scheduled Datetime</label>
                    <input type="datetime-local" name="scheduled_datetime" id="edit_scheduled_datetime" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_status">Ticket Status</label>
                    <select name="status" id="edit_status" class="form-select" required>
                        <option value="Scheduled">Scheduled</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_description">Service Description</label>
                <textarea name="description" id="edit_description" rows="3" class="form-textarea"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Assign Technician Modal -->
<div id="assignTechnicianModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="assign-tech-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="assign-tech-title">Assign Field Technician</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('assignTechnicianModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=jobs">
            <input type="hidden" name="action" value="assign_technician">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="job_id" id="assign_job_id">

            <div class="form-group">
                <label>Target Work Order</label>
                <input type="text" id="assign_job_id_display" class="form-input" readonly>
            </div>

            <div class="form-group">
                <label for="assign_technician_id">Select Technician</label>
                <select name="technician_id" id="assign_technician_id" class="form-select" required>
                    <option value="">-- Choose Field Technician --</option>
                    <?php foreach ($technicianRows as $t): ?>
                        <option value="<?= (int)$t['technician_id'] ?>">
                            <?= htmlspecialchars($t['full_name'], ENT_QUOTES, 'UTF-8') ?> (Lic: <?= htmlspecialchars($t['license_number'], ENT_QUOTES, 'UTF-8') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="assign_role">Operational Role</label>
                <select name="role_on_job" id="assign_role" class="form-select" required>
                    <option value="Lead Technician" selected>Lead Technician</option>
                    <option value="Assistant Technician">Assistant Technician</option>
                    <option value="Diagnostics Specialist">Diagnostics Specialist</option>
                    <option value="Inspector">Inspector</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignTechnicianModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Assign to Work Order</button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Log Part Modal -->
<div id="logPartModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="log-part-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="log-part-title">Log Inventory Part Usage</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('logPartModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=jobs">
            <input type="hidden" name="action" value="log_part_used">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="job_id" id="log_part_job_id">

            <div class="form-group">
                <label>Target Work Order</label>
                <input type="text" id="log_part_job_id_display" class="form-input" readonly>
            </div>

            <div class="form-group">
                <label for="log_part_id">Parts Inventory</label>
                <select name="part_id" id="log_part_id" class="form-select" required onchange="onPartSelectChange()">
                    <option value="">-- Select Inventory Item --</option>
                    <?php foreach ($inventoryRows as $p): ?>
                        <option
                            value="<?= (int)$p['part_id'] ?>"
                            data-cost="<?= (float)$p['unit_cost'] ?>"
                            data-stock="<?= (int)$p['quantity_in_stock'] ?>"
                        >
                            <?= htmlspecialchars($p['part_name'], ENT_QUOTES, 'UTF-8') ?> [SKU: <?= htmlspecialchars($p['sku'], ENT_QUOTES, 'UTF-8') ?>] (Available: <?= (int)$p['quantity_in_stock'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="log_part_quantity">Quantity Used</label>
                    <input type="number" name="quantity_used" id="log_part_quantity" class="form-input" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label for="log_part_price">Price Charged ($)</label>
                    <input type="number" name="price_charged" id="log_part_price" step="0.01" min="0" class="form-input" placeholder="0.00" required>
                </div>
            </div>
            <div class="form-group">
                <label for="log_service_vehicle">Service Van / Truck ID</label>
                <input type="text" name="service_vehicle" id="log_service_vehicle" class="form-input" maxlength="100" placeholder="e.g. Van 04 or Truck HVAC-2" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('logPartModal')">Cancel</button>
                <button type="submit" class="btn btn-emerald">Deduct Stock & Log Usage</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. Generate Invoice Modal -->
<div id="jobInvoiceModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="job-invoice-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="job-invoice-title">Issue Invoice for Work Order</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('jobInvoiceModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=jobs">
            <input type="hidden" name="action" value="generate_invoice">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="job_id" id="invoice_job_id">

            <div class="form-group">
                <label>Target Work Order</label>
                <input type="text" id="invoice_job_id_display" class="form-input" readonly>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_total_amount">Total Amount ($)</label>
                    <input type="number" name="total_amount" id="invoice_total_amount" step="0.01" min="0" class="form-input" placeholder="150.00" required>
                </div>
                <div class="form-group">
                    <label for="invoice_issued_date">Billing Date</label>
                    <input type="date" name="issued_date" id="invoice_issued_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="invoice_payment_status">Initial Payment Status</label>
                <select name="payment_status" id="invoice_payment_status" class="form-select" required>
                    <option value="Unpaid" selected>Unpaid</option>
                    <option value="Paid">Paid</option>
                    <option value="Partial">Partial</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('jobInvoiceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pre-fill Handlers -->
<script>
    function openEditModal(job) {
        document.getElementById('edit_job_id').value = job.job_id;
        document.getElementById('edit_customer_id').value = job.customer_id;
        filterEquipmentByCustomer('edit_customer_id', 'edit_equipment_id');
        document.getElementById('edit_equipment_id').value = job.equipment_id;

        if (job.scheduled_datetime) {
            const dt = new Date(job.scheduled_datetime.replace(' ', 'T'));
            if (!isNaN(dt.getTime())) {
                const formatted = new Date(dt.getTime() - dt.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
                document.getElementById('edit_scheduled_datetime').value = formatted;
            }
        }

        document.getElementById('edit_status').value = job.status || 'Scheduled';
        document.getElementById('edit_description').value = job.description || '';
        openModal('editModal');
    }

    function openAssignTechnicianModal(jobId) {
        document.getElementById('assign_job_id').value = jobId;
        document.getElementById('assign_job_id_display').value = 'Work Order #' + jobId;
        openModal('assignTechnicianModal');
    }

    function openLogPartModal(jobId) {
        document.getElementById('log_part_job_id').value = jobId;
        document.getElementById('log_part_job_id_display').value = 'Work Order #' + jobId;
        openModal('logPartModal');
    }

    function openGenerateInvoiceModal(jobId) {
        document.getElementById('invoice_job_id').value = jobId;
        document.getElementById('invoice_job_id_display').value = 'Work Order #' + jobId;
        openModal('jobInvoiceModal');
    }

    function onPartSelectChange() {
        const select = document.getElementById('log_part_id');
        const selected = select.selectedOptions[0];
        if (selected && selected.getAttribute('data-cost')) {
            document.getElementById('log_part_price').value = selected.getAttribute('data-cost');
        }
    }

    function updateCompoundPartPrice() {
        const select = document.getElementById('comp_part_id');
        const selected = select.selectedOptions[0];
        if (selected && selected.getAttribute('data-cost')) {
            document.getElementById('comp_part_price').value = selected.getAttribute('data-cost');
        }
    }
</script>