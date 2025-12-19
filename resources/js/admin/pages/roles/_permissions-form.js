export default function initPermissionsForm(root) {
    const getChecks = (scope = root) =>
        Array.from(scope.querySelectorAll('.perm-check'));

    root.querySelector('#perm_select_all')?.addEventListener('click', () => {
        getChecks().forEach(c => c.checked = true);
    });

    root.querySelector('#perm_clear_all')?.addEventListener('click', () => {
        getChecks().forEach(c => c.checked = false);
    });
}
