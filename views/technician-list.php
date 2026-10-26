<?php
// Technician registry view.
declare(strict_types=1);

$totalDispatches = array_sum(array_column($technicianRows, 'assigned_jobs'));
?>

<!-- Technician Telemetry Metrics -->
<section class="stats-grid" aria-label="Technician telemetry">
    <div class="stat-card stat-cyan">
        <div class="stat-label">Field Technicians <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m1-13h5m-2.5-2.5v5"/></svg></div>
        <div class="stat-value"><?= count($technicianRows) ?></div>
        <div class="stat-subtext">Active field personnel</div>
    </div>
    <div class="stat-card stat-emerald">
        <div class="stat-label">Catalog Skills <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.3 5.8L20 11l-5.7 2.2L12 19l-2.3-5.8L4 11l5.7-2.2L12 3Z"/></svg></div>
        <div class="stat-value"><?= count($skillRows) ?></div>
        <div class="stat-subtext">Certified technical domains</div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-label">Active Dispatches <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7z"/></svg></div>
        <div class="stat-value"><?= (int)$totalDispatches ?></div>
        <div class="stat-subtext">Cumulative technician assignments</div>
    </div>
</section>

<!-- Action Toolbar -->
<div class="toolbar-container">
    <div style="flex: 1;">
        <span style="font-size: 13px; color: var(--text-muted); font-family: var(--font-mono);">
            Field Force: <?= count($technicianRows) ?> Certified Operators
        </span>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('skillModal')">+ Assign Certification</button>
</div>

<!-- Technicians Grid -->
<section class="entity-grid">
    <?php if (empty($technicianRows)): ?>
        <div style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; color: var(--text-muted); background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl);">
            <svg class="empty-state-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m1-13h5m-2.5-2.5v5"/></svg>
            <strong style="color: var(--text-secondary); display: block; font-size: 15px;">No technician profiles active</strong>
            <p style="font-size: 13px; margin-top: 6px;">Register field specialists and assign certifications to enable work order dispatching.</p>
        </div>
    <?php else: ?>
        <?php foreach ($technicianRows as $t): ?>
            <article class="entity-card">
                <div>
                    <div class="entity-card-top">
                        <div class="entity-icon icon-emerald"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.3 5.8L20 11l-5.7 2.2L12 19l-2.3-5.8L4 11l5.7-2.2L12 3Z"/></svg></div>
                        <div class="entity-info">
                            <h3><?= htmlspecialchars($t['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p style="color: var(--color-cyan); font-family: var(--font-mono); font-size: 12px;">
                                License: <?= htmlspecialchars($t['license_number'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p style="font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);">
                                <svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h4l2 5-3 2a15 15 0 0 0 5 5l2-3 5 2v4c0 1.1-.9 2-2 2C10 20 4 14 4 5c0-1.1.9-2 2-2Z"/></svg> <?= htmlspecialchars($t['phone'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p style="margin-top: 6px; font-size: 12.5px;">
                                <svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5L2 9Z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5m4-2v6"/></svg> <strong>Certifications:</strong>
                                <?= htmlspecialchars($t['skills'] ?: 'No certifications logged', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    </div>

                    <div class="entity-tags">
                        <span class="tag-pill"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7z"/></svg> <?= (int)$t['assigned_jobs'] ?> Assigned Tickets</span>
                        <span class="tag-pill"><svg class="inline-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg> Hired: <?= htmlspecialchars(!empty($t['hire_date']) ? date('M Y', strtotime($t['hire_date'])) : 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <div class="action-matrix" style="border-top: 1px solid var(--border-subtle); padding-top: 14px;">
                    <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        onclick="editTechnician(<?= htmlspecialchars(json_encode($t), ENT_QUOTES, 'UTF-8') ?>)"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 5 5 5M4 20l4.5-1 11-11a2.1 2.1 0 0 0-3-3l-11 11L4 20Z"/></svg> Edit
                    </button>
                    <button
                        type="button"
                        class="btn btn-cyan-subtle btn-sm"
                        onclick="openSkillAssignment(<?= (int)$t['technician_id'] ?>)"
                    >
                        + Certify
                    </button>
                    <form method="POST" action="index.php?page=technicians" onsubmit="return confirm('Delete technician \'<?= htmlspecialchars(addslashes($t['full_name']), ENT_QUOTES, 'UTF-8') ?>\'?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete_technician">
                        <input type="hidden" name="technician_id" value="<?= (int)$t['technician_id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Technician" aria-label="Delete technician"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 0V4h4v3m4 0-1 14H7L6 7m3 4v6m6-6v6"/></svg></button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Add Technician Modal -->
<div id="technicianModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="add-tech-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="add-tech-title">Register Field Technician</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('technicianModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=technicians">
            <input type="hidden" name="action" value="create_technician">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="create_tech_name">Full Name</label>
                <input type="text" name="full_name" id="create_tech_name" class="form-input" placeholder="e.g. Carlos Ramos" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="create_tech_license">License / Cert ID</label>
                    <input type="text" name="license_number" id="create_tech_license" class="form-input" placeholder="e.g. HVAC-LIC-8821" required>
                </div>
                <div class="form-group">
                    <label for="create_tech_phone">Direct Phone</label>
                    <input type="text" name="phone" id="create_tech_phone" class="form-input" placeholder="+1 (555) 000-0000" required>
                </div>
            </div>

            <div class="form-group">
                <label for="create_tech_hire_date">Date of Hire</label>
                <input type="date" name="hire_date" id="create_tech_hire_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('technicianModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Technician</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Technician Modal -->
<div id="editTechnicianModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="edit-tech-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="edit-tech-title">Edit Technician Credentials</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('editTechnicianModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=technicians">
            <input type="hidden" name="action" value="update_technician">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="technician_id" id="edit_tech_id">

            <div class="form-group">
                <label for="edit_tech_name">Full Name</label>
                <input type="text" name="full_name" id="edit_tech_name" class="form-input" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="edit_tech_license">License Number</label>
                    <input type="text" name="license_number" id="edit_tech_license" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="edit_tech_phone">Phone</label>
                    <input type="text" name="phone" id="edit_tech_phone" class="form-input" required>
                </div>
            </div>

            <div class="form-group">
                <label for="edit_tech_hire_date">Date of Hire</label>
                <input type="date" name="hire_date" id="edit_tech_hire_date" class="form-input" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editTechnicianModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </div>
        </form>
    </div>
</div>

<!-- Assign Skill Certification Modal -->
<div id="skillModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="assign-skill-title">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="assign-skill-title">Log Technical Certification</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('skillModal')">&times;</button>
        </div>
        <form method="POST" action="index.php?page=technicians">
            <input type="hidden" name="action" value="assign_skill">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="skill_technician_id">Field Technician</label>
                <select name="technician_id" id="skill_technician_id" class="form-select" required>
                    <option value="">-- Choose Field Specialist --</option>
                    <?php foreach ($technicianRows as $t): ?>
                        <option value="<?= (int)$t['technician_id'] ?>"><?= htmlspecialchars($t['full_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="skill_id">Technical Skill Domain</label>
                <select name="skill_id" id="skill_id" class="form-select" required>
                    <option value="">-- Select Certified Skill --</option>
                    <?php foreach ($skillRows as $s): ?>
                        <option value="<?= (int)$s['skill_id'] ?>"><?= htmlspecialchars($s['skill_name'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="skill_certified_date">Certification Date</label>
                <input type="date" name="certified_date" id="skill_certified_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('skillModal')">Cancel</button>
                <button type="submit" class="btn btn-emerald">Assign Certification</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editTechnician(item) {
        document.getElementById('edit_tech_id').value = item.technician_id;
        document.getElementById('edit_tech_name').value = item.full_name || '';
        document.getElementById('edit_tech_license').value = item.license_number || '';
        document.getElementById('edit_tech_phone').value = item.phone || '';
        document.getElementById('edit_tech_hire_date').value = item.hire_date || '';
        openModal('editTechnicianModal');
    }

    function openSkillAssignment(techId) {
        document.getElementById('skill_technician_id').value = techId;
        openModal('skillModal');
    }
</script>
