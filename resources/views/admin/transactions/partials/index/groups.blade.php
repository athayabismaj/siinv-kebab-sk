@forelse($groupedTransactions as $group)
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-4 md:px-6 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $group['label'] }}</p>
        </div>

        <div class="md:hidden p-3 space-y-3">
            @foreach($group['items'] as $trx)
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                    <p class="font-semibold text-slate-800 dark:text-white break-all">{{ $trx->transaction_code }}</p>
                    <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Kasir</span><span class="font-medium text-right">{{ $trx->user->name ?? '-' }}</span></p>
                    <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Pembayaran</span><span class="font-medium text-right">{{ $trx->paymentMethod->name ?? '-' }}</span></p>
                    <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Total</span><span class="font-semibold text-right">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</span></p>
                    <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Waktu</span><span class="font-medium text-right">{{ $trx->created_at->copy()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y H:i') }}</span></p>
                    <a href="{{ route('admin.transactions.show', $trx->id) }}" class="inline-block text-sm text-blue-600 hover:underline">Detail</a>
                </div>
            @endforeach
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-3 text-left">Kode</th>
                        <th class="px-6 py-3 text-left">Kasir</th>
                        <th class="px-6 py-3 text-left">Pembayaran</th>
                        <th class="px-6 py-3 text-left">Total</th>
                        <th class="px-6 py-3 text-left">Tanggal</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['items'] as $trx)
                        <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="px-6 py-4 font-medium">{{ $trx->transaction_code }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $trx->user->name ?? '-' }}</td>
                            <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-md bg-slate-100 dark:bg-slate-800">{{ $trx->paymentMethod->name ?? '-' }}</span></td>
                            <td class="px-6 py-4 font-medium">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $trx->created_at->copy()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.transactions.show', $trx->id) }}" class="text-blue-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 px-6 py-10 text-center text-slate-500">
        Belum ada transaksi pada tanggal ini
    </div>
@endforelse
