const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

export default () => ({
    open: false,
    confirming: false,
    working: false,
    editing: null,
    form: { name: '', email: '' },
    errors: {},

    create() {
        this.editing = null;
        this.form = { name: '', email: '' };
        this.errors = {};
        this.open = true;
        this.$nextTick(() => this.$refs.name?.focus());
    },

    edit(customer) {
        this.editing = customer;
        this.form = { name: customer.name, email: customer.email };
        this.errors = {};
        this.open = true;
        this.$nextTick(() => this.$refs.name?.focus());
    },

    confirmDelete(customer) {
        this.editing = customer;
        this.confirming = true;
    },

    validate() {
        const errors = {};

        if (this.form.name.trim() === '') {
            errors.name = 'A name is required.';
        }

        if (this.form.email.trim() === '') {
            errors.email = 'An email is required.';
        } else if (!EMAIL_PATTERN.test(this.form.email.trim())) {
            errors.email = 'That does not look like an email address.';
        }

        this.errors = errors;

        return Object.keys(errors).length === 0;
    },

    async save() {
        if (!this.validate()) {
            return;
        }

        this.working = true;

        try {
            const response = await fetch(
                this.editing ? `/api/customers/${this.editing.id}` : '/api/customers',
                {
                    method: this.editing ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        name: this.form.name.trim(),
                        email: this.form.email.trim(),
                    }),
                },
            );

            const payload = await response.json();

            if (!response.ok) {
                this.errors = Object.fromEntries(
                    Object.entries(payload.errors ?? {}).map(([field, messages]) => [field, messages[0]]),
                );

                if (Object.keys(this.errors).length === 0) {
                    this.$store.toasts.error('Could not save', payload.message ?? 'Please try again.');
                }

                return;
            }

            this.$store.toasts.success(
                this.editing ? 'Customer updated' : 'Customer added',
                payload.data.name,
            );
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
            const response = await fetch(`/api/customers/${this.editing.id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (response.status === 204) {
                window.location.href = '/customers';

                return;
            }

            const payload = await response.json().catch(() => ({}));
            this.$store.toasts.error('Could not delete', payload.message ?? 'Please try again.');
        } catch {
            this.$store.toasts.error('Could not reach the server', 'Check the connection and try again.');
        } finally {
            this.working = false;
            this.confirming = false;
        }
    },
});
