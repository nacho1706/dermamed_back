<?php

namespace App\Http\Controllers;

use App\Factories\ProductFactory;
use App\Http\Requests\Product\ImportProductRequest;
use App\Http\Requests\Product\IndexProductsRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(IndexProductsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Product::query();

        if (isset($validated['name'])) {
            $query->where('name', 'like', '%'.$validated['name'].'%');
        }

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return ProductResource::collection($paginador);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $product = ProductFactory::fromRequest($validated);
        $product->save();

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product = ProductFactory::fromRequest($validated, $product);
        $product->save();

        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    public function import(ImportProductRequest $request)
    {
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return response()->json([
                'message' => 'El archivo está vacío o no tiene encabezados.',
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        // Normalize header names (trim + lowercase)
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $requiredColumns = ['name', 'price', 'stock'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (! empty($missingColumns)) {
            fclose($handle);

            return response()->json([
                'message' => 'Columnas requeridas faltantes: '.implode(', ', $missingColumns),
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        $imported = 0;
        $errors = [];
        $rowNumber = 1;

        DB::transaction(function () use ($handle, $header, &$imported, &$errors, &$rowNumber) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (count($row) !== count($header)) {
                    $errors[] = "Fila {$rowNumber}: número de columnas incorrecto.";

                    continue;
                }

                $data = array_combine($header, $row);

                // Validate required fields
                if (empty(trim($data['name'] ?? ''))) {
                    $errors[] = "Fila {$rowNumber}: el campo 'name' es obligatorio.";

                    continue;
                }
                if (! is_numeric($data['price'] ?? '')) {
                    $errors[] = "Fila {$rowNumber}: el campo 'price' debe ser numérico.";

                    continue;
                }
                if (! ctype_digit(strval($data['stock'] ?? ''))) {
                    $errors[] = "Fila {$rowNumber}: el campo 'stock' debe ser un número entero.";

                    continue;
                }

                Product::updateOrCreate(
                    ['name' => trim($data['name'])],
                    [
                        'description' => trim($data['description'] ?? '') ?: null,
                        'price' => (float) $data['price'],
                        'stock' => (int) $data['stock'],
                        'min_stock' => ctype_digit(strval($data['min_stock'] ?? '')) ? (int) $data['min_stock'] : 0,
                    ]
                );

                $imported++;
            }
        });

        fclose($handle);

        return response()->json([
            'message' => "Importación completada: {$imported} registro(s) procesados.",
            'imported_count' => $imported,
            'errors' => $errors,
        ]);
    }
}
