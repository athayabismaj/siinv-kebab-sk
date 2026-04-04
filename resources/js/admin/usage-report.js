function formatDate(dateObj) {
    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function resolveWeekRange(dateObj) {
    const day = dateObj.getDay();
    const diff = day === 0 ? -6 : 1 - day;

    const start = new Date(dateObj);
    start.setDate(dateObj.getDate() + diff);

    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    return { from: formatDate(start), to: formatDate(end) };
}

window.changeType = function changeType(newType) {
    const typeInput = document.getElementById('hidden_type');
    const fromInput = document.getElementById('hidden_date_from');
    const toInput = document.getElementById('hidden_date_to');
    const form = document.getElementById('filter-form');

    if (!typeInput || !fromInput || !toInput || !form) {
        return;
    }

    typeInput.value = newType;

    const now = new Date();
    let from = '';
    let to = '';

    if (newType === 'daily') {
        from = formatDate(now);
        to = from;
    } else if (newType === 'weekly') {
        const range = resolveWeekRange(now);
        from = range.from;
        to = range.to;
    } else {
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        from = formatDate(start);
        to = formatDate(end);
    }

    fromInput.value = from;
    toInput.value = to;
    form.submit();
};

window.updateDateRange = function updateDateRange(input, type) {
    if (!input || !input.value) {
        return;
    }

    const fromInput = document.getElementById('hidden_date_from');
    const toInput = document.getElementById('hidden_date_to');
    const form = document.getElementById('filter-form');

    if (!fromInput || !toInput || !form) {
        return;
    }

    let from = '';
    let to = '';

    if (type === 'daily') {
        from = input.value;
        to = input.value;
    } else if (type === 'weekly') {
        const range = resolveWeekRange(new Date(input.value));
        from = range.from;
        to = range.to;
    } else {
        const parts = input.value.split('-');
        const year = Number(parts[0]);
        const month = Number(parts[1]) - 1;
        const start = new Date(year, month, 1);
        const end = new Date(year, month + 1, 0);
        from = formatDate(start);
        to = formatDate(end);
    }

    fromInput.value = from;
    toInput.value = to;
    form.submit();
};
