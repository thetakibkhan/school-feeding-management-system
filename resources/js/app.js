import '../css/beams-background.css';
import '../css/auth.css';
import '../css/dashboard.css';
import '../css/table.css';
import '../css/modal.css';
import { mountBeamsBackground } from './beams-background';

const dashboardShell = document.querySelector('[data-dashboard-shell]');
if (dashboardShell) {
    const sidebar = dashboardShell.querySelector('[data-dashboard-sidebar]');
    const backdrop = dashboardShell.querySelector('[data-sidebar-backdrop]');
    const setMobileOpen = (open) => {
        sidebar?.classList.toggle('is-mobile-open', open);
        backdrop?.classList.toggle('is-visible', open);
    };
    dashboardShell.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => sidebar?.classList.toggle('is-collapsed'));
    dashboardShell.querySelectorAll('[data-sidebar-section]').forEach((section) => {
        section.addEventListener('click', () => {
            if (sidebar?.classList.contains('is-collapsed')) sidebar.classList.remove('is-collapsed');
            const name = section.dataset.sidebarSection;
            const submenu = dashboardShell.querySelector(`[data-sidebar-submenu="${name}"]`);
            const open = section.getAttribute('aria-expanded') === 'true';
            section.setAttribute('aria-expanded', String(!open));
            submenu?.classList.toggle('is-open', !open);
        });
    });
    dashboardShell.querySelectorAll('[data-sidebar-action]').forEach((action) => {
        action.addEventListener('click', () => {
            dashboardShell.querySelectorAll('[data-sidebar-action]').forEach((item) => item.classList.remove('is-active'));
            action.classList.add('is-active');
        });
    });
    dashboardShell.querySelector('[data-sidebar-open]')?.addEventListener('click', () => setMobileOpen(true));
    dashboardShell.querySelector('[data-sidebar-close]')?.addEventListener('click', () => setMobileOpen(false));
    backdrop?.addEventListener('click', () => setMobileOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMobileOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'b') {
            event.preventDefault();
            if (window.matchMedia('(max-width: 767px)').matches) setMobileOpen(!sidebar?.classList.contains('is-mobile-open'));
            else sidebar?.classList.toggle('is-collapsed');
        }
    });
}

document.querySelectorAll('[data-beams-background]').forEach((element) => {
    mountBeamsBackground(element, element.dataset.intensity || 'strong');
});

document.querySelectorAll('[data-editable-table]').forEach((table) => {
    const rows = [...table.querySelectorAll('[data-table-row]')];
    const search = table.querySelector('[data-table-search]');
    const empty = table.querySelector('[data-table-empty]');
    const count = table.querySelector('[data-table-visible-count]');
    const searchKeys = (table.dataset.tableSearchKeys || 'name,email,role,status').split(',');
    const render = () => {
        const query = search?.value.toLowerCase().trim() || '';
        let visible = 0;
        rows.forEach((row) => {
            const matches = !query || searchKeys.some((key) => row.dataset[key]?.includes(query));
            row.hidden = !matches;
            if (matches) visible += 1;
        });
        if (count) count.textContent = String(visible);
        if (empty) empty.hidden = visible !== 0;
    };
    search?.addEventListener('input', render);
    table.querySelector('[data-table-select-all]')?.addEventListener('change', (event) => {
        rows.forEach((row) => { if (!row.hidden) { const checkbox = row.querySelector('[data-table-row-select]'); checkbox.checked = event.target.checked; row.classList.toggle('is-selected', event.target.checked); } });
    });
    table.querySelectorAll('[data-table-row-select]').forEach((checkbox) => checkbox.addEventListener('change', (event) => event.target.closest('tr').classList.toggle('is-selected', event.target.checked)));
    table.querySelectorAll('[data-table-sort]').forEach((button) => button.addEventListener('click', () => {
        const key = button.dataset.tableSort;
        const body = table.querySelector('[data-table-body]');
        rows.sort((a, b) => (a.dataset[key] || '').localeCompare(b.dataset[key] || ''));
        rows.forEach((row) => body.appendChild(row));
    }));
});

document.querySelectorAll('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.querySelector(`[data-modal="${button.dataset.modalOpen}"]`);
        if (modal) { modal.hidden = false; modal.querySelector('input, select, button:not([data-modal-close])')?.focus(); }
    });
});
document.querySelectorAll('[data-edit-user]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.querySelector('[data-modal="edit-user-modal"]');
        const form = modal?.querySelector('[data-edit-user-form]');
        if (!modal || !form) return;
        form.action = button.dataset.action;
        form.querySelector('[name="name"]').value = button.dataset.name || '';
        form.querySelector('[name="email"]').value = button.dataset.email || '';
        form.querySelector('[name="role"]').value = button.dataset.role || 'field_staff';
        modal.hidden = false;
        form.querySelector('[name="name"]').focus();
    });
});
document.querySelectorAll('[data-edit-school]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.querySelector('[data-modal="edit-school-modal"]');
        const form = modal?.querySelector('[data-edit-school-form]');
        if (!modal || !form) return;
        form.action = button.dataset.action;
        form.querySelector('[name="name"]').value = button.dataset.name || '';
        form.querySelector('[name="school_code"]').value = button.dataset.schoolCode || '';
        form.querySelector('[name="emis_code"]').value = button.dataset.emisCode || '';
        modal.hidden = false;
        form.querySelector('[name="name"]').focus();
    });
});
document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => { button.closest('[data-modal]')?.setAttribute('hidden', ''); });
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') document.querySelectorAll('[data-modal]:not([hidden])').forEach((modal) => modal.setAttribute('hidden', ''));
});
