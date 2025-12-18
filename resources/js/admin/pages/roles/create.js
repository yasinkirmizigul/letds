let ac = null;

export default function init({ root }) {
    ac = new AbortController();
    const { signal } = ac;

    const allBtn = root.querySelector('#perm_select_all');
    const clearBtn = root.querySelector('#perm_clear_all');

    const setAll = (checked) => {
        root.querySelectorAll('.perm-check').forEach((c) => {
            if (c instanceof HTMLInputElement) c.checked = checked;
        });
    };

    allBtn?.addEventListener('click', () => setAll(true), { signal });
    clearBtn?.addEventListener('click', () => setAll(false), { signal });
}

export function destroy() {
    try { ac?.abort(); } catch {}
    ac = null;
}
