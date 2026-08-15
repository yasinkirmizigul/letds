import { showConfirmDialog } from '@/core/swal-alert';

export default function init(ctx) {
    ctx.root.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.matches('form[data-confirm="clear-audit-logs"]')) return;

        event.preventDefault();

        const confirmed = await showConfirmDialog({
            type: 'warning',
            title: 'Tüm loglar temizlensin mi?',
            message: 'Mevcut sistem ve kullanıcı kayıtları kalıcı olarak silinecek. Bu işlem geri alınamaz.',
            confirmButtonText: 'Logları Temizle',
            cancelButtonText: 'Vazgeç',
        });

        if (confirmed) form.submit();
    }, { signal: ctx.signal });
}
