export default function init(ctx) {
    const root = ctx.root;
    const signal = ctx.signal;

    const modal = document.getElementById('roleDeleteModal');
    if (!modal) return;

    const alertWrap = document.getElementById('roleDeleteAlert');
    const nameEl = document.getElementById('roleDeleteName');
    const usersEl = document.getElementById('roleDeleteUsers');
    const usersWrap = document.getElementById('roleDeleteUsersWrap');
    const confirmBtn = document.getElementById('roleDeleteConfirmBtn');

    let currentRoleId = null;
    let delayTimer = null;
    let countdownTimer = null;

    function resetTimers() {
        if (delayTimer) {
            clearTimeout(delayTimer);
            delayTimer = null;
        }
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function setDangerMode(isDanger) {
        if (alertWrap) alertWrap.classList.toggle('text-danger', isDanger);

        if (usersWrap) {
            usersWrap.classList.toggle('text-danger', isDanger);
            usersWrap.classList.toggle('font-medium', isDanger);
        }

        if (usersEl) {
            usersEl.classList.toggle('text-danger', isDanger);
            usersEl.classList.toggle('font-bold', isDanger);
        }
    }

    function enableConfirm() {
        if (!confirmBtn) return;
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Sil';
    }

    function disableConfirm(seconds) {
        if (!confirmBtn) return;

        confirmBtn.disabled = true;
        confirmBtn.textContent = `Sil (${seconds})`;

        let remaining = seconds;

        countdownTimer = setInterval(() => {
            remaining--;
            if (remaining <= 0) {
                resetTimers();
                enableConfirm();
            } else {
                confirmBtn.textContent = `Sil (${remaining})`;
            }
        }, 1000);
    }

    // ✅ daha önce global listener idi, şimdi ctx.signal ile cleanup garanti
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-kt-modal-toggle="#roleDeleteModal"]');
        if (!btn) return;

        resetTimers();
        enableConfirm();

        currentRoleId = btn.getAttribute('data-role-id');

        const roleName = btn.getAttribute('data-role-name') || '';
        const usersCountRaw = btn.getAttribute('data-role-users') || '0';
        const usersCount = Number(usersCountRaw) || 0;

        if (nameEl) nameEl.textContent = roleName;
        if (usersEl) usersEl.textContent = String(usersCount);

        const isDanger = usersCount > 0;
        setDangerMode(isDanger);

        // panic click engelleme
        if (isDanger) {
            disableConfirm(2);
        }
    }, { signal });

    confirmBtn?.addEventListener('click', function () {
        if (confirmBtn.disabled) return;
        if (!currentRoleId) return;

        const form = document.getElementById('role_delete_form_' + currentRoleId);
        if (form) form.submit();
    }, { signal });

    // Modal kapanınca state temizle (signal)
    modal.addEventListener('hidden', resetTimers, { signal });

    // ----- DataTable
    const table = root.querySelector('#roles_table');
    if (!table) return;

    window.initDataTable?.({
        root,
        table: '#roles_table',
        search: '#rolesSearch',
        pageSize: '#rolesPageSize',
        info: '#rolesInfo',
        pagination: '#rolesPagination',

        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[0, 'asc']],
        dom: 't',

        emptyTemplate: '#dt-empty-roles',
        zeroTemplate: '#dt-zero-roles',

        columnDefs: [
            { orderable: false, searchable: false, targets: [3] },
            { className: 'text-right', targets: [3] },
            { className: 'text-center', targets: [2] },
        ],

        // ✅ yeni standart
        signal,
        cleanup: (fn) => ctx.cleanup(fn),
    });
}
