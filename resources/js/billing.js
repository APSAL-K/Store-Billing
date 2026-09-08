const DENOMINATIONS = [500, 200, 100, 50, 20, 10, 5, 2, 1];

const form = document.getElementById('order-form');

if (form) {
    const catalogue = new Map(
        JSON.parse(document.getElementById('catalogue').textContent).map((p) => [p.id, p]),
    );

    const lines = document.getElementById('order-lines');
    const picker = document.getElementById('product-picker');
    const tendered = document.getElementById('amount-tendered');
    const alertBox = document.getElementById('form-alert');
    const submit = document.getElementById('generate-bill');

    const rupees = (paise) =>
        '₹' + (paise / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const toPaise = (amount) => Math.round(Number(amount || 0) * 100);

    /**
     * Mirrors App\Services\OrderTotals: integer paise, tax rounded per line.
     * The server stays the source of truth; this only keeps the screen live.
     */
    const priceLine = (product, quantity) => {
        const subtotal = toPaise(product.unit_price) * quantity;
        const tax = Math.round((subtotal * product.tax_percentage) / 100);

        return { subtotal, tax, total: subtotal + tax };
    };

    const changeBreakdown = (paise) => {
        let remaining = Math.floor(paise / 100);

        return DENOMINATIONS.reduce((parts, denomination) => {
            const count = Math.floor(remaining / denomination);

            if (count > 0) {
                remaining -= count * denomination;
                parts.push(`${count}×${denomination}`);
            }

            return parts;
        }, []);
    };

    const addRow = (productId) => {
        const product = catalogue.get(Number(productId));

        if (!product) {
            return;
        }

        const existing = lines.querySelector(`tr[data-product-id="${product.id}"]`);

        if (existing) {
            const quantity = existing.querySelector('input');
            quantity.value = Math.min(Number(quantity.value) + 1, product.stock_on_hand);
            recalculate();

            return;
        }

        const row = document.createElement('tr');
        row.dataset.productId = String(product.id);
        row.innerHTML = `
            <td class="px-4 py-2.5">
                <span class="font-medium text-slate-800">${product.name}</span>
                <span class="ml-1 text-xs text-slate-500">${product.code}</span>
            </td>
            <td class="px-4 py-2.5">
                <input type="number" min="1" max="${product.stock_on_hand}" value="1"
                       class="w-20 rounded border border-slate-300 px-2 py-1 text-sm tabular-nums outline-none focus:border-slate-500">
            </td>
            <td class="px-4 py-2.5 text-right tabular-nums text-slate-600">${rupees(toPaise(product.unit_price))}</td>
            <td class="px-4 py-2.5 text-right font-medium tabular-nums" data-line-total>₹0.00</td>
            <td class="px-4 py-2.5 text-right">
                <button type="button" data-remove aria-label="Remove ${product.name}"
                        class="text-slate-400 transition hover:text-red-600">&times;</button>
            </td>`;

        lines.append(row);
        recalculate();
    };

    const recalculate = () => {
        let subtotal = 0;
        let tax = 0;

        lines.querySelectorAll('tr').forEach((row) => {
            const product = catalogue.get(Number(row.dataset.productId));
            const quantity = Math.max(1, Number(row.querySelector('input').value) || 1);
            const line = priceLine(product, quantity);

            row.querySelector('[data-line-total]').textContent = rupees(line.total);
            subtotal += line.subtotal;
            tax += line.tax;
        });

        const grand = subtotal + tax;
        const change = Math.max(0, toPaise(tendered.value) - grand);
        const parts = changeBreakdown(change);

        document.querySelector('[data-total="subtotal"]').textContent = rupees(subtotal);
        document.querySelector('[data-total="tax"]').textContent = rupees(tax);
        document.querySelector('[data-total="grand"]').textContent = rupees(grand);
        document.querySelector('[data-total="change"]').textContent = rupees(change);
        document.querySelector('[data-total="change-breakdown"]').textContent = parts.length
            ? parts.join(' + ')
            : '';

        submit.disabled = lines.children.length === 0;
    };

    const clearErrors = () => {
        alertBox.classList.add('hidden');
        alertBox.textContent = '';
        document.querySelectorAll('[data-error]').forEach((element) => {
            element.classList.add('hidden');
            element.textContent = '';
        });
    };

    const showErrors = (payload) => {
        if (payload.shortages) {
            alertBox.textContent = payload.shortages
                .map((s) => `${s.name}: asked for ${s.requested}, only ${s.available} left.`)
                .join(' ');
            alertBox.classList.remove('hidden');

            return;
        }

        let handled = false;

        Object.entries(payload.errors ?? {}).forEach(([field, messages]) => {
            const target = document.querySelector(`[data-error="${field.replace(/\.\d+\..*$/, '')}"]`);

            if (target) {
                target.textContent = messages[0];
                target.classList.remove('hidden');
                handled = true;
            }
        });

        if (!handled) {
            alertBox.textContent = payload.message ?? 'Something went wrong saving this order.';
            alertBox.classList.remove('hidden');
        }
    };

    picker.addEventListener('change', (event) => {
        addRow(event.target.value);
        event.target.value = '';
    });

    document.getElementById('add-line').addEventListener('click', () => picker.focus());

    lines.addEventListener('input', recalculate);
    tendered.addEventListener('input', recalculate);

    lines.addEventListener('click', (event) => {
        if (event.target.closest('[data-remove]')) {
            event.target.closest('tr').remove();
            recalculate();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        submit.disabled = true;

        const items = [...lines.querySelectorAll('tr')].map((row) => ({
            product_id: Number(row.dataset.productId),
            quantity: Math.max(1, Number(row.querySelector('input').value) || 1),
        }));

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
                        email: document.getElementById('customer-email').value,
                        name: document.getElementById('customer-name').value || null,
                    },
                    items,
                    amount_tendered: tendered.value === '' ? null : Number(tendered.value),
                }),
            });

            const payload = await response.json();

            if (!response.ok) {
                showErrors(payload);
                submit.disabled = false;

                return;
            }

            window.location.href = `/orders/${payload.data.id}`;
        } catch (error) {
            alertBox.textContent = 'Could not reach the server. Check the connection and try again.';
            alertBox.classList.remove('hidden');
            submit.disabled = false;
        }
    });

    recalculate();
}
