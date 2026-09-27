<script>
    (() => {
        try {
            const savedTheme = localStorage.getItem('prottyashi-theme');
            const theme = savedTheme === 'dark' || savedTheme === 'light'
                ? savedTheme
                : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            document.documentElement.dataset.theme = theme;
            document.documentElement.classList.toggle('dark', theme === 'dark');
        } catch {
            document.documentElement.dataset.theme = 'light';
        }
    })();
</script>
<button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode" title="Switch to dark mode">
    <span data-theme-icon aria-hidden="true">☾</span>
</button>
