let nextId = 0;

export default {
    items: [],

    push(tone, title, body = '', timeout = 6000) {
        const id = ++nextId;

        this.items.push({ id, tone, title, body });

        if (timeout) {
            setTimeout(() => this.dismiss(id), timeout);
        }
    },

    success(title, body = '') {
        this.push('success', title, body, 4000);
    },

    error(title, body = '') {
        this.push('error', title, body, 8000);
    },

    dismiss(id) {
        this.items = this.items.filter((toast) => toast.id !== id);
    },
};
