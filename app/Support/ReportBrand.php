<?php

namespace App\Support;

class ReportBrand
{
    public static function logoPath(): ?string
    {
        $path = public_path('images/kebab-sk-logo-report.jpeg');

        if (! is_file($path)) {
            $path = public_path('images/kebab-sk-logo.jpeg');
        }

        return is_file($path) ? $path : null;
    }

    public static function logoDataUri(): ?string
    {
        $path = self::logoPath();

        if ($path === null) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($contents);
    }
}
