function bindClearZeroInputs() {
    document.querySelectorAll('[data-clear-zero-input]').forEach((input) => {
        if (input.dataset.clearZeroBound === 'true') {
            return;
        }

        input.dataset.clearZeroBound = 'true';
        input.addEventListener('focus', () => {
            if (input.value === '0') {
                input.value = '';
            }
        });
        input.addEventListener('input', () => {
            input.value = input.value.replace(/^0+(?=\d)/, '');
        });
        input.addEventListener('blur', () => {
            if (input.value === '') {
                input.value = '0';
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindClearZeroInputs, { once: true });
} else {
    bindClearZeroInputs();
}
