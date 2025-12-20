export default {
    init() {
        // Delegation: modal içinden data-media-id yakala
        document.addEventListener('click', (e) => {
            const el = e.target.closest('[data-media-id]');
            if (!el) return;

            const mediaId = el.getAttribute('data-media-id');
            if (!mediaId) return;

            const input = document.getElementById('avatar_media_id');
            const form = document.getElementById('avatarForm');

            if (!input || !form) return;

            // Dosya seçiliyse temizle (çakışmayı engelle)
            const fileInput = form.querySelector('input[type="file"][name="avatar"]');
            if (fileInput && fileInput.files && fileInput.files.length) {
                fileInput.value = '';
            }

            input.value = mediaId;
            form.submit();
        });
    }
};
