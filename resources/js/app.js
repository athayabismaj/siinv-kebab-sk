import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {

    const otpPage = document.querySelector('.otp-page');
    if (!otpPage) return;

    const inputs = document.querySelectorAll('.otp-box');
    const hiddenInput = document.getElementById('otp');
    const form = document.getElementById('otpForm');
    const resendBtn = document.getElementById('resendBtn');
    const countdownElement = document.getElementById('countdown');

    // ===== OTP INPUT LOGIC =====
    inputs.forEach((input, index) => {

        input.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');

            if (input.value && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }

            updateHiddenInput();

            // Auto submit jika 6 digit lengkap
            if (hiddenInput.value.length === 6) {
                updateHiddenInput();

                if (/^\d{6}$/.test(hiddenInput.value)) {
                    setTimeout(() => {
                        form.requestSubmit();
                    }, 50);
                }
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

    });

    function updateHiddenInput() {
        let otpValue = '';
        inputs.forEach(input => otpValue += input.value);
        hiddenInput.value = otpValue;
    }

    // ===== COUNTDOWN SINKRON SERVER =====
    if (countdownElement) {
        const expireTime = parseInt(countdownElement.dataset.expire) * 1000;

        let interval = setInterval(() => {
            const now = new Date().getTime();
            const distance = Math.floor((expireTime - now) / 1000);

            if (distance <= 0) {
                clearInterval(interval);
                countdownElement.textContent = "Expired";
            } else {
                countdownElement.textContent = distance;
            }
        }, 1000);
    }

    // ===== RESEND LOCK 60 DETIK =====
    if (resendBtn) {
        setTimeout(() => {
            resendBtn.disabled = false;
        }, 60000);
    }

});