<?php

namespace App\View\Presenters;

final class StockSessionPresenter
{
    public function present(?string $statusKey, ?string $label): StockSessionPresentation
    {
        $classes = match (strtolower(trim((string) $statusKey))) {
            'closed' => [
                'dot' => 'bg-emerald-500',
                'text' => 'text-emerald-700 dark:text-emerald-300',
                'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300',
            ],
            'open' => [
                'dot' => 'bg-amber-500',
                'text' => 'text-amber-700 dark:text-amber-300',
                'badge' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300',
            ],
            default => [
                'dot' => 'bg-slate-400',
                'text' => 'text-slate-700 dark:text-slate-300',
                'badge' => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
            ],
        };

        return new StockSessionPresentation(
            label: trim((string) $label) !== '' ? trim((string) $label) : 'Belum Dibuka',
            dotClass: $classes['dot'],
            textClass: $classes['text'],
            badgeClass: $classes['badge'],
        );
    }
}
