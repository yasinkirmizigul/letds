<script>
    (() => {
        const root = document.documentElement;
        const storageKey = 'probablue-site-theme';
        const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');
        const allowedPreferences = new Set(['light', 'dark', 'system']);

        const readPreference = () => {
            try {
                const storedPreference = window.localStorage.getItem(storageKey);

                return allowedPreferences.has(storedPreference) ? storedPreference : 'system';
            } catch {
                return 'system';
            }
        };

        const resolvedTheme = (preference) => (
            preference === 'system'
                ? (colorScheme.matches ? 'dark' : 'light')
                : preference
        );

        const syncToggle = (toggle, theme) => {
            const nextTheme = theme === 'dark' ? 'light' : 'dark';
            const label = nextTheme === 'dark' ? 'Koyu temaya geç' : 'Açık temaya geç';

            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            toggle.setAttribute('title', label);
        };

        const applyTheme = (preference, persist = false) => {
            const safePreference = allowedPreferences.has(preference) ? preference : 'system';
            const theme = resolvedTheme(safePreference);

            root.classList.toggle('dark', theme === 'dark');
            root.dataset.siteTheme = theme;
            root.dataset.siteThemePreference = safePreference;
            root.dataset.ktThemeMode = theme;
            root.style.colorScheme = theme;

            if (persist) {
                try {
                    window.localStorage.setItem(storageKey, safePreference);
                } catch {
                    // The active theme still works when storage is unavailable.
                }
            }

            document.querySelectorAll('[data-site-theme-toggle]').forEach((toggle) => syncToggle(toggle, theme));
            window.dispatchEvent(new CustomEvent('site:themechange', { detail: { preference: safePreference, theme } }));

            return theme;
        };

        const toggleTheme = () => {
            const currentTheme = root.dataset.siteTheme || resolvedTheme(readPreference());

            return applyTheme(currentTheme === 'dark' ? 'light' : 'dark', true);
        };

        window.SiteTheme = Object.freeze({
            apply: (preference) => applyTheme(preference, true),
            current: () => root.dataset.siteTheme,
            toggle: toggleTheme,
        });

        applyTheme(readPreference());

        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(root.dataset.siteThemePreference || readPreference());

            document.addEventListener('click', (event) => {
                const toggle = event.target instanceof Element
                    ? event.target.closest('[data-site-theme-toggle]')
                    : null;

                if (toggle) toggleTheme();
            });
        }, { once: true });

        colorScheme.addEventListener?.('change', () => {
            if (root.dataset.siteThemePreference === 'system') applyTheme('system');
        });
    })();
</script>
