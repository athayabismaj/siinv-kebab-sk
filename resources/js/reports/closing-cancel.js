document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-closing-cancel]');

    if (!form || form.dataset.closingCancelConfirmed === 'true') {
        return;
    }

    event.preventDefault();
    Swal.fire({
        title: 'Batalkan Tutup Buku?',
        html: '<p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Ketik <strong>BATALKAN</strong> untuk membatalkan tutup buku periode ini.</p><p class="text-[11px] text-rose-600 dark:text-rose-400 font-bold bg-rose-50 dark:bg-rose-500/10 p-3 rounded-xl border border-rose-100 dark:border-rose-500/20">Perhatian: Tindakan ini hanya menghapus snapshot, bukan menghapus transaksi asli.</p>',
        input: 'text',
        inputPlaceholder: 'Ketik BATALKAN',
        icon: 'warning',
        iconColor: '#f43f5e',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Konfirmasi',
        cancelButtonText: 'Batal',
        inputAttributes: { autocapitalize: 'off', autocomplete: 'off' },
        didOpen: () => {
            Swal.getInput()?.addEventListener('input', (inputEvent) => {
                inputEvent.target.value = inputEvent.target.value.toUpperCase();
            });
        },
        customClass: {
            popup: 'rounded-[2rem] dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-2xl pb-6',
            title: 'text-xl font-black text-slate-800 dark:text-white pt-2',
            input: 'rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 dark:text-white focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 text-center font-black tracking-widest uppercase !mt-6 shadow-sm transition-all',
            validationMessage: 'rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 font-bold text-[11px] border border-rose-200 dark:border-rose-500/20 mx-8 mt-4 py-3 px-4 flex items-center justify-center',
            actions: 'mt-6 w-full px-8 flex gap-3',
            confirmButton: 'rounded-xl px-6 py-2.5 font-bold shadow-sm flex-1',
            cancelButton: 'rounded-xl px-6 py-2.5 font-bold shadow-sm bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 flex-1 border-0',
        },
        preConfirm: (value) => {
            if (value !== 'BATALKAN') {
                Swal.showValidationMessage('Teks tidak cocok. Anda harus mengetik BATALKAN.');
                return false;
            }
            return value;
        },
    }).then((result) => {
        if (result.isConfirmed && result.value === 'BATALKAN') {
            const confirmation = form.querySelector('[name="confirmation"]');
            if (confirmation) {
                confirmation.value = result.value;
            }
            form.dataset.closingCancelConfirmed = 'true';
            form.submit();
        }
    });
});
