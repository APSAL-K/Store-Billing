import { changeBreakdown, formatPaise, priceLine, toPaise } from '../money';

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export default (config) => ({
    catalogue: config.catalogue,
    customers: config.customers,
    orderId: config.orderId ?? null,
    lines: [],

    mode: 'existing',
    customerId: config.customerId ?? null,
    customerSearch: '',
    customerOpen: false,
    customerHighlighted: 0,
    draft: { name: '', email: '' },

    search: '',
    tendered: config.tendered ?? '',
    errors: {},
    shortages: [],
    submitting: false,

    money: formatPaise,

    init() {
        const byId = new Map(this.catalogue.map((product) => [product.id, product]));

        (config.lines ?? []).forEach((line) => {
            const product = byId.get(line.product_id);

            if (product) {
                this.lines.push({ product, quantity: line.quantity });
            }
        });
    },

    get isEditing() {
        return this.orderId !== null;
    },

    get selectedCustomer() {
        return this.customers.find((customer) => customer.id === this.customerId) ?? null;
    },

    get customerMatches() {
        const needle = this.customerSearch.trim().toLowerCase();

        return this.customers
            .filter((customer) =>
                needle === '' ||
                customer.name.toLowerCase().includes(needle) ||
                customer.email.toLowerCase().includes(needle),
            )
            .slice(0, 8);
    },

    openCustomers() {
        this.customerOpen = true;
        this.customerHighlighted = 0;
    },

    moveCustomer(step) {
        if (!this.customerOpen) {
            this.openCustomers();

            return;
        }

        const count = this.customerMatches.length;

        if (count > 0) {
            this.customerHighlighted = (this.customerHighlighted + step + count) % count;
        }
    },

    pickCustomer(customer) {
        if (!customer) {
            return;
        }

        this.customerId = customer.id;
        this.customerSearch = '';
        this.customerOpen = false;
        this.clearError('customer.email');
    },

    pickHighlightedCustomer() {
        this.pickCustomer(this.customerMatches[this.customerHighlighted]);
    },

    clearCustomer() {
        this.customerId = null;
        this.customerSearch = '';
        this.$nextTick(() => this.$refs.customerSearch?.focus());
    },

    startNewCustomer() {
        this.mode = 'new';
        this.customerId = null;
        this.customerOpen = false;
        this.draft = { name: this.customerSearch.trim(), email: '' };
        this.customerSearch = '';
        this.$nextTick(() => this.$refs.draftName?.focus());
    },

    cancelNewCustomer() {
        this.mode = 'existing';
        this.draft = { name: '', email: '' };
        this.clearError('customer.name');
        this.clearError('customer.email');
    },

    get visibleProducts() {
        const needle = this.search.trim().toLowerCase();

        return this.catalogue.filter((product) =>
            needle === '' ||
            product.name.toLowerCase().includes(needle) ||
            product.code.toLowerCase().includes(needle),
        );
    },

    lineFor(product) {
        return this.lines.find((line) => line.product.id === product.id) ?? null;
    },

    quantityOf(product) {
        return this.lineFor(product)?.quantity ?? 0;
    },

    add(product) {
        if (product.stock_on_hand < 1) {
            return;
        }

        const line = this.lineFor(product);

        if (line) {
            this.setQuantity(line, line.quantity + 1);

            return;
        }

        this.lines.push({ product, quantity: 1 });
        this.clearError('items');
    },

    remove(index) {
        this.lines.splice(index, 1);
    },

    removeProduct(product) {
        const index = this.lines.findIndex((line) => line.product.id === product.id);

        if (index > -1) {
            this.lines.splice(index, 1);
        }
    },

    setQuantity(line, value) {
        const quantity = Math.trunc(Number(value));

        line.quantity = Number.isFinite(quantity) && quantity > 0
            ? Math.min(quantity, line.product.stock_on_hand)
            : 1;
    },

    shortageFor(productId) {
        return this.shortages.find((shortage) => shortage.product_id === productId) ?? null;
    },

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

    get suggestedTenders() {
        const due = Math.ceil(this.grandTotal / 100);

        if (due === 0) {
            return [];
        }

        const options = new Set([due]);

        [10, 50, 100, 500].forEach((step) => options.add(Math.ceil(due / step) * step));

        return [...options].sort((a, b) => a - b).slice(0, 4);
    },

    quickTender(amount) {
        this.tendered = String(amount);
        this.clearError('amount_tendered');
    },

    validate() {
        const errors = {};

        if (this.mode === 'existing') {
            if (this.customerId === null) {
                errors['customer.email'] = 'Choose a customer, or add a new one.';
            }
        } else {
            if (this.draft.name.trim() === '') {
                errors['customer.name'] = 'A name is required.';
            }

            if (this.draft.email.trim() === '') {
                errors['customer.email'] = 'An email is required.';
            } else if (!EMAIL_PATTERN.test(this.draft.email.trim())) {
                errors['customer.email'] = 'That does not look like an email address.';
            }
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
        this.mode = 'existing';
        this.customerId = null;
        this.customerSearch = '';
        this.draft = { name: '', email: '' };
        this.tendered = '';
        this.errors = {};
        this.shortages = [];
        this.search = '';
    },

    async submit() {
        this.shortages = [];

        if (!this.validate()) {
            this.$store.toasts.error('Check the highlighted fields', 'The bill was not saved.');

            return;
        }

        this.submitting = true;

        const customer = this.mode === 'existing'
            ? { email: this.selectedCustomer.email, name: null }
            : { email: this.draft.email.trim(), name: this.draft.name.trim() };

        try {
            const response = await fetch(this.isEditing ? `/api/orders/${this.orderId}` : '/api/orders', {
                method: this.isEditing ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    customer,
                    items: this.lines.map((line) => ({
                        product_id: line.product.id,
                        quantity: line.quantity,
                    })),
                    amount_tendered: this.tendered === '' ? null : Number(this.tendered),
                }),
            });

            const payload = await response.json();

            if (response.ok) {
                window.location.href = `/orders/${payload.data.id}?${this.isEditing ? 'updated' : 'placed'}=1`;

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

        this.errors = Object.fromEntries(
            Object.entries(payload.errors ?? {}).map(([field, messages]) => [
                field.startsWith('items.') ? 'items' : field,
                messages[0],
            ]),
        );

        this.$store.toasts.error('The bill was not saved', payload.message ?? 'Please review the form.');
    },
});
