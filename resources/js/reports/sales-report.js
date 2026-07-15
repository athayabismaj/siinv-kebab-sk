function createPaginationButton(label, page, render, disabled = false, active = false) {
    const button = document.createElement('button');
    button.textContent = label;
    button.disabled = disabled;
    button.className = [
        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200',
        active
            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
            : disabled
                ? 'text-slate-300 dark:text-slate-700 cursor-not-allowed'
                : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800',
    ].join(' ');

    if (!disabled) {
        button.addEventListener('click', () => render(page));
    }

    return button;
}

function setupPaginatedTable({ bodyId, cardListId = null, infoId, controlsId, perPage = 20 }) {
    const body = document.getElementById(bodyId);
    if (!body) {
        return;
    }

    const rows = Array.from(body.querySelectorAll('tr:not([data-empty-row="true"])'));
    const emptyRows = Array.from(body.querySelectorAll('tr[data-empty-row="true"]'));
    const cardList = cardListId ? document.getElementById(cardListId) : null;
    const cards = cardList ? Array.from(cardList.children).filter((card) => card.dataset.emptyCard !== 'true') : [];
    const emptyCards = cardList ? Array.from(cardList.children).filter((card) => card.dataset.emptyCard === 'true') : [];
    const info = document.getElementById(infoId);
    const controls = document.getElementById(controlsId);

    if (rows.length === 0) {
        emptyRows.forEach((row) => { row.style.display = ''; });
        emptyCards.forEach((card) => { card.style.display = ''; });
        if (info) info.textContent = 'Belum ada data pada periode ini.';
        if (controls) controls.innerHTML = '';
        return;
    }

    emptyRows.forEach((row) => { row.style.display = 'none'; });
    emptyCards.forEach((card) => { card.style.display = 'none'; });

    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / perPage);

    const render = (page) => {
        currentPage = page;
        const start = (page - 1) * perPage;
        const end = start + perPage;

        rows.forEach((row, index) => {
            row.style.display = index >= start && index < end ? '' : 'none';
        });
        cards.forEach((card, index) => {
            card.style.display = index >= start && index < end ? '' : 'none';
        });

        if (info) {
            info.textContent = `Menampilkan ${start + 1}-${Math.min(end, rows.length)} dari ${rows.length} data`;
        }

        if (!controls) {
            return;
        }

        controls.innerHTML = '';
        controls.appendChild(createPaginationButton('<', currentPage - 1, render, currentPage === 1));

        const range = [];
        for (let pageNumber = 1; pageNumber <= totalPages; pageNumber += 1) {
            if (pageNumber === 1 || pageNumber === totalPages || Math.abs(pageNumber - currentPage) <= 1) {
                range.push(pageNumber);
            } else if (range[range.length - 1] !== '...') {
                range.push('...');
            }
        }

        range.forEach((pageNumber) => {
            if (pageNumber === '...') {
                const separator = document.createElement('span');
                separator.className = 'px-1 text-slate-300 dark:text-slate-700 text-xs';
                separator.textContent = '...';
                controls.appendChild(separator);
                return;
            }

            controls.appendChild(createPaginationButton(pageNumber, pageNumber, render, false, pageNumber === currentPage));
        });

        controls.appendChild(createPaginationButton('>', currentPage + 1, render, currentPage === totalPages));
    };

    render(1);
}

function bindSalesReport() {
    setupPaginatedTable({
        bodyId: 'transaction-table-body',
        cardListId: 'transaction-card-list',
        infoId: 'transaction-pagination-info',
        controlsId: 'transaction-pagination-controls',
        perPage: 10,
    });
    setupPaginatedTable({
        bodyId: 'table-body',
        infoId: 'pagination-info',
        controlsId: 'pagination-controls',
        perPage: 20,
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindSalesReport, { once: true });
} else {
    bindSalesReport();
}
