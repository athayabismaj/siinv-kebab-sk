<?php

namespace Tests\Feature\Admin;

use App\Models\ApiToken;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuVariantImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_replace_and_delete_a_variant_image(): void
    {
        Storage::fake('public');

        $admin = $this->user('admin');
        $menu = $this->menu();

        $this->actingAs($admin)
            ->post(route('admin.menu-variants.store', $menu), [
                'name' => 'Jumbo',
                'cost_price' => 10000,
                'sell_price' => 18000,
                'sort_order' => 1,
                'is_available' => 1,
                'image' => UploadedFile::fake()->image('jumbo.jpg', 600, 600),
            ])
            ->assertRedirect(route('admin.menu-variants.index', $menu));

        $variant = MenuVariant::query()->where('menu_id', $menu->id)->firstOrFail();
        $oldImagePath = $variant->image_path;

        $this->assertNotNull($oldImagePath);
        Storage::disk('public')->assertExists($oldImagePath);

        $this->actingAs($admin)
            ->put(route('admin.menu-variants.update', [$menu, $variant]), [
                'name' => 'Jumbo Pedas',
                'cost_price' => 11000,
                'sell_price' => 19000,
                'sort_order' => 1,
                'is_available' => 1,
                'image' => UploadedFile::fake()->image('jumbo-baru.png', 600, 600),
            ])
            ->assertRedirect(route('admin.menu-variants.index', $menu));

        $variant->refresh();
        $newImagePath = $variant->image_path;

        $this->assertNotSame($oldImagePath, $newImagePath);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($newImagePath);

        $this->actingAs($admin)
            ->delete(route('admin.menu-variants.destroy', [$menu, $variant]))
            ->assertRedirect(route('admin.menu-variants.index', $menu));

        Storage::disk('public')->assertMissing($newImagePath);
    }

    public function test_pos_menu_api_returns_the_variant_image_url(): void
    {
        Storage::fake('public');

        $owner = $this->user('owner');
        $token = $this->apiToken($owner);
        $menu = $this->menu();
        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Reguler',
            'image_path' => 'menu-variants/reguler.webp',
            'price' => 15000,
            'cost_price' => 9000,
            'sell_price' => 15000,
            'is_available' => true,
            'sort_order' => 1,
        ]);
        Storage::disk('public')->put($variant->image_path, 'webp-image-content');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/menus');

        $response->assertOk();

        $apiVariant = collect($response->json('data.menus'))
            ->flatMap(fn (array $apiMenu) => $apiMenu['variants'])
            ->firstWhere('id', $variant->id);

        $this->assertNotNull($apiVariant);
        $this->assertSame($variant->image_url, $apiVariant['image_url']);

        $mediaResponse = $this->get(parse_url($variant->image_url, PHP_URL_PATH));
        $mediaResponse->assertOk();
        $this->assertStringContainsString(
            'max-age=604800',
            (string) $mediaResponse->headers->get('cache-control'),
        );
    }

    private function user(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function menu(): Menu
    {
        return Menu::query()->create([
            'name' => 'Kebab Gambar '.bin2hex(random_bytes(3)),
            'description' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function apiToken(User $user): string
    {
        $plainTextToken = 'variant_image_'.bin2hex(random_bytes(8));

        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'variant-image-test',
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDay(),
        ]);

        return $plainTextToken;
    }
}
