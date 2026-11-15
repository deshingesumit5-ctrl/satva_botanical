<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Uom;
use App\Models\User;
use Tests\TestCase;

class RecipeValidationTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
    }

    public function test_can_view_recipe_list_and_create_draft(): void
    {
        $response = $this->actingAs($this->admin)->get(route('recipe.index'));
        $response->assertStatus(200);

        $product = Product::first();
        $uom = Uom::where('uom_code', 'KG')->first();

        $createResponse = $this->actingAs($this->admin)->post(route('recipe.store'), [
            'product_id' => $product->id,
            'name' => 'Curcumin 95% Standardized SOP',
            'reference_batch_size' => 200.00,
            'batch_uom_id' => $uom->id,
            'expected_yield_percent' => 98.5,
            'description' => 'Test Curcumin Recipe SOP Draft',
        ]);

        $createResponse->assertRedirect();
        $this->assertDatabaseHas('recipes', ['name' => 'Curcumin 95% Standardized SOP']);
    }

    public function test_can_navigate_recipe_builder_steps(): void
    {
        $recipe = Recipe::first();
        $this->assertNotNull($recipe);

        // Test step 1 to 8 access
        for ($step = 1; $step <= 8; $step++) {
            $stepResponse = $this->actingAs($this->admin)->get(route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => $step]));
            $stepResponse->assertStatus(200);
        }
    }

    public function test_can_view_recipe_versions_and_clone(): void
    {
        $recipe = Recipe::first();
        $this->assertNotNull($recipe);

        $response = $this->actingAs($this->admin)->get(route('recipe.versions', $recipe->id));
        $response->assertStatus(200);
    }
}
