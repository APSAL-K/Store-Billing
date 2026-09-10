export default () => ({
    open: false,
    confirming: false,
    restocking: false,
    working: false,
    editing: null,
    form: { name: '', code: '', unit_price: '', tax_percentage: '', stock_on_hand: '', low_stock_threshold: '' },
    restock: { quantity: '', note: '' },
    errors: {},

    create() {
        this.editing = null;
        this.form = {
            name: '',
            code: '',
            unit_price: '',
            tax_percentage: '18',
            stock_on_hand: '0',
            low_stock_threshold: '',
        };
        this.errors = {};
        this.open = true;
        this.$nextTick(() => this.$refs.name?.focus());
    },

    edit(product) {
        this.editing = product;
        this.form = {
            name: product.name,
            code: product.code,
            unit_price: product.unit_price,
            tax_percentage: product.tax_percentage,
            stock_on_hand: product.stock_on_hand,
            low_stock_threshold: product.low_stock_threshold ?? '',
        };
        this.errors = {};
        this.open = true;
        this.$nextTick(() => this.$refs.name?.focus());
    },

    confirmDelete(product) {
        this.editing = product;
        this.confirming = true;
    },

    openRestock(product) {
        this.editing = product;
        this.restock = { quantity: '', note: '' };
        this.errors = {};
        this.restocking = true;
        this.$nextTick(() => this.$refs.quantity?.focus());
    },

    payload() {
        const body = {
            name: this.form.name.trim(),
            code: this.form.code.trim().toUpperCase(),
            unit_price: this.form.unit_price === '' ? null : Number(this.form.unit_price),
            tax_percentage: this.form.tax_percentage === '' ? null : Number(this.form.tax_percentage),
            low_stock_threshold: this.form.low_stock_threshold === ''
                ? null
                : Math.trunc(Number(this.form.low_stock_threshold)),
        };

        if (!this.editing) {
            body.stock_on_hand = this.form.stock_on_hand === ''
                ? null
                : Math.trunc(Number(this.form.stock_on_hand));
        }

        return body;
    },

    async request(url, method, body) {
        return fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
    },

    async save() {
        this.working = true;
        this.errors = {};

        try {
            const response = await this.request(
                this.editing ? `/api/products/${this.editing.id}` : '/api/products',
                this.editing ? 'PUT' : 'POST',
                this.payload(),
            );

            const data = await response.json();

            if (!response.ok) {
                this.errors = Object.fromEntries(
                    Object.entries(data.errors ?? {}).map(([field, messages]) => [field, messages[0]]),
                );

                if (Object.keys(this.errors).length === 0) {
                    this.$store.toasts.error('Could not save', data.message ?? 'Please try again.');
                }

                return;
            }

            this.$store.toasts.success(this.editing ? 'Product updated' : 'Product added', data.data.name);
            setTimeout(() => window.location.reload(), 600);
        } catch {
            this.$store.toasts.error('Could not reach the server', 'Check the connection and try again.');
        } finally {
            this.working = false;
        }
    },

    async destroy() {
        this.working = true;

        try {
            const response = await this.request(`/api/products/${this.editing.id}`, 'DELETE');

            if (response.status === 204) {
                window.location.reload();

                return;
            }

            const data = await response.json().catch(() => ({}));
            this.$store.toasts.error('Could not delete', data.message ?? 'Please try again.');
        } catch {
            this.$store.toasts.error('Could not reach the server', 'Check the connection and try again.');
        } finally {
            this.working = false;
            this.confirming = false;
        }
    },

    async addStock() {
        const quantity = Math.trunc(Number(this.restock.quantity));

        if (!Number.isFinite(quantity) || quantity < 1) {
            this.errors = { quantity: 'Enter at least one unit.' };

            return;
        }

        this.working = true;

        try {
            const response = await this.request(`/api/products/${this.editing.id}/restock`, 'POST', {
                quantity,
                note: this.restock.note || null,
            });

            const data = await response.json();

            if (!response.ok) {
                this.errors = { quantity: data.errors?.quantity?.[0] ?? data.message ?? 'Could not add the stock.' };

                return;
            }

            this.$store.toasts.success('Stock added', `${data.data.name} is now at ${data.data.stock_on_hand} units.`);
            setTimeout(() => window.location.reload(), 700);
        } catch {
            this.errors = { quantity: 'Could not reach the server.' };
        } finally {
            this.working = false;
        }
    },
});
