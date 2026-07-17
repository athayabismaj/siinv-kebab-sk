<?php

namespace Tests\Unit;

use App\Support\IngredientUnit;
use PHPUnit\Framework\TestCase;

class IngredientUnitTest extends TestCase
{
    public function test_base_quantities_are_converted_to_display_units_without_losing_decimals(): void
    {
        $this->assertSame(1.0, IngredientUnit::toDisplay('kg', 1000));
        $this->assertSame(0.5, IngredientUnit::toDisplay('kg', 500));
        $this->assertSame(500.0, IngredientUnit::toDisplay('g', 500));
        $this->assertSame(1.0, IngredientUnit::toDisplay('l', 1000));
        $this->assertSame(0.5, IngredientUnit::toDisplay('l', 500));
        $this->assertSame(1000.0, IngredientUnit::toDisplay('ml', 1000));
        $this->assertSame(16.0, IngredientUnit::toDisplay('pcs', 16));
        $this->assertSame(0.25, IngredientUnit::toDisplay('ml', 0.25));
        $this->assertSame(0.0, IngredientUnit::toDisplay('kg', 0));
    }

    public function test_display_quantities_are_converted_to_base_units_consistently(): void
    {
        $this->assertSame(1000.0, IngredientUnit::toBase('kg', 1));
        $this->assertSame(250.0, IngredientUnit::toBase('kg', 0.25));
        $this->assertSame(1000.0, IngredientUnit::toBase('l', 1));
        $this->assertSame(250.0, IngredientUnit::toBase('l', 0.25));
        $this->assertSame(250.0, IngredientUnit::toBase('g', 250));
        $this->assertSame(250.0, IngredientUnit::toBase('ml', 250));
        $this->assertSame(16.0, IngredientUnit::toBase('pcs', 16));
    }
}
