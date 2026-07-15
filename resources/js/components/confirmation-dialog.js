const pendingConfirmations = new WeakSet();

function confirmationOptions(target) {
    return {
        title: target.dataset.confirmTitle || 'Konfirmasi',
        text: target.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan?',
        icon: target.dataset.confirmVariant || 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#ef4444',
        confirmButtonText: target.dataset.confirmButton || 'Ya, Lanjutkan',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl dark:bg-slate-900 dark:border dark:border-slate-800 shadow-xl',
            title: 'text-lg font-bold text-slate-800 dark:text-white',
            htmlContainer: 'text-sm font-medium text-slate-500 dark:text-slate-400',
            confirmButton: 'rounded-xl px-5 py-2.5 font-bold shadow-sm focus:ring-4 focus:ring-blue-500/20',
            cancelButton: 'rounded-xl px-5 py-2.5 font-bold shadow-sm bg-slate-100 text-slate-700 hover:bg-slate-200 focus:ring-4 focus:ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:focus:ring-slate-700',
        },
    };
}

function continueConfirmedAction(target) {
    if (target instanceof HTMLButtonElement && target.type === 'submit') {
        target.closest('form')?.requestSubmit(target);
        return;
    }

    if (target instanceof HTMLAnchorElement && target.href) {
        window.location.assign(target.href);
        return;
    }

    target.dispatchEvent(new CustomEvent('confirmed', { bubbles: true }));
}

function initConfirmationDialog() {
    const root = document.documentElement;
    if (root.dataset.confirmationDialogBound === 'true') return;

    root.dataset.confirmationDialogBound = 'true';

    document.addEventListener('click', async (event) => {
        const target = event.target.closest('[data-confirm]');
        if (!target) return;

        event.preventDefault();
        event.stopPropagation();

        if (pendingConfirmations.has(target)) return;
        pendingConfirmations.add(target);

        const options = confirmationOptions(target);
        const confirmed = window.Swal
            ? (await window.Swal.fire(options)).isConfirmed
            : window.confirm(options.text);

        if (!confirmed) {
            pendingConfirmations.delete(target);
            return;
        }

        continueConfirmedAction(target);
    }, true);
}

initConfirmationDialog();
