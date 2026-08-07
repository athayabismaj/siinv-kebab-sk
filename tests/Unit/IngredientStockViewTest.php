<?php

namespace Tests\Unit;

use App\Models\Ingredient;
use App\Support\IngredientStockView;
use PHPUnit\Framework\TestCase;

class IngredientStockViewTest extends TestCase
{
    public function test_fractional_piece_residue_is_presented_as_zero_and_out_of_stock(): void
    {
        $ingredient = new Ingredient([
            'name' => 'Patty',
            'stock' => 0.04,
            'minimum_stock' => 10,
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 10,
        ]);

        $view = IngredientStockView::fromIngredient($ingredient);

        $this->assertSame('0', $view['stock_text']);
        $this->assertTrue($view['is_out']);
        $this->assertFalse($view['is_low']);
        $this->assertNull($view['stock_pack_label']);
    }

    public function test_loose_whole_pieces_remain_valid_without_a_confusing_zero_pack_label(): void
    {
        $ingredient = new Ingredient([
            'name' => 'Patty',
            'stock' => 2,
            'minimum_stock' => 10,
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 10,
        ]);

        $view = IngredientStockView::fromIngredient($ingredient);

        $this->assertSame('2', $view['stock_text']);
        $this->assertFalse($view['is_out']);
        $this->assertTrue($view['is_low']);
        $this->assertNull($view['stock_pack_label']);
        $this->assertSame('1 pack = 10 pcs', $view['pack_info_label']);
    }
}
