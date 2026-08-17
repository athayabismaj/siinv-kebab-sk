<?php

namespace App\Console\Commands;

use App\Models\MenuVariant;
use App\Services\MenuVariantImageService;
use App\Support\AdminCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ConvertMenuVariantImagesToWebp extends Command
{
    protected $signature = 'menu-images:convert-webp
        {--dry-run : Tampilkan gambar yang akan dikonversi tanpa mengubah file atau database}
        {--delete-originals : Hapus file JPG/PNG lama setelah database berhasil diperbarui}';

    protected $description = 'Convert legacy menu variant images to resized WebP thumbnails';

    public function handle(MenuVariantImageService $imageService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deleteOriginals = (bool) $this->option('delete-originals');
        $converted = 0;
        $alreadyWebp = 0;
        $failed = 0;

        MenuVariant::query()
            ->whereNotNull('image_path')
            ->orderBy('id')
            ->eachById(function (MenuVariant $variant) use (
                $imageService,
                $dryRun,
                $deleteOriginals,
                &$converted,
                &$alreadyWebp,
                &$failed,
            ): void {
                $oldPath = (string) $variant->image_path;
                if (strtolower(pathinfo($oldPath, PATHINFO_EXTENSION)) === 'webp') {
                    $alreadyWebp++;

                    return;
                }

                if ($dryRun) {
                    $this->line("[DRY RUN] Varian #{$variant->id}: {$oldPath}");
                    $converted++;

                    return;
                }

                $newPath = null;
                try {
                    $newPath = $imageService->convertStoredToWebp($oldPath);

                    DB::transaction(function () use ($variant, $newPath): void {
                        MenuVariant::query()
                            ->whereKey($variant->id)
                            ->lockForUpdate()
                            ->update(['image_path' => $newPath]);
                    });

                    if ($deleteOriginals) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    $converted++;
                    $this->info("Varian #{$variant->id} dikonversi ke {$newPath}");
                } catch (Throwable $exception) {
                    if ($newPath !== null) {
                        Storage::disk('public')->delete($newPath);
                    }

                    $failed++;
                    $this->error("Varian #{$variant->id} gagal: {$exception->getMessage()}");
                }
            });

        if (! $dryRun && $converted > 0) {
            AdminCache::bumpCatalog();
        }

        $this->newLine();
        $this->table(
            ['Status', 'Jumlah'],
            [
                [$dryRun ? 'Akan dikonversi' : 'Berhasil dikonversi', $converted],
                ['Sudah WebP', $alreadyWebp],
                ['Gagal', $failed],
            ],
        );

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
