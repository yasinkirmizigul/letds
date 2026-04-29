export default {
    init() {
        if (window.__letdsProfileEditBound) return;
        window.__letdsProfileEditBound = true;

        document.addEventListener('click', (event) => {
            const item = event.target.closest('[data-media-id]');
            if (!item) return;

            const mediaId = item.getAttribute('data-media-id');
            const input = document.getElementById('avatar_media_id');
            const form = document.getElementById('avatarForm');

            if (!mediaId || !input || !form) return;

            const fileInput = form.querySelector('input[type="file"][name="avatar_file"]');
            if (fileInput?.files?.length) {
                fileInput.value = '';
            }

            input.value = mediaId;
        });
    }
};
