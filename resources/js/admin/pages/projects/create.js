function slugify(s) {
    return (s || '')
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}

function setPreview(root, val) {
    const prev = root.querySelector('#projectSlugPreview');
    if (prev) prev.textContent = val || '';
}

export default function init() {
    const root = document.querySelector('[data-page="projects.create"]');
    if (!root) return;

    const title = root.querySelector('#projectTitle');
    const slug = root.querySelector('#projectSlug');
    const genBtn = root.querySelector('#projectSlugGenBtn');

    const applySlugFromTitle = () => {
        if (!title || !slug) return;
        const v = slugify(title.value);
        slug.value = v;
        setPreview(root, v);
    };

    if (slug) {
        setPreview(root, slug.value);
        slug.addEventListener('input', () => setPreview(root, slug.value.trim()));
    }

    if (genBtn) genBtn.addEventListener('click', applySlugFromTitle);

    // Blog’daki davranış: slug boşsa title blur’da üret
    if (title && slug) {
        title.addEventListener('blur', () => {
            if (slug.value.trim() !== '') return;
            applySlugFromTitle();
        });
    }

    // TinyMCE varsa devreye girsin
    if (window.tinymce && typeof window.tinymce.init === 'function') {
        try {
            window.tinymce.init({ selector: '#projectContent' });
        } catch (e) {}
    }

    // featured clear
    const clearBtn = root.querySelector('#projectFeaturedClearBtn');
    const hid = root.querySelector('#projectFeaturedMediaId');
    const img = root.querySelector('#projectFeaturedPreview');

    if (clearBtn && hid) {
        clearBtn.addEventListener('click', () => {
            hid.value = '';
            if (img) {
                img.src = '';
                img.classList.add('hidden');
            }
        });
    }
}
