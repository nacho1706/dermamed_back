<?php

namespace App\Http\Controllers;

use App\Factories\ProductFactory;
use App\Http\Requests\Product\ImportProductRequest;
use App\Http\Requests\Product\IndexProductsRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(IndexProductsRequest $request)
    {
        $validated = $request->validated();
        $cantidad = $validated['cantidad'] ?? 10;
        $pagina = $validated['pagina'] ?? 1;

        $query = Product::with(['brand:id,name', 'category:id,name', 'subcategory:id,name,category_id']);

        // ── Trashed filter (archived products) ─────────────────────────
        if (isset($validated['trashed']) && $validated['trashed'] === 'true') {
            $query->onlyTrashed();
        }

        // ── Low stock server-side filter ─────────────────────────────────
        if (isset($validated['stock_status']) && $validated['stock_status'] === 'low') {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        // ── Text search (combined with other filters) ───────────────────
        if (isset($validated['name'])) {
            $query->where('name', 'ilike', '%'.$validated['name'].'%');
        }

        // ── Filter by category ──────────────────────────────────────────
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // ── Filter by brand ─────────────────────────────────────────────
        if (isset($validated['brand_id'])) {
            $query->where('brand_id', $validated['brand_id']);
        }

        // ── Filter by product type ──────────────────────────────────────
        if (isset($validated['is_for_sale'])) {
            $query->where('is_for_sale', filter_var($validated['is_for_sale'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($validated['is_supply'])) {
            $query->where('is_supply', filter_var($validated['is_supply'], FILTER_VALIDATE_BOOLEAN));
        }

        // ── Sorting ─────────────────────────────────────────────────────
        $sort = $validated['sort'] ?? null;
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'stock_asc' => $query->orderBy('stock', 'asc'),
            'stock_desc' => $query->orderBy('stock', 'desc'),
            default => $query->latest(),
        };

        $paginador = $query->paginate($cantidad, ['*'], 'page', $pagina);

        return ProductResource::collection($paginador);
    }

    public function kpis(IndexProductsRequest $request)
    {
        $validated = $request->validated();

        $query = Product::query();

        // ── Same trashed/stock filters as index ──────────────────────────
        if (isset($validated['trashed']) && $validated['trashed'] === 'true') {
            $query->onlyTrashed();
        }
        if (isset($validated['stock_status']) && $validated['stock_status'] === 'low') {
            $query->whereColumn('stock', '<=', 'min_stock');
        }

        // ── Same filters as index ─────────────────────────────────────────
        if (isset($validated['name'])) {
            $query->where('name', 'ilike', '%'.$validated['name'].'%');
        }
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }
        if (isset($validated['brand_id'])) {
            $query->where('brand_id', $validated['brand_id']);
        }
        if (isset($validated['is_for_sale'])) {
            $query->where('is_for_sale', filter_var($validated['is_for_sale'], FILTER_VALIDATE_BOOLEAN));
        }
        if (isset($validated['is_supply'])) {
            $query->where('is_supply', filter_var($validated['is_supply'], FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'total_products'  => (clone $query)->count(),
            'total_value'     => (float) (clone $query)->selectRaw('COALESCE(SUM(price * stock), 0) as total')->value('total'),
            'low_stock_count' => (clone $query)->whereColumn('stock', '<=', 'min_stock')->count(),
            'active_products' => (clone $query)->where('stock', '>', 0)->count(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $product = ProductFactory::fromRequest($validated);
        $product->save();

        return (new ProductResource($product->load(['brand:id,name', 'category:id,name', 'subcategory:id,name,category_id'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product)
    {
        return new ProductResource($product->load(['brand:id,name', 'category:id,name', 'subcategory:id,name,category_id']));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product = ProductFactory::fromRequest($validated, $product);
        $product->save();

        return new ProductResource($product->load(['brand:id,name', 'category:id,name', 'subcategory:id,name,category_id']));
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product archived successfully',
        ]);
    }

    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return new ProductResource($product->load(['brand:id,name', 'category:id,name', 'subcategory:id,name,category_id']));
    }

    public function adjustmentReasons()
    {
        return response()->json([
            'data' => [
                ['value' => 'sale', 'label' => 'Venta'],
                ['value' => 'expiry', 'label' => 'Caducidad'],
                ['value' => 'breakage', 'label' => 'Rotura'],
                ['value' => 'internal_use_adj', 'label' => 'Uso Interno'],
            ],
        ]);
    }

    public function import(ImportProductRequest $request)
    {
        $file = $request->file('file');
        $path = $file->getRealPath();

        // ── 0. Detect CSV delimiter (Latin Excel uses ; instead of ,) ────────
        $firstLine = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)[0] ?? '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen($path, 'r');

        // ── 1. Read & validate header ────────────────────────────────────────
        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);

            return response()->json([
                'message' => 'El archivo está vacío o no tiene encabezados.',
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $requiredColumns = ['name', 'price', 'stock', 'marca', 'categoria', 'subcategoria', 'venta', 'insumo'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (! empty($missingColumns)) {
            fclose($handle);

            return response()->json([
                'message' => 'Columnas requeridas faltantes: '.implode(', ', $missingColumns),
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        // ── 2. Read ALL rows into memory (no DB writes yet) ──────────────────
        $rows = [];
        $rowNumber = 1;
        while (($rawRow = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if (array_filter($rawRow, fn ($v) => trim($v) !== '') === []) {
                continue; // skip blank lines
            }
            $data = array_combine($header, array_pad($rawRow, count($header), ''));
            $data = array_map(fn ($v) => trim($v) === '' ? null : trim($v), $data);
            $data['_row'] = $rowNumber;
            $rows[] = $data;
        }
        fclose($handle);

        if (empty($rows)) {
            return response()->json([
                'message' => 'El archivo no contiene filas de datos.',
                'imported_count' => 0,
                'errors' => [],
            ], 422);
        }

        // ── 3. Build validation payload ─────────────────────────────────────
        $payload = ['rows' => $rows];

        $rules = [];
        $messages = [];
        foreach ($rows as $idx => $row) {
            $rowLabel = "Fila {$row['_row']}";
            $rules["rows.{$idx}.name"] = 'required|string|max:255';
            $rules["rows.{$idx}.price"] = 'required|numeric|min:0';
            $rules["rows.{$idx}.stock"] = 'required|integer|min:0';
            $rules["rows.{$idx}.min_stock"] = 'nullable|integer|min:0';
            $rules["rows.{$idx}.description"] = 'nullable|string|max:255';
            $rules["rows.{$idx}.marca"] = 'required|string|max:255';
            $rules["rows.{$idx}.categoria"] = 'required|string|max:255';
            $rules["rows.{$idx}.subcategoria"] = 'required|string|max:255';
            $rules["rows.{$idx}.venta"] = 'required|string|in:SI,NO,si,no,Si,No,sí,Sí';
            $rules["rows.{$idx}.insumo"] = 'required|string|in:SI,NO,si,no,Si,No,sí,Sí';

            $messages["rows.{$idx}.name.required"] = "{$rowLabel}: El campo 'name' es requerido.";
            $messages["rows.{$idx}.name.max"] = "{$rowLabel}: El nombre no puede superar los 255 caracteres.";
            $messages["rows.{$idx}.price.required"] = "{$rowLabel}: El campo 'price' es requerido.";
            $messages["rows.{$idx}.price.numeric"] = "{$rowLabel}: El campo 'price' debe ser numérico.";
            $messages["rows.{$idx}.price.min"] = "{$rowLabel}: El precio no puede ser negativo.";
            $messages["rows.{$idx}.stock.required"] = "{$rowLabel}: El campo 'stock' es requerido.";
            $messages["rows.{$idx}.stock.integer"] = "{$rowLabel}: El campo 'stock' debe ser un número entero (sin decimales).";
            $messages["rows.{$idx}.stock.min"] = "{$rowLabel}: El stock no puede ser negativo.";
            $messages["rows.{$idx}.min_stock.integer"] = "{$rowLabel}: El campo 'min_stock' debe ser un número entero (sin decimales).";
            $messages["rows.{$idx}.min_stock.min"] = "{$rowLabel}: El stock mínimo no puede ser negativo.";
            $messages["rows.{$idx}.marca.required"] = "{$rowLabel}: El campo 'marca' es requerido.";
            $messages["rows.{$idx}.categoria.required"] = "{$rowLabel}: El campo 'categoria' es requerido.";
            $messages["rows.{$idx}.subcategoria.required"] = "{$rowLabel}: El campo 'subcategoria' es requerido.";
            $messages["rows.{$idx}.venta.required"] = "{$rowLabel}: El campo 'venta' es requerido (SI/NO).";
            $messages["rows.{$idx}.venta.in"] = "{$rowLabel}: El campo 'venta' debe ser SI o NO.";
            $messages["rows.{$idx}.insumo.required"] = "{$rowLabel}: El campo 'insumo' es requerido (SI/NO).";
            $messages["rows.{$idx}.insumo.in"] = "{$rowLabel}: El campo 'insumo' debe ser SI o NO.";
        }

        // Unique name check across the database
        foreach ($rows as $idx => $row) {
            $name = $row['name'] ?? null;
            if ($name !== null) {
                $rules["rows.{$idx}.name"] .= '|unique:products,name';
                $messages["rows.{$idx}.name.unique"] = "Fila {$row['_row']}: El nombre '{$name}' ya está registrado en la base de datos.";
            }
        }

        $validator = Validator::make($payload, $rules, $messages);

        // Also check for duplicates within the file itself
        $nameCounts = array_count_values(
            array_filter(array_column($rows, 'name'), fn ($n) => $n !== null)
        );
        $inFileDuplicates = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? null;
            if ($name !== null && ($nameCounts[$name] ?? 0) > 1) {
                $inFileDuplicates[] = "Fila {$row['_row']}: El nombre '{$name}' está duplicado dentro del archivo.";
            }
        }

        if ($validator->fails() || ! empty($inFileDuplicates)) {
            $flatErrors = [];

            foreach ($validator->errors()->messages() as $field => $fieldMessages) {
                preg_match('/^rows\.(\d+)\./', $field, $matches);
                $rowIndex = isset($matches[1]) ? (int) $matches[1] : null;
                $rowNum = $rowIndex !== null ? ($rows[$rowIndex]['_row'] ?? ($rowIndex + 2)) : '?';
                $column = preg_replace('/^rows\.\d+\./', '', $field);

                foreach ($fieldMessages as $msg) {
                    $flatErrors[] = $msg;
                }
            }

            $errors = array_values(array_unique(array_merge($flatErrors, $inFileDuplicates)));

            return response()->json([
                'message' => 'Errores de validación',
                'imported_count' => 0,
                'errors' => $errors,
            ], 422);
        }

        // ── 4. Resolve foreign keys (case-insensitive) ──────────────────────
        $lookupErrors = [];

        // Pre-load all brands, categories, subcategories for case-insensitive matching
        $allBrands = Brand::pluck('id', DB::raw('LOWER(name)'))->toArray();
        $allCategories = Category::pluck('id', DB::raw('LOWER(name)'))->toArray();
        $allSubcategories = Subcategory::select('id', 'name', 'category_id')
            ->get()
            ->groupBy(fn ($s) => strtolower($s->name));

        $resolvedRows = [];
        foreach ($rows as $row) {
            $rowNum = $row['_row'];

            // Brand lookup
            $brandKey = strtolower(trim($row['marca']));
            $brandId = $allBrands[$brandKey] ?? null;
            if (! $brandId) {
                $lookupErrors[] = "Fila {$rowNum}: La marca '{$row['marca']}' no existe en el sistema.";
            }

            // Category lookup
            $categoryKey = strtolower(trim($row['categoria']));
            $categoryId = $allCategories[$categoryKey] ?? null;
            if (! $categoryId) {
                $lookupErrors[] = "Fila {$rowNum}: La categoría '{$row['categoria']}' no existe en el sistema.";
            }

            // Subcategory lookup (must match category)
            $subcategoryKey = strtolower(trim($row['subcategoria']));
            $subcategoryId = null;
            $matchingSubs = $allSubcategories[$subcategoryKey] ?? collect();
            if ($categoryId && $matchingSubs->isNotEmpty()) {
                $match = $matchingSubs->firstWhere('category_id', $categoryId);
                if ($match) {
                    $subcategoryId = $match->id;
                } else {
                    $lookupErrors[] = "Fila {$rowNum}: La subcategoría '{$row['subcategoria']}' no pertenece a la categoría '{$row['categoria']}'.";
                }
            } elseif ($categoryId) {
                $lookupErrors[] = "Fila {$rowNum}: La subcategoría '{$row['subcategoria']}' no existe en el sistema.";
            }

            $ventaNorm = strtolower(trim($row['venta'] ?? ''));
            $insumoNorm = strtolower(trim($row['insumo'] ?? ''));

            $resolvedRows[] = [
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price' => (float) $row['price'],
                'stock' => (int) $row['stock'],
                'min_stock' => isset($row['min_stock']) && ctype_digit(strval($row['min_stock']))
                    ? (int) $row['min_stock']
                    : 0,
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'subcategory_id' => $subcategoryId,
                'is_for_sale' => in_array($ventaNorm, ['si', 'sí']),
                'is_supply' => in_array($insumoNorm, ['si', 'sí']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($lookupErrors)) {
            return response()->json([
                'message' => 'Errores de validación',
                'imported_count' => 0,
                'errors' => $lookupErrors,
            ], 422);
        }

        // ── 5. All valid → bulk insert inside a transaction ─────────────────
        DB::transaction(function () use ($resolvedRows) {
            DB::table('products')->insert($resolvedRows);
        });

        $count = count($resolvedRows);

        return response()->json([
            'message' => "Importación exitosa: {$count} producto(s) importados correctamente.",
            'imported_count' => $count,
            'errors' => [],
        ]);
    }
}
