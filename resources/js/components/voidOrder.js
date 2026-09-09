export default (orderId) => ({
    open: false,
    reason: '',
    working: false,

    async confirm() {
        this.working = true;

        try {
            const response = await fetch(`/api/orders/${orderId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ reason: this.reason || null }),
            });

            if (response.status === 204) {
                window.location.href = `/orders/${orderId}?voided=1`;

                return;
            }

            const payload = await response.json().catch(() => ({}));
            this.$store.toasts.error('Could not void this bill', payload.message ?? 'Please try again.');
        } catch {
            this.$store.toasts.error('Could not reach the server', 'Check the connection and try again.');
        } finally {
            this.working = false;
            this.open = false;
        }
    },
});
