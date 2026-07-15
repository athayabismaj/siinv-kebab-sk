function initOtpPage() {
    const otpPage = document.querySelector('.otp-page');

    if (!otpPage || otpPage.dataset.otpBound === 'true') {
        return;
    }

    const inputs = Array.from(otpPage.querySelectorAll('.otp-box'));
    const hiddenInput = otpPage.querySelector('#otp');
    const form = otpPage.querySelector('#otpForm');
    const resendBtn = otpPage.querySelector('#resendBtn');
    const countdownElement = otpPage.querySelector('#countdown');

    if (inputs.length === 0 || !hiddenInput || !form) {
        return;
    }

    otpPage.dataset.otpBound = 'true';

    const updateHiddenInput = () => {
        hiddenInput.value = inputs.map((input) => input.value).join('');
    };

    inputs.forEach((input, index) => {
        input.addEventListener('input', (event) => {
            event.target.value = event.target.value.replace(/[^0-9]/g, '');

            if (input.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }

            updateHiddenInput();

            if (/^\d{6}$/.test(hiddenInput.value)) {
                window.setTimeout(() => form.requestSubmit(), 50);
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
    });

    if (countdownElement) {
        const expireTime = Number.parseInt(countdownElement.dataset.expire, 10) * 1000;

        if (Number.isFinite(expireTime)) {
            const interval = window.setInterval(() => {
                const distance = Math.floor((expireTime - Date.now()) / 1000);

                if (distance <= 0) {
                    window.clearInterval(interval);
                    countdownElement.textContent = 'Expired';
                    return;
                }

                countdownElement.textContent = distance;
            }, 1000);

            window.addEventListener('pagehide', () => window.clearInterval(interval), { once: true });
        }
    }

    if (resendBtn) {
        window.setTimeout(() => {
            resendBtn.disabled = false;
        }, 60000);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOtpPage, { once: true });
} else {
    initOtpPage();
}
