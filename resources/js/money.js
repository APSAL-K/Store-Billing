export const DENOMINATIONS = [500, 200, 100, 50, 20, 10, 5, 2, 1];

export const toPaise = (amount) => Math.round(Number(amount || 0) * 100);

export const formatPaise = (paise) =>
    '₹' + (paise / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/**
 * Mirrors App\Services\OrderTotals: integer paise, tax applied and rounded per
 * line. The server remains the source of truth; this only keeps the screen live
 * while the cashier is still building the order.
 */
export const priceLine = (product, quantity) => {
    const subtotal = toPaise(product.unit_price) * quantity;
    const tax = Math.round((subtotal * product.tax_percentage) / 100);

    return { subtotal, tax, total: subtotal + tax };
};

/**
 * Mirrors App\Support\CashDrawer: notes and coins down to a rupee.
 */
export const changeBreakdown = (paise) => {
    let remaining = Math.floor(paise / 100);

    return DENOMINATIONS.reduce((parts, denomination) => {
        const count = Math.floor(remaining / denomination);

        if (count > 0) {
            remaining -= count * denomination;
            parts.push({ denomination, count });
        }

        return parts;
    }, []);
};
