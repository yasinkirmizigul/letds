import initPermissionsForm from './_permissions-form';
let ac = null;

export default function init({ root }) {
    initPermissionsForm(root);
    // checkboxları bulmak için helper
    const getChecks = (scope = root) =>
        Array.from(scope.querySelectorAll('.perm-check'));

    // tümünü seç
    root.querySelector('#perm_select_all')?.addEventListener('click', () => {
        getChecks().forEach(c => c.checked = true);
    });

    // tümünü temizle
    root.querySelector('#perm_clear_all')?.addEventListener('click', () => {
        getChecks().forEach(c => c.checked = false);
    });

    // grup bazlı seç
    root.querySelectorAll('[data-perm-group-select]').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.getAttribute('data-perm-group-select');
            const container = root.querySelector(`[data-perm-group="${group}"]`);
            if (!container) return;

            getChecks(container).forEach(c => c.checked = true);
        });
    });

    // grup bazlı temizle
    root.querySelectorAll('[data-perm-group-clear]').forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.getAttribute('data-perm-group-clear');
            const container = root.querySelector(`[data-perm-group="${group}"]`);
            if (!container) return;

            getChecks(container).forEach(c => c.checked = false);
        });
    });
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;
}
