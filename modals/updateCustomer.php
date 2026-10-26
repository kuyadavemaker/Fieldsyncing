<div class="modal-backdrop" id="updateCustomerModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="updateCustomerTitle">
        <h2 id="updateCustomerTitle">Edit customer</h2>
        <p>Keep the customer name current for dispatch records.</p>
        <form method="post">
            <input type="hidden" name="action" value="update_customer"><input type="hidden" name="customer_id" id="update_customer_id">
            <div class="field"><label for="update_customer_name">Full name</label><input id="update_customer_name" type="text" name="full_name" maxlength="150" required></div>
            <div class="modal-actions"><button type="button" class="button secondary" data-close-modal="updateCustomerModal">Cancel</button><button type="submit">Save changes</button></div>
        </form>
    </div>
</div>
