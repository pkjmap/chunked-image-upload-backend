<?php
// app/Http/Controllers/ProductImportController.php
namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240'
        ]);

        $path = $request->file('file')->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_shift($data));

        $imported = $updated = $skipped = 0;

        foreach ($data as $row) {
            $row = array_combine($header, $row);

            $validator = Validator::make($row, [
                'sku' => 'required|string',
                'name' => 'required|string',
                'price' => 'nullable|numeric'
            ]);

            if ($validator->fails()) {
                $skipped++;
                continue;
            }

            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'] ?? '',
                    'price' => $row['price'] ?? 0,
                ]
            );

            $product->wasRecentlyCreated ? $imported++ : $updated++;
        }

        return response()->json([
            'total' => count($data),
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);
    }
}
