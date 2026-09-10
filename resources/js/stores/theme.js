export default {
    dark: false,

    init() {
        this.dark = document.documentElement.classList.contains('dark');
    },

    toggle() {
        this.dark = ! this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        this.remember(this.dark ? 'dark' : 'light');
    },

    remember(choice) {
        try {
            localStorage.setItem('theme', choice);
        } catch {
            return false;
        }

        return true;
    },
};
