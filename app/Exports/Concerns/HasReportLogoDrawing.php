<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

trait HasReportLogoDrawing
{
    private ?string $logoPath = null;

    public function drawings()
    {
        if ($this->logoPath === null || ! is_file($this->logoPath)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('Logo Kebab SK');
        $drawing->setDescription('Logo usaha Kebab SK');
        $drawing->setPath($this->logoPath);
        $drawing->setHeight(56);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(8);
        $drawing->setOffsetY(4);

        return [$drawing];
    }

    private function raiseMemoryLimit(): void
    {
        $currentLimit = ini_get('memory_limit');

        if ($currentLimit === false || $currentLimit === '-1') {
            return;
        }

        if ($this->memoryLimitToBytes($currentLimit) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }

    private function memoryLimitToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
