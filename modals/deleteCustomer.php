<div class="modal-backdrop" id="deleteCustomerModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteCustomerTitle">
        <h2 id="deleteCustomerTitle">Delete customer?</h2>
        <p>A customer linked to a job cannot be deleted until those job records are reassigned or removed.</p>
        <form method="post"><input type="hidden" name="action" value="delete_customer"><input type="hidden" name="customer_id" id="delete_customer_id">
            <div class="modal-actions"><button type="button" class="button secondary" data-close-modal="deleteCustomerModal">Keep customer</button><button type="submit" class="button danger">Delete customer</button></div>
        </form>
    </div>
</div>
