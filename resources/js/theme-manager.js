(() => {
    const THEMES = ['midnight', 'mint', 'dawn', 'cobalt', 'graphite', 'sapphire'];
    
    // По умолчанию - Полночь
    const getDefaultTheme = () => 'midnight';

    const applyTheme = (theme) => {
        if (!THEMES.includes(theme)) theme = getDefaultTheme();
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('user-theme', theme);
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
    };

    const savedTheme = localStorage.getItem('user-theme');
    const initialTheme = THEMES.includes(savedTheme) ? savedTheme : getDefaultTheme();
    
    applyTheme(initialTheme);
    
    window.ThemeManager = {
        setTheme: applyTheme,
        getThemes: () => THEMES,
        getCurrent: () => document.documentElement.getAttribute('data-theme')
    };
})();
