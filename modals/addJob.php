<div class="modal-backdrop" id="addJobModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="addJobTitle">
        <h2 id="addJobTitle">Create job ticket</h2>
        <p>Schedule a new field service visit.</p>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="form-grid">
                <div class="field"><label for="add_scheduled_datetime">Scheduled date and time</label><input id="add_scheduled_datetime" type="datetime-local" name="scheduled_datetime" required></div>
                <div class="field"><label for="add_status">Status</label><input id="add_status" type="text" name="status" value="Scheduled" placeholder="e.g. Scheduled, In Progress, Completed" required></div>
                <div class="field"><label for="add_customer_id">Customer</label><select id="add_customer_id" name="customer_id" required><option value="">Select customer</option><?php foreach ($customers as $customer): ?><option value="<?= (int) $customer['customer_id'] ?>"><?= htmlspecialchars($customer['full_name']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="add_equipment_id">Equipment ID</label><input id="add_equipment_id" type="number" name="equipment_id" min="1" placeholder="Enter Equipment ID" required></div>
                <div class="field full"><label for="add_description">Job description</label><textarea id="add_description" name="description" required></textarea></div>
            </div>
            <div class="modal-actions"><button type="button" class="button secondary" data-close-modal="addJobModal">Cancel</button><button type="submit">Create ticket</button></div>
        </form>
    </div>
</div>
