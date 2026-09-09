export const DENOMINATIONS = [500, 200, 100, 50, 20, 10, 5, 2, 1];

export const toPaise = (amount) => Math.round(Number(amount || 0) * 100);

export const formatPaise = (paise) =>
    '₹' + (paise / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export const priceLine = (product, quantity) => {
    const subtotal = toPaise(product.unit_price) * quantity;
    const tax = Math.round((subtotal * product.tax_percentage) / 100);

    return { subtotal, tax, total: subtotal + tax };
};

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
