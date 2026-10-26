<div class="modal-backdrop" id="addCustomerModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="addCustomerTitle">
        <h2 id="addCustomerTitle">Add customer</h2>
        <p>Create a customer record for future dispatches.</p>
        <form method="post">
            <input type="hidden" name="action" value="add_customer">
            <div class="field"><label for="add_customer_name">Full name</label><input id="add_customer_name" type="text" name="full_name" maxlength="150" required></div>
            <div class="modal-actions"><button type="button" class="button secondary" data-close-modal="addCustomerModal">Cancel</button><button type="submit">Add customer</button></div>
        </form>
    </div>
</div>
