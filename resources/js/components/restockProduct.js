export default (productId) => ({
    open: false,
    quantity: '',
    note: '',
    error: '',
    working: false,

    async confirm() {
        this.error = '';

        const quantity = Math.trunc(Number(this.quantity));

        if (!Number.isFinite(quantity) || quantity < 1) {
            this.error = 'Enter at least one unit.';

            return;
        }

        this.working = true;

        try {
            const response = await fetch(`/api/products/${productId}/restock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ quantity, note: this.note || null }),
            });

            const payload = await response.json();

            if (!response.ok) {
                this.error = payload.errors?.quantity?.[0] ?? payload.message ?? 'Could not add the stock.';

                return;
            }

            this.$store.toasts.success(
                'Stock added',
                `${payload.data.name} is now at ${payload.data.stock_on_hand} units.`,
            );
            setTimeout(() => window.location.reload(), 700);
        } catch {
            this.error = 'Could not reach the server.';
        } finally {
            this.working = false;
        }
    },
});
