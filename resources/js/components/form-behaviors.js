document.addEventListener('change', (event) => {
    const field = event.target.closest('[data-submit-on-change]');
    if (field?.form) {
        field.form.submit();
    }
});

document.addEventListener('input', (event) => {
    const field = event.target.closest('[data-uppercase-input]');
    if (field && typeof field.value === 'string') {
        field.value = field.value.toUpperCase();
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-print-page]');
    if (button) {
        window.print();
    }
});
