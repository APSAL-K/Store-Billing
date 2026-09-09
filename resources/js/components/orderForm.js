import { changeBreakdown, formatPaise, priceLine, toPaise } from '../money';

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export default (catalogue) => ({
    catalogue,
    lines: [],

    customer: { email: '', name: '' },
    lookup: { state: 'idle', ordersCount: 0 },

    tendered: '',
    errors: {},
    shortages: [],
    submitting: false,

    // Product combobox
    search: '',
    open: false,
    highlighted: 0,

    money: formatPaise,

    // -- Catalogue ----------------------------------------------------------

    get matches() {
        const needle = this.search.trim().toLowerCase();
        const chosen = new Set(this.lines.map((line) => line.product.id));

        return this.catalogue
            .filter((product) => !chosen.has(product.id))
            .filter((product) =>
                needle === '' ||
                product.name.toLowerCase().includes(needle) ||
                product.code.toLowerCase().includes(needle),
            )
            .slice(0, 8);
    },

    openPicker() {
        this.open = true;
        this.highlighted = 0;
    },

    move(step) {
        if (!this.open) {
            this.openPicker();

            return;
        }

        const count = this.matches.length;

        if (count > 0) {
            this.highlighted = (this.highlighted + step + count) % count;
        }
    },

    choose(product) {
        if (!product || product.stock_on_hand < 1) {
            return;
        }

        this.lines.push({ product, quantity: 1 });
        this.search = '';
        this.open = false;
        this.highlighted = 0;
        delete this.errors.items;

        this.$nextTick(() => this.$refs.search?.focus());
    },

    chooseHighlighted() {
        this.choose(this.matches[this.highlighted]);
    },

    remove(index) {
        this.lines.splice(index, 1);
    },

    /**
     * Quantity is clamped to what is on the shelf. The server checks again under
     * a row lock, but there is no reason to let the cashier type an order that
     * is already known to fail.
     */
    setQuantity(line, value) {
        const quantity = Math.trunc(Number(value));

        line.quantity = Number.isFinite(quantity) && quantity > 0
            ? Math.min(quantity, line.product.stock_on_hand)
            : 1;
    },

    // -- Totals -------------------------------------------------------------

    get subtotal() {
        return this.lines.reduce((sum, line) => sum + priceLine(line.product, line.quantity).subtotal, 0);
    },

    get tax() {
        return this.lines.reduce((sum, line) => sum + priceLine(line.product, line.quantity).tax, 0);
    },

    get grandTotal() {
        return this.subtotal + this.tax;
    },

    get tenderedPaise() {
        return this.tendered === '' ? null : toPaise(this.tendered);
    },

    get changeDue() {
        return this.tenderedPaise === null ? 0 : Math.max(0, this.tenderedPaise - this.grandTotal);
    },

    get changeParts() {
        return changeBreakdown(this.changeDue);
    },

    get isShort() {
        return this.tenderedPaise !== null && this.tenderedPaise < this.grandTotal;
    },

    get itemCount() {
        return this.lines.reduce((sum, line) => sum + line.quantity, 0);
    },

    lineTotal(line) {
        return priceLine(line.product, line.quantity).total;
    },

    // -- Customer -----------------------------------------------------------

    /**
     * Looks the email up as soon as it is a plausible address so a returning
     * customer does not have to type their name again.
     */
    async lookupCustomer() {
        const email = this.customer.email.trim().toLowerCase();

        if (!EMAIL_PATTERN.test(email)) {
            this.lookup = { state: 'idle', ordersCount: 0 };

            return;
        }

        this.lookup = { state: 'searching', ordersCount: 0 };

        try {
            const response = await fetch(`/api/customers/lookup?email=${encodeURIComponent(email)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                this.lookup = { state: 'new', ordersCount: 0 };

                return;
            }

            const { data } = await response.json();

            this.customer.name = data.name;
            this.lookup = { state: 'known', ordersCount: data.orders_count ?? 0 };
            delete this.errors['customer.name'];
        } catch {
            // A failed lookup is not worth interrupting the sale for; the server
            // will ask for a name if it turns out to need one.
            this.lookup = { state: 'idle', ordersCount: 0 };
        }
    },

    // -- Validation ---------------------------------------------------------

    validate() {
        const errors = {};

        if (this.customer.email.trim() === '') {
            errors['customer.email'] = 'An email is required.';
        } else if (!EMAIL_PATTERN.test(this.customer.email.trim())) {
            errors['customer.email'] = 'That does not look like an email address.';
        }

        if (this.lookup.state !== 'known' && this.customer.name.trim() === '') {
            errors['customer.name'] = 'A name is required the first time we see this email.';
        }

        if (this.lines.length === 0) {
            errors.items = 'Add at least one product to the bill.';
        }

        if (this.isShort) {
            errors.amount_tendered = `The amount given is short of ${formatPaise(this.grandTotal)}.`;
        }

        this.errors = errors;

        return Object.keys(errors).length === 0;
    },

    clearError(field) {
        delete this.errors[field];
    },

    reset() {
        this.lines = [];
        this.customer = { email: '', name: '' };
        this.lookup = { state: 'idle', ordersCount: 0 };
        this.tendered = '';
        this.errors = {};
        this.shortages = [];
        this.search = '';
    },

    // -- Submit -------------------------------------------------------------

    async submit() {
        this.shortages = [];

        if (!this.validate()) {
            this.$store.toasts.error('Check the highlighted fields', 'The bill was not saved.');

            return;
        }

        this.submitting = true;

        try {
            const response = await fetch('/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    customer: {
                        email: this.customer.email.trim(),
                        name: this.customer.name.trim() || null,
                    },
                    items: this.lines.map((line) => ({
                        product_id: line.product.id,
                        quantity: line.quantity,
                    })),
                    amount_tendered: this.tendered === '' ? null : Number(this.tendered),
                }),
            });

            const payload = await response.json();

            if (response.ok) {
                window.location.href = `/orders/${payload.data.id}?placed=1`;

                return;
            }

            this.handleFailure(payload);
        } catch {
            this.$store.toasts.error('Could not reach the server', 'Check the connection and try again.');
        } finally {
            this.submitting = false;
        }
    },

    handleFailure(payload) {
        if (payload.shortages) {
            this.shortages = payload.shortages;
            this.$store.toasts.error(
                'Not enough stock',
                payload.shortages.map((s) => `${s.name}: ${s.available} left`).join(', '),
            );

            return;
        }

        // Laravel reports nested item errors as items.0.quantity; the row itself
        // is what the cashier needs to see highlighted.
        this.errors = Object.fromEntries(
            Object.entries(payload.errors ?? {}).map(([field, messages]) => [
                field.startsWith('items.') ? 'items' : field,
                messages[0],
            ]),
        );

        this.$store.toasts.error('The bill was not saved', payload.message ?? 'Please review the form.');
    },
});
