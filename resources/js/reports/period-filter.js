function formatDate(date) {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

function resolveWeekRange(date) {
    const offset = date.getDay() === 0 ? -6 : 1 - date.getDay();
    const start = new Date(date);
    start.setDate(date.getDate() + offset);
    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    return { from: formatDate(start), to: formatDate(end) };
}

function resolveRange(type, value = null) {
    if (type === 'daily') {
        const date = value ?? formatDate(new Date());
        return { from: date, to: date };
    }

    if (type === 'weekly') {
        return resolveWeekRange(value ? new Date(value) : new Date());
    }

    const selected = value ? value.split('-').map(Number) : [new Date().getFullYear(), new Date().getMonth() + 1];
    const start = new Date(selected[0], selected[1] - 1, 1);
    const end = new Date(selected[0], selected[1], 0);

    return { from: formatDate(start), to: formatDate(end) };
}

function submitRange(form, type, value = null) {
    const typeInput = form.querySelector('[name="type"]');
    const fromInput = form.querySelector('[name="date_from"]');
    const toInput = form.querySelector('[name="date_to"]');

    if (!typeInput || !fromInput || !toInput) {
        return;
    }

    const range = resolveRange(type, value);
    typeInput.value = type;
    fromInput.value = range.from;
    toInput.value = range.to;
    form.submit();
}

function bindPeriodFilters() {
    document.querySelectorAll('[data-period-filter]').forEach((form) => {
        if (form.dataset.periodFilterBound === 'true') {
            return;
        }

        form.dataset.periodFilterBound = 'true';
        form.querySelectorAll('[data-period-type]').forEach((button) => {
            button.addEventListener('click', () => submitRange(form, button.dataset.periodType));
        });

        const dateInput = form.querySelector('[data-period-date]');
        dateInput?.addEventListener('change', () => {
            if (dateInput.value) {
                submitRange(form, dateInput.dataset.periodMode, dateInput.value);
            }
        });

        const searchInput = form.querySelector('[data-period-search]');
        if (searchInput) {
            let timeout = null;
            searchInput.addEventListener('input', () => {
                window.clearTimeout(timeout);
                timeout = window.setTimeout(() => form.submit(), 500);
            });
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindPeriodFilters, { once: true });
} else {
    bindPeriodFilters();
}
