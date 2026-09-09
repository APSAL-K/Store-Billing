export default () => ({
    open: false,
    target: null,
    working: false,

    confirm(order) {
        this.target = order;
        this.open = true;
    },

    async run() {
        this.working = true;

        try {
            const response = await fetch(`/api/orders/${this.target.id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (response.status === 204) {
                window.location.href = this.target.redirect ?? window.location.pathname;

                return;
            }

            const payload = await response.json().catch(() => ({}));
            this.$store.toasts.error('Could not delete this bill', payload.message ?? 'Please try again.');
        } catch {
            this.$store.toasts.error('Could not reach the server', 'Check the connection and try again.');
        } finally {
            this.working = false;
            this.open = false;
        }
    },
});
