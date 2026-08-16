import { showConfirmDialog } from '@/core/swal-alert';

export default function init(ctx) {
    const root = ctx?.root || document;

    root.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.matches('form[data-demo-action]')) return;
        if (form.dataset.confirmed === 'true') return;

        event.preventDefault();

        const isReset = form.dataset.demoAction === 'reset';
        const confirmed = await showConfirmDialog({
            type: isReset ? 'warning' : 'info',
            title: isReset ? 'Operasyon verileri sıfırlansın mı?' : 'Örnek veri seti oluşturulsun mu?',
            message: isReset
                ? 'Blog, proje, ürün, medya, üye, mesaj, randevu, sipariş ve CMS kayıtları kalıcı olarak silinecek. Yönetici hesapları ve sistem yapılandırması korunacak.'
                : 'Tüm ana modüllere birbiriyle ilişkili yeni örnek kayıtlar eklenecek. Mevcut veriler değiştirilmeyecek.',
            confirmButtonText: isReset ? 'Verileri Sıfırla' : 'Örnek Verileri Üret',
            cancelButtonText: 'Vazgeç',
            confirmationText: isReset ? 'SIFIRLA' : undefined,
            inputPlaceholder: 'SIFIRLA',
            inputAriaLabel: 'Sıfırlama işlemini onaylamak için SIFIRLA yazın',
            allowEscapeKey: !isReset,
        });

        if (!confirmed) return;

        form.dataset.confirmed = 'true';
        form.requestSubmit();
    }, { signal: ctx.signal });
}
