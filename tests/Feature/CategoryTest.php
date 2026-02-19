<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    // ── Index ─────────────────────────────────────────────────

    public function test_index_returns_all_categories(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Retrieved successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug'],
                ],
            ]);
    }

    // ── Show ──────────────────────────────────────────────────

    public function test_show_returns_category_by_slug(): void
    {
        $category = Category::first();

        $response = $this->getJson("/api/categories/{$category->slug}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Retrieved successfully.',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
            ]);
    }

    public function test_show_returns_404_for_invalid_slug(): void
    {
        $response = $this->getJson('/api/categories/nonexistent-slug');

        $response->assertNotFound();
    }
}
