<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MenuVariantImageController extends Controller
{
    public function __invoke(string $filename): StreamedResponse
    {
        $path = 'menu-variants/'.$filename;

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
