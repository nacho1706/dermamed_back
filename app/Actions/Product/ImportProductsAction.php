<?php

namespace App\Actions\Product;

use App\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportProductsAction
{
    /**
     * Executes the product import from a CSV/Excel file.
     *
     * @return array Returns an array with 'status' (int), 'message' (string), 'imported_count' (int) and 'errors' (array).
     */
    public function execute(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        // ── 0. Detect CSV delimiter (Latin Excel uses ; instead of ,) ────────
        $firstLine = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)[0] ?? '';
        $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen($path, 'r');

        // ── 1. Read & validate header ────────────────────────────────────────
        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);

            return [
                'status' => 422,
                'message' => 'El archivo está vacío o no tiene encabezados.',
                'imported_count' => 0,
                'errors' => [],
            ];
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $requiredColumns = ['name', 'price', 'stock', 'marca'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (! empty($missingColumns)) {
            fclose($handle);

            return [
                'status' => 422,
                'message' => 'Columnas requeridas faltantes: '.implode(', ', $missingColumns),
                'imported_count' => 0,
                'errors' => [],
            ];
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
            return [
                'status' => 422,
                'message' => 'El archivo no contiene filas de datos.',
                'imported_count' => 0,
                'errors' => [],
            ];
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
                // We map row error correctly
                foreach ($fieldMessages as $msg) {
                    $flatErrors[] = $msg;
                }
            }

            $errors = array_values(array_unique(array_merge($flatErrors, $inFileDuplicates)));

            return [
                'status' => 422,
                'message' => 'Errores de validación',
                'imported_count' => 0,
                'errors' => $errors,
            ];
        }

        // ── 4. Resolve foreign keys (case-insensitive) ──────────────────────
        $lookupErrors = [];

        // Pre-load all brands for case-insensitive matching
        $allBrands = Brand::pluck('id', DB::raw('LOWER(name)'))->toArray();

        $resolvedRows = [];
        foreach ($rows as $row) {
            $rowNum = $row['_row'];

            // Brand lookup
            $brandKey = strtolower(trim($row['marca']));
            $brandId = $allBrands[$brandKey] ?? null;
            if (! $brandId) {
                $lookupErrors[] = "Fila {$rowNum}: La marca '{$row['marca']}' no existe en el sistema.";
            }

            $resolvedRows[] = [
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price' => (float) $row['price'],
                'stock' => (int) $row['stock'],
                'min_stock' => isset($row['min_stock']) && ctype_digit(strval($row['min_stock']))
                    ? (int) $row['min_stock']
                    : 0,
                'brand_id' => $brandId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($lookupErrors)) {
            return [
                'status' => 422,
                'message' => 'Errores de validación',
                'imported_count' => 0,
                'errors' => $lookupErrors,
            ];
        }

        // ── 5. All valid → bulk insert inside a transaction ─────────────────
        DB::transaction(function () use ($resolvedRows) {
            DB::table('products')->insert($resolvedRows);
        });

        $count = count($resolvedRows);

        return [
            'status' => 200,
            'message' => "Importación exitosa: {$count} producto(s) importados correctamente.",
            'imported_count' => $count,
            'errors' => [],
        ];
    }
}
