@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">Riwayat Export</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">File export diproses di background queue.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
            {{ session('error') }}
        </div>
    @endif

    @if(!empty($runtimeError))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
            {{ $runtimeError }}
        </div>
    @endif

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-500 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-4 py-3 text-left">ID</th>
                        <th class="px-4 py-3 text-left">Tipe</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Permintaan</th>
                        <th class="px-4 py-3 text-left">Jadwal</th>
                        <th class="px-4 py-3 text-left">Selesai</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exports as $item)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-200">#{{ $item->id }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $item->type }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ strtoupper($item->status) }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ optional($item->scheduled_for)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ optional($item->finished_at)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($item->status === 'completed')
                                    @if($scope === 'admin')
                                        <a href="{{ route('admin.exports.download', $item) }}" class="text-blue-600 hover:underline">Download</a>
                                    @else
                                        <a href="{{ route('owner.exports.download', $item) }}" class="text-blue-600 hover:underline">Download</a>
                                    @endif
                                @elseif($item->status === 'failed')
                                    <span class="text-rose-600" title="{{ $item->error_message }}">Gagal</span>
                                @else
                                    <span class="text-amber-600">Diproses...</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada export.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-end">
        {{ $exports->links() }}
    </div>
</div>
@endsection
