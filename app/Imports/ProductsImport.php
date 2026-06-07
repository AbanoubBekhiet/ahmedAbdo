<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class ProductsImport implements ToCollection, WithHeadingRow
{
    protected array $errors = [];
    protected int $importedCount = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            // Filter out empty rows
            if (empty(array_filter($rowArray))) {
                continue;
            }

            $normalized = $this->normalizeRow($rowArray);

            // Row validation
            $rules = [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'unit_price' => 'required|numeric|min:0',
                'max_quantity' => 'required|integer|min:0',
                'unit' => 'nullable|in:شريط,كرتونة,علبة',
                'status' => 'nullable',
            ];

            // If category_id is present, validate it. Otherwise category or category_name must be present.
            if (!empty($normalized['category_id'])) {
                $rules['category_id'] = 'exists:categories,id';
            } else {
                $rules['category'] = 'required';
            }

            $validator = Validator::make($normalized, $rules, [
                'name.required' => 'اسم المنتج مطلوب في الصف ' . ($index + 2),
                'name.max' => 'اسم المنتج يجب ألا يزيد عن 255 حرف في الصف ' . ($index + 2),
                'unit_price.required' => 'سعر الوحدة مطلوب في الصف ' . ($index + 2),
                'unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقماً في الصف ' . ($index + 2),
                'max_quantity.required' => 'الكمية القصوى مطلوبة في الصف ' . ($index + 2),
                'max_quantity.integer' => 'الكمية القصوى يجب أن تكون عدداً صحيحاً في الصف ' . ($index + 2),
                'unit.in' => 'الوحدة يجب أن تكون شريط أو كرتونة أو علبة في الصف ' . ($index + 2),
                'category_id.exists' => 'رقم القسم غير موجود في الصف ' . ($index + 2),
                'category.required' => 'القسم أو رقم القسم مطلوب في الصف ' . ($index + 2),
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $index + 2,
                    'errors' => $validator->errors()->all()
                ];
                continue;
            }

            // Find or create category
            $categoryId = null;
            if (!empty($normalized['category_id'])) {
                $categoryId = $normalized['category_id'];
            } else {
                $categoryName = trim((string)$normalized['category']);
                $category = Category::firstOrCreate(['name' => $categoryName]);
                $categoryId = $category->id;
            }

            // Map status
            $status = true;
            if (isset($normalized['status'])) {
                $statusVal = strtolower(trim((string)$normalized['status']));
                if ($statusVal === '0' || $statusVal === 'false' || $statusVal === 'ملغي' || $statusVal === 'غير نشط' || $statusVal === 'inactive') {
                    $status = false;
                }
            }

            // Create or update product
            Product::updateOrCreate(
                ['name' => trim((string)$normalized['name'])],
                [
                    'category_id' => $categoryId,
                    'description' => $normalized['description'] ?? '',
                    'unit_price' => (float)$normalized['unit_price'],
                    'max_quantity' => (int)$normalized['max_quantity'],
                    'unit' => $normalized['unit'] ?? 'كرتونة',
                    'status' => $status,
                ]
            );

            $this->importedCount++;
        }
    }

    public function normalizeRow(array $row): array
    {
        $normalized = [];
        $mappings = [
            'name' => ['الاسم', 'اسم المنتج', 'اسم_المنتج', 'name'],
            'category_id' => ['رقم القسم', 'رقم_القسم', 'category_id'],
            'category' => ['القسم', 'اسم القسم', 'اسم_القسم', 'category', 'category_name'],
            'description' => ['الوصف', 'description'],
            'unit_price' => ['السعر', 'سعر الوحدة', 'سعر_الوحدة', 'unit_price', 'price'],
            'max_quantity' => ['الكمية القصوى', 'الكمية_القصوى', 'الكمية', 'max_quantity', 'quantity'],
            'unit' => ['الوحدة', 'unit'],
            'status' => ['الحالة', 'status'],
        ];

        foreach ($row as $key => $value) {
            $keyStr = strtolower(trim((string)$key));
            $found = false;
            foreach ($mappings as $targetKey => $aliases) {
                foreach ($aliases as $alias) {
                    $aliasStr = strtolower(trim($alias));
                    if ($keyStr === $aliasStr || str_contains($keyStr, $aliasStr)) {
                        $normalized[$targetKey] = $value;
                        $found = true;
                        break 2;
                    }
                }
            }
            if (!$found) {
                $normalized[$keyStr] = $value;
            }
        }
        return $normalized;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
