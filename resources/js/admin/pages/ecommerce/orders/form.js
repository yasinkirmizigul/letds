function parseNumber(value) {
    const normalized = String(value ?? '0').replace(',', '.');
    const parsed = Number.parseFloat(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
}

function money(value) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value || 0);
}

function findPageRoot(ctx) {
    if (ctx?.root?.matches?.('[data-page^="ecommerce.orders."]')) {
        return ctx.root;
    }

    return ctx?.root?.querySelector?.('[data-page^="ecommerce.orders."]')
        || document.querySelector('[data-page^="ecommerce.orders."]');
}

function productMap(form) {
    const source = form.querySelector('[data-product-options]');
    if (!source) return new Map();

    try {
        const products = JSON.parse(source.getAttribute('data-product-options') || '[]');

        return new Map(products.map((product) => [String(product.id), product]));
    } catch (_) {
        return new Map();
    }
}

function rowFields(row) {
    return {
        product: row.querySelector('[data-order-item-product]'),
        title: row.querySelector('[data-order-item-title]'),
        sku: row.querySelector('[data-order-item-sku]'),
        brand: row.querySelector('[data-order-item-brand]'),
        barcode: row.querySelector('[data-order-item-barcode]'),
        quantity: row.querySelector('[data-order-item-quantity]'),
        unitPrice: row.querySelector('[data-order-item-unit-price]'),
        discount: row.querySelector('[data-order-item-discount]'),
        taxRate: row.querySelector('[data-order-item-tax-rate]'),
        total: row.querySelector('[data-order-item-total]'),
    };
}

function calculateRow(row) {
    const fields = rowFields(row);
    const quantity = Math.max(0, parseNumber(fields.quantity?.value || 0));
    const unitPrice = Math.max(0, parseNumber(fields.unitPrice?.value || 0));
    const subtotal = quantity * unitPrice;
    const discount = Math.min(subtotal, Math.max(0, parseNumber(fields.discount?.value || 0)));
    const taxRate = Math.max(0, parseNumber(fields.taxRate?.value || 0));
    const taxBase = Math.max(0, subtotal - discount);
    const taxTotal = taxBase * (taxRate / 100);
    const total = taxBase + taxTotal;

    if (fields.total) {
        fields.total.textContent = money(total);
    }

    return {
        subtotal,
        discount,
        taxTotal,
        total,
    };
}

function updateSummary(form) {
    const orderDiscount = Math.max(0, parseNumber(form.querySelector('[data-order-discount]')?.value || 0));
    const shipping = Math.max(0, parseNumber(form.querySelector('[data-order-shipping]')?.value || 0));
    let subtotal = 0;
    let lineDiscount = 0;
    let tax = 0;

    form.querySelectorAll('[data-order-item-row]').forEach((row) => {
        const rowTotal = calculateRow(row);
        subtotal += rowTotal.subtotal;
        lineDiscount += rowTotal.discount;
        tax += rowTotal.taxTotal;
    });

    const discount = Math.min(subtotal, lineDiscount + orderDiscount);
    const grand = Math.max(0, subtotal - discount + tax + shipping);

    const values = {
        subtotal,
        discount,
        tax,
        grand,
    };

    Object.entries(values).forEach(([key, value]) => {
        const target = form.querySelector(`[data-order-summary="${key}"]`);
        if (target) target.textContent = money(value);
    });
}

function hydrateProduct(row, products, form) {
    const fields = rowFields(row);
    const selected = products.get(String(fields.product?.value || ''));
    if (!selected) return;

    if (fields.title) fields.title.value = selected.title || '';
    if (fields.sku) fields.sku.value = selected.sku || '';
    if (fields.brand) fields.brand.value = selected.brand || '';
    if (fields.barcode) fields.barcode.value = selected.barcode || '';
    if (fields.unitPrice) fields.unitPrice.value = selected.unit_price ?? 0;
    if (fields.taxRate) fields.taxRate.value = selected.tax_rate ?? 0;

    const currency = form.querySelector('[data-order-currency]');
    if (currency && (!currency.value || currency.value.toUpperCase() === 'TRY')) {
        currency.value = selected.currency || currency.value || 'TRY';
    }

    updateSummary(form);
}

function initKtSelects(scope) {
    scope.querySelectorAll?.('[data-kt-select="true"]').forEach((select) => {
        try {
            window.KTSelect?.getOrCreateInstance?.(select);
        } catch (_) {
            //
        }
    });
}

function addItemRow(form) {
    const template = form.querySelector('template[data-order-item-template]');
    const list = form.querySelector('[data-order-items]');
    if (!template || !list) return;

    const index = Date.now().toString();
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', index).trim();
    const row = wrapper.firstElementChild;
    if (!row) return;

    list.appendChild(row);
    initKtSelects(row);
    updateSummary(form);
}

function removeItemRow(form, row) {
    const rows = [...form.querySelectorAll('[data-order-item-row]')];
    if (rows.length <= 1) {
        row.querySelectorAll('input, textarea').forEach((input) => {
            input.value = '';
        });
        const product = row.querySelector('[data-order-item-product]');
        if (product) product.value = '';
        updateSummary(form);
        return;
    }

    row.remove();
    updateSummary(form);
}

function initMemberAutofill(form) {
    const memberSelect = form.querySelector('#member_id');
    if (!memberSelect) return;

    memberSelect.addEventListener('change', () => {
        const option = memberSelect.selectedOptions?.[0];
        if (!option) return;

        const values = {
            customer_name: option.dataset.memberName,
            customer_email: option.dataset.memberEmail,
            customer_phone: option.dataset.memberPhone,
        };

        Object.entries(values).forEach(([id, value]) => {
            const input = form.querySelector(`#${id}`);
            if (input && value && !input.value) {
                input.value = value;
            }
        });
    });
}

export default function initEcommerceOrderForm(ctx = {}) {
    const root = findPageRoot(ctx);
    const form = root?.querySelector?.('[data-ecommerce-order-form="true"]');
    if (!form) return;

    const products = productMap(form);
    initMemberAutofill(form);
    initKtSelects(form);
    updateSummary(form);

    form.addEventListener('input', (event) => {
        if (event.target.closest('[data-order-item-row]') || event.target.matches('[data-order-discount], [data-order-shipping]')) {
            updateSummary(form);
        }
    });

    form.addEventListener('change', (event) => {
        const productSelect = event.target.closest('[data-order-item-product]');
        if (productSelect) {
            hydrateProduct(productSelect.closest('[data-order-item-row]'), products, form);
            return;
        }

        if (event.target.matches('[data-order-discount], [data-order-shipping]')) {
            updateSummary(form);
        }
    });

    form.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-order-add-item]');
        if (addButton) {
            addItemRow(form);
            return;
        }

        const removeButton = event.target.closest('[data-order-remove-item]');
        if (removeButton) {
            removeItemRow(form, removeButton.closest('[data-order-item-row]'));
        }
    });
}
