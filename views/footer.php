<?php
// Shared workstation footer and interaction scripts.
declare(strict_types=1);
?>
        </main>
    </div>

    <!-- Master Workstation Dynamic Modal & State Controller -->
    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'flex';
                document.body.classList.add('modal-open');
                const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 60);
                }
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'none';
            }
            if (!document.querySelector('.modal[style*="display: flex"]')) {
                document.body.classList.remove('modal-open');
            }
        }

        // Global Backdrop Click Dismiss
        window.addEventListener('click', function(event) {
            if (event.target && event.target.classList.contains('modal')) {
                closeModal(event.target.id);
            }
        });

        // Global Escape Key Dismiss
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(function(modal) {
                    if (modal.style.display === 'flex') {
                        closeModal(modal.id);
                    }
                });
            }
        });

        // Dynamic Equipment Filter based on Customer Selection
        function filterEquipmentByCustomer(customerSelectId, equipmentSelectId) {
            const custSelect = document.getElementById(customerSelectId);
            const equipSelect = document.getElementById(equipmentSelectId);
            if (!custSelect || !equipSelect) return;

            const selectedCustId = custSelect.value;
            const options = equipSelect.querySelectorAll('option[data-customer-id]');

            let matchCount = 0;
            options.forEach(opt => {
                if (!selectedCustId || opt.getAttribute('data-customer-id') === selectedCustId) {
                    opt.style.display = '';
                    matchCount++;
                } else {
                    opt.style.display = 'none';
                }
            });

            // If selected equipment does not belong to new customer, reset it
            const currentSelected = equipSelect.selectedOptions[0];
            if (currentSelected && currentSelected.getAttribute('data-customer-id') && currentSelected.getAttribute('data-customer-id') !== selectedCustId) {
                equipSelect.value = '';
            }
        }

        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const mobileSidebarToggle = document.querySelector('.mobile-sidebar-toggle');
        const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
        const mobileSidebarQuery = window.matchMedia('(max-width: 640px)');
        if (sidebar && sidebarToggle) {
            const savedCollapse = localStorage.getItem('fieldSyncSidebarCollapsed');
            if (savedCollapse === 'true') sidebar.classList.add('is-collapsed');
            const syncSidebarToggle = () => {
                const expanded = !sidebar.classList.contains('is-collapsed');
                sidebarToggle.setAttribute('aria-expanded', String(expanded));
                sidebarToggle.setAttribute('aria-label', expanded ? 'Collapse navigation' : 'Expand navigation');
                sidebarToggle.title = expanded ? 'Collapse navigation' : 'Expand navigation';
                sidebarToggle.querySelector('path').setAttribute(
                    'd',
                    expanded ? 'm14 6-6 6 6 6' : 'm10 6 6 6-6 6'
                );
            };
            syncSidebarToggle();
            sidebarToggle.addEventListener('click', function() {
                if (mobileSidebarQuery.matches) return;
                sidebar.classList.toggle('is-collapsed');
                localStorage.setItem('fieldSyncSidebarCollapsed', String(sidebar.classList.contains('is-collapsed')));
                syncSidebarToggle();
            });

            const setMobileSidebar = (open) => {
                document.body.classList.toggle('sidebar-open', open);
                sidebar.inert = mobileSidebarQuery.matches && !open;
                if (mainContent) mainContent.inert = mobileSidebarQuery.matches && open;
                if (mobileSidebarToggle) {
                    mobileSidebarToggle.setAttribute('aria-expanded', String(open));
                    mobileSidebarToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
                }
                if (sidebarBackdrop) sidebarBackdrop.tabIndex = open ? 0 : -1;
                if (open) sidebar.querySelector('.nav-link.active')?.focus({ preventScroll: true });
            };

            mobileSidebarToggle?.addEventListener('click', () => setMobileSidebar(true));
            sidebarBackdrop?.addEventListener('click', () => {
                setMobileSidebar(false);
                mobileSidebarToggle?.focus();
            });
            sidebar.querySelectorAll('.nav-link').forEach((link) => {
                link.addEventListener('click', () => {
                    if (mobileSidebarQuery.matches) setMobileSidebar(false);
                });
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                    setMobileSidebar(false);
                    mobileSidebarToggle?.focus();
                }
            });
            mobileSidebarQuery.addEventListener('change', (event) => {
                setMobileSidebar(false);
                sidebar.inert = event.matches;
                if (mainContent) mainContent.inert = false;
            });
            sidebar.inert = mobileSidebarQuery.matches;
            if (mainContent) mainContent.inert = false;
        }
    </script>
</body>
</html>