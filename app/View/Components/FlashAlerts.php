<?php

namespace App\View\Components;

use Illuminate\Support\Collection;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Illuminate\View\View;

class FlashAlerts extends Component
{
    public function __construct(
        public string $containerClass = 'mb-5 w-full space-y-2',
        public bool $autoDismiss = true,
        public bool $includeErrors = false,
        public string $position = 'inline',
        public ?ViewErrorBag $errorBag = null,
    ) {}

    public function render(): View
    {
        $flashMessages = $this->flashMessages();
        $validationErrors = $this->validationErrors();

        return view('components.flash-alerts', [
            'flashMessages' => $flashMessages,
            'validationErrors' => $validationErrors,
            'shouldAutoDismiss' => $this->autoDismiss
                && $flashMessages->isNotEmpty()
                && $validationErrors->isEmpty(),
        ]);
    }

    /**
     * @return Collection<int, array{label: string, message: mixed, border: string, iconBg: string, labelColor: string, icon: string}>
     */
    private function flashMessages(): Collection
    {
        return collect([
            [
                'label' => 'Berhasil',
                'message' => session('success'),
                'border' => 'border-emerald-200 dark:border-emerald-900/60',
                'iconBg' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
                'labelColor' => 'text-emerald-600 dark:text-emerald-300',
                'icon' => 'check',
            ],
            [
                'label' => 'Perlu Dicek',
                'message' => session('warning'),
                'border' => 'border-amber-200 dark:border-amber-900/60',
                'iconBg' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
                'labelColor' => 'text-amber-600 dark:text-amber-300',
                'icon' => 'warning',
            ],
            [
                'label' => 'Gagal',
                'message' => session('error'),
                'border' => 'border-rose-200 dark:border-rose-900/60',
                'iconBg' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
                'labelColor' => 'text-rose-600 dark:text-rose-300',
                'icon' => 'error',
            ],
        ])->filter(fn (array $item): bool => filled($item['message']))->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function validationErrors(): Collection
    {
        $errors = $this->errorBag ?? session('errors');

        if (! $this->includeErrors || $errors === null) {
            return collect();
        }

        return collect($errors->all())->filter()->values();
    }
}
