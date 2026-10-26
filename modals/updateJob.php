<div class="modal-backdrop" id="updateJobModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="updateJobTitle">
        <h2 id="updateJobTitle">Edit job ticket</h2>
        <p>Update the dispatch details below.</p>
        <form method="post">
            <input type="hidden" name="action" value="update"><input type="hidden" name="job_id" id="update_job_id">
            <div class="form-grid">
                <div class="field"><label for="update_scheduled_datetime">Scheduled date and time</label><input id="update_scheduled_datetime" type="datetime-local" name="scheduled_datetime" required></div>
                <div class="field"><label for="update_status">Status</label><input id="update_status" type="text" name="status" placeholder="e.g. Scheduled, In Progress, Completed" required></div>
                <div class="field"><label for="update_customer_id">Customer</label><select id="update_customer_id" name="customer_id" required><?php foreach ($customers as $customer): ?><option value="<?= (int) $customer['customer_id'] ?>"><?= htmlspecialchars($customer['full_name']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label for="update_equipment_id">Equipment ID</label><input id="update_equipment_id" type="number" name="equipment_id" min="1" placeholder="Enter Equipment ID" required></div>
                <div class="field full"><label for="update_description">Job description</label><textarea id="update_description" name="description" required></textarea></div>
            </div>
            <div class="modal-actions"><button type="button" class="button secondary" data-close-modal="updateJobModal">Cancel</button><button type="submit">Save changes</button></div>
        </form>
    </div>
</div>
