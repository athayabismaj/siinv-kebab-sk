function toNumber(value) {
    const number = Number.parseFloat(value);
    return Number.isFinite(number) ? number : 0;
}

function toBase(displayQuantity, unit) {
    return unit === 'kg' || unit === 'l' ? displayQuantity * 1000 : displayQuantity;
}

function formatQuantity(number) {
    return number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '') || '0';
}

function bindCloseSession(root) {
    const inputs = Array.from(root.querySelectorAll('.daily-remaining-input'));
    if (inputs.length === 0) {
        return;
    }

    let unitMap = [];
    try {
        unitMap = JSON.parse(root.dataset.unitMap || '[]');
    } catch {
        return;
    }

    const recompute = () => {
        const groups = {};
        unitMap.forEach((unit) => {
            groups[unit] = { opening: 0, remaining: 0 };
        });

        inputs.forEach((input) => {
            const openingBase = toNumber(input.dataset.openingBase);
            const displayUnit = input.dataset.displayUnit || '';
            const unit = (displayUnit || 'unit').toUpperCase();
            const maxDisplay = toNumber(input.getAttribute('max'));
            let currentDisplay = toNumber(input.value);

            if (currentDisplay > maxDisplay) {
                input.value = maxDisplay;
                currentDisplay = maxDisplay;
            } else if (currentDisplay < 0) {
                input.value = 0;
                currentDisplay = 0;
            }

            if (!groups[unit]) {
                groups[unit] = { opening: 0, remaining: 0 };
            }

            groups[unit].opening += openingBase;
            groups[unit].remaining += toBase(currentDisplay, displayUnit.toLowerCase());
        });

        unitMap.forEach((unit, index) => {
            const group = groups[unit];
            if (!group) {
                return;
            }

            const used = Math.max(0, group.opening - group.remaining);
            const usesConvertedDisplayUnit = unit === 'KG' || unit === 'L';
            const displayRemaining = usesConvertedDisplayUnit ? group.remaining / 1000 : group.remaining;
            const displayUsed = usesConvertedDisplayUnit ? used / 1000 : used;
            const remainingElement = document.getElementById(`summary-remaining-${index}`);
            const usedElement = document.getElementById(`summary-used-${index}`);

            if (remainingElement) remainingElement.textContent = formatQuantity(displayRemaining);
            if (usedElement) usedElement.textContent = formatQuantity(displayUsed);
        });
    };

    inputs.forEach((input) => {
        input.addEventListener('input', recompute);
        input.addEventListener('blur', () => {
            if (input.value === '') input.value = '0';
            recompute();
        });
    });

    recompute();
}

function bindCloseSessions() {
    document.querySelectorAll('[data-daily-stock-close]').forEach((root) => bindCloseSession(root));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindCloseSessions, { once: true });
} else {
    bindCloseSessions();
}
