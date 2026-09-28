document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    const button = event.target.closest('[data-password-toggle]');
    if (!(button instanceof HTMLButtonElement)) return;

    const inputId = button.getAttribute('aria-controls');
    const input = inputId ? document.getElementById(inputId) : null;
    if (!(input instanceof HTMLInputElement)) return;

    const showingPassword = input.type === 'password';
    input.type = showingPassword ? 'text' : 'password';
    button.textContent = showingPassword ? 'Hide' : 'Show';
    button.setAttribute('aria-label', `${showingPassword ? 'Hide' : 'Show'} password`);
    button.setAttribute('aria-pressed', String(showingPassword));
});

const themeToggleButtons = document.querySelectorAll('[data-theme-toggle]');
const setTheme = (theme, persist = false) => {
    document.documentElement.dataset.theme = theme;
    document.documentElement.classList.toggle('dark', theme === 'dark');

    themeToggleButtons.forEach((button) => {
        const nextTheme = theme === 'dark' ? 'light' : 'dark';
        const label = `Switch to ${nextTheme} mode`;
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        const icon = button.querySelector('[data-theme-icon]');
        if (icon) icon.textContent = nextTheme === 'dark' ? '☾' : '◐';
    });

    if (persist) {
        try {
            localStorage.setItem('prottyashi-theme', theme);
        } catch {
            // Keep the selected theme active for the current page if storage is unavailable.
        }
    }
};

const currentTheme = document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light';
setTheme(currentTheme);
themeToggleButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        setTheme(nextTheme, true);
    });
});

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
        form.querySelector('[name="principal_name"]').value = button.dataset.principalName || '';
        form.querySelector('[name="principal_mobile"]').value = button.dataset.principalMobile || '';
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

document.querySelectorAll('[data-photo-modal]').forEach((modal) => {
    const image = modal.querySelector('[data-photo-modal-image]');
    const error = modal.querySelector('[data-photo-modal-error]');

    document.querySelectorAll('[data-photo-open]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            if (!(image instanceof HTMLImageElement) || !(modal instanceof HTMLDialogElement)) return;

            error.hidden = true;
            image.hidden = false;
            image.src = link.href;
            modal.showModal();
        });
    });

    image?.addEventListener('error', () => {
        image.hidden = true;
        error.hidden = false;
    });
    modal.querySelector('[data-photo-close]')?.addEventListener('click', () => modal.close());
    modal.addEventListener('click', (event) => {
        if (event.target === modal) modal.close();
    });
    modal.addEventListener('close', () => {
        if (image instanceof HTMLImageElement) image.removeAttribute('src');
    });
});

document.querySelector('[data-print-report]')?.addEventListener('click', () => window.print());
