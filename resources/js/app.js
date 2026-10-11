const root = document.documentElement;

if (root.dataset.designSystem === 'experimental') {
    const storageKey = 'jogo-da-velha.theme';
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
    const validPreferences = ['auto', 'light', 'dark'];

    const readPreference = () => {
        try {
            const stored = window.localStorage.getItem(storageKey);

            return validPreferences.includes(stored) ? stored : 'auto';
        } catch {
            return 'auto';
        }
    };

    let preference = readPreference();

    const syncThemeSelectors = () => {
        document.querySelectorAll('select[data-theme-selector]').forEach((selector) => {
            selector.value = preference;
            selector.hidden = false;
        });
    };

    const effectiveTheme = () => preference === 'auto'
        ? (systemTheme.matches ? 'dark' : 'light')
        : preference;

    const applyTheme = () => {
        root.dataset.themePreference = preference;
        root.dataset.theme = effectiveTheme();
        root.dispatchEvent(new CustomEvent('jogo-da-velha:theme-change', {
            detail: { preference, theme: root.dataset.theme },
        }));
    };

    systemTheme.addEventListener('change', () => {
        if (preference === 'auto') {
            applyTheme();
        }
    });

    root.addEventListener('jogo-da-velha:theme-change', syncThemeSelectors);

    document.addEventListener('change', (event) => {
        if (event.target instanceof HTMLSelectElement && event.target.matches('[data-theme-selector]')) {
            window.JogoDaVelhaTheme.setPreference(event.target.value);
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncThemeSelectors, { once: true });
    } else {
        syncThemeSelectors();
    }

    window.JogoDaVelhaTheme = Object.freeze({
        getPreference: () => preference,
        getEffectiveTheme: () => effectiveTheme(),
        setPreference: (nextPreference) => {
            if (!validPreferences.includes(nextPreference)) {
                throw new TypeError('Invalid theme preference');
            }

            preference = nextPreference;

            try {
                if (preference === 'auto') {
                    window.localStorage.removeItem(storageKey);
                } else {
                    window.localStorage.setItem(storageKey, preference);
                }
            } catch {
                // The selected theme still works for this page when storage is unavailable.
            }

            applyTheme();
        },
    });

    applyTheme();
}
