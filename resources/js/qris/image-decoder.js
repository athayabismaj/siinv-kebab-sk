import jsQR from 'jsqr';

document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.querySelector('[data-qris-image]');
    const payloadInput = document.querySelector('[data-qris-payload]');
    const status = document.querySelector('[data-qris-status]');

    if (!fileInput || !payloadInput || !status) {
        return;
    }

    const setStatus = (message, tone = 'neutral') => {
        status.textContent = message;
        status.dataset.tone = tone;
        status.className = tone === 'success'
            ? 'mt-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400'
            : tone === 'error'
                ? 'mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400'
                : 'mt-2 text-xs font-medium text-slate-500 dark:text-slate-400';
    };

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file) {
            return;
        }

        if (!file.type.startsWith('image/')) {
            setStatus('File harus berupa gambar QRIS.', 'error');
            fileInput.value = '';
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            setStatus('Ukuran gambar maksimal 10 MB.', 'error');
            fileInput.value = '';
            return;
        }

        setStatus('Membaca QR dari gambar…');

        try {
            const bitmap = await createImageBitmap(file);
            const canvas = document.createElement('canvas');
            const maxSide = 1600;
            const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));

            const context = canvas.getContext('2d', { willReadFrequently: true });
            context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            bitmap.close();

            const imageData = context.getImageData(0, 0, canvas.width, canvas.height);
            const result = jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'attemptBoth',
            });

            if (!result?.data) {
                throw new Error('QR tidak ditemukan');
            }

            payloadInput.value = result.data.trim();
            payloadInput.dispatchEvent(new Event('input', { bubbles: true }));
            setStatus('QR berhasil dibaca. Simpan untuk menjalankan validasi backend.', 'success');
        } catch (error) {
            payloadInput.value = '';
            setStatus('QR tidak terbaca. Gunakan gambar yang tajam atau tempel payload secara manual.', 'error');
        }
    });
});
