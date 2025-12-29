export default function init() {
    const root = document.querySelector('[data-page="galleries.create"]');
    if (!root) return;

    const name = root.querySelector('input[name="name"]');
    const slug = root.querySelector('input[name="slug"]');
    if (!name || !slug) return;

    function slugify(s) {
        return String(s || '')
            .trim()
            .toLowerCase()
            .replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's')
            .replace(/ı/g, 'i').replace(/ö/g, 'o').replace(/ç/g, 'c')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    name.addEventListener('input', () => {
        if (slug.value.trim()) return; // kullanıcı elle yazdıysa dokunma
        slug.value = slugify(name.value);
    });
}
