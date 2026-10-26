<div class="modal-backdrop" id="deleteJobModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="deleteJobTitle">
        <h2 id="deleteJobTitle">Delete job ticket?</h2>
        <p>This will permanently remove the selected dispatch record.</p>
        <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="job_id" id="delete_job_id">
            <div class="modal-actions"><button type="button" class="button secondary" data-close-modal="deleteJobModal">Keep ticket</button><button type="submit" class="button danger">Delete ticket</button></div>
        </form>
    </div>
</div>
