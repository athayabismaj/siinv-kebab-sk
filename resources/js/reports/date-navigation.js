function bindDateNavigation() {
    document.querySelectorAll('[data-date-navigation]').forEach((input) => {
        input.addEventListener('change', () => {
            if (!input.value || !input.dataset.baseUrl || !input.dataset.param) {
                return;
            }

            const url = new URL(input.dataset.baseUrl, window.location.origin);
            url.searchParams.set(input.dataset.param, input.value);
            window.location.href = url.toString();
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindDateNavigation, { once: true });
} else {
    bindDateNavigation();
}
