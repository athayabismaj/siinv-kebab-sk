<?php

namespace Tests\Feature\Console;

use App\Models\Menu;
use App\Models\MenuVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConvertMenuVariantImagesToWebpTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_legacy_images_and_keeps_the_original_by_default(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD WebP tidak tersedia.');
        }

        Storage::fake('public');
        $legacyPath = 'menu-variants/legacy.png';
        Storage::disk('public')->put(
            $legacyPath,
            UploadedFile::fake()->image('legacy.png', 1200, 800)->getContent(),
        );
        $variant = $this->createVariant($legacyPath);

        $this->artisan('menu-images:convert-webp')->assertSuccessful();

        $variant->refresh();
        $this->assertStringEndsWith('.webp', $variant->image_path);
        Storage::disk('public')->assertExists($variant->image_path);
        Storage::disk('public')->assertExists($legacyPath);

        $size = getimagesizefromstring(Storage::disk('public')->get($variant->image_path));
        $this->assertNotFalse($size);
        $this->assertLessThanOrEqual(640, max($size[0], $size[1]));
    }

    public function test_dry_run_does_not_change_the_database_or_file(): void
    {
        Storage::fake('public');
        $legacyPath = 'menu-variants/legacy.jpg';
        Storage::disk('public')->put(
            $legacyPath,
            UploadedFile::fake()->image('legacy.jpg', 800, 800)->getContent(),
        );
        $variant = $this->createVariant($legacyPath);

        $this->artisan('menu-images:convert-webp', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame($legacyPath, $variant->fresh()->image_path);
        Storage::disk('public')->assertExists($legacyPath);
    }

    private function createVariant(string $imagePath): MenuVariant
    {
        $menu = Menu::query()->create([
            'name' => 'Menu WebP '.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Varian',
            'image_path' => $imagePath,
            'price' => 10_000,
            'is_available' => true,
            'sort_order' => 0,
        ]);
    }
}
