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
