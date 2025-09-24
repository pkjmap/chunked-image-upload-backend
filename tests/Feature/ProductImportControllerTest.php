<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_imports_products_from_csv()
    {
        // Prepare a sample CSV content
        $csvContent = <<<CSV
sku,name,description,price
SKU001,Product One,Description One,100
SKU002,Product Two,Description Two,200
SKU003,Product Three,,150
CSV;

        // Create a temporary CSV file
        $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->postJson('/products/import', [
            'file' => $file,
        ]);

        // Assert response
        $response->assertStatus(200)
                 ->assertJson([
                     'total' => 3,
                     'imported' => 3,
                     'updated' => 0,
                     'skipped' => 0,
                 ]);

        // Assert database has the products
        $this->assertDatabaseHas('products', ['sku' => 'SKU001', 'name' => 'Product One', 'price' => 100]);
        $this->assertDatabaseHas('products', ['sku' => 'SKU002', 'name' => 'Product Two', 'price' => 200]);
        $this->assertDatabaseHas('products', ['sku' => 'SKU003', 'name' => 'Product Three', 'price' => 150]);
    }

    /** @test */
    public function it_skips_invalid_rows_in_csv()
    {
        $csvContent = <<<CSV
sku,name,description,price
SKU001,Product One,Description One,100
,Missing SKU,,50
SKU003,Product Three,,abc
CSV;

        $file = UploadedFile::fake()->createWithContent('products_invalid.csv', $csvContent);

        $response = $this->postJson('/products/import', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'total' => 3,
                     'imported' => 1,
                     'updated' => 0,
                     'skipped' => 2,
                 ]);

        $this->assertDatabaseHas('products', ['sku' => 'SKU001', 'name' => 'Product One']);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU003']); // invalid price
    }

    /** @test */
    public function it_updates_existing_products()
    {
        // Create an existing product
        Product::create([
            'sku' => 'SKU001',
            'name' => 'Old Name',
            'description' => 'Old Description',
            'price' => 50,
        ]);

        $csvContent = <<<CSV
sku,name,description,price
SKU001,New Name,New Description,100
CSV;

        $file = UploadedFile::fake()->createWithContent('update.csv', $csvContent);

        $response = $this->postJson('/products/import', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'total' => 1,
                     'imported' => 0,
                     'updated' => 1,
                     'skipped' => 0,
                 ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'SKU001',
            'name' => 'New Name',
            'description' => 'New Description',
            'price' => 100,
        ]);
    }
}
