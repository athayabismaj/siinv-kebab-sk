function initLoginPasswordToggle() {
    const input = document.getElementById('login_password');
    const button = document.getElementById('toggle_login_password');
    const hiddenIcon = document.getElementById('login_password_icon_hidden');
    const visibleIcon = document.getElementById('login_password_icon_visible');

    if (!input || !button || !hiddenIcon || !visibleIcon || button.dataset.passwordToggleBound === 'true') {
        return;
    }

    button.dataset.passwordToggleBound = 'true';
    button.addEventListener('click', () => {
        const shouldShow = input.type === 'password';

        input.type = shouldShow ? 'text' : 'password';
        button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
        button.setAttribute('aria-label', shouldShow ? 'Sembunyikan password' : 'Tampilkan password');
        hiddenIcon.hidden = shouldShow;
        visibleIcon.hidden = !shouldShow;
        input.focus();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoginPasswordToggle, { once: true });
} else {
    initLoginPasswordToggle();
}
