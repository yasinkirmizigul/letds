import { showConfirmDialog } from '@/core/swal-alert';

document.querySelectorAll('[data-project-file-delete]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const confirmed = await showConfirmDialog({
            type: 'warning',
            title: 'Dosya silinsin mi?',
            message: 'Bu dosya proje alanından kaldırılacak.',
            confirmButtonText: 'Dosyayı sil',
        });

        if (confirmed) form.submit();
    });
});

const fileInput = document.querySelector('[data-project-files]');
const fileSelection = document.querySelector('[data-file-selection]');

fileInput?.addEventListener('change', () => {
    const count = fileInput.files?.length ?? 0;
    if (fileSelection) fileSelection.textContent = count ? `${count} dosya seçildi` : 'Dosya seçilmedi';
});

const terminationForm = document.querySelector('[data-member-termination-form]');
terminationForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const confirmed = await showConfirmDialog({
        type: 'warning',
        title: 'Üyeliğiniz pasife alınsın mı?',
        message: 'Bu işlemden sonra hesabınıza giriş yapamazsınız. Geçmiş operasyon kayıtları korunur.',
        confirmButtonText: 'Üyeliği sonlandır',
    });

    if (confirmed) terminationForm.submit();
});
