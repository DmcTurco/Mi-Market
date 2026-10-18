<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Productos de ejemplo de una bodega para probar el punto de venta.
 * Incluye productos por kilo, algunos casi agotados (aviso de stock bajo), uno sin stock y uno exonerado.
 * Se puede correr varias veces: no duplica productos.
 */
class ProductSeeder extends Seeder
{
    public function run(StockService $stock): void
    {
        $company = Company::find(1);
        if (!$company) {
            return;
        }

        // [categoría, nombre, código de barras, unidad, costo, precio, stock, mínimo, IGV]
        $products = [
            ['Abarrotes', 'Arroz Costeño 1kg',            '7751271011010', 'unidad', 3.80, 4.50, 40, 10, '10'],
            ['Abarrotes', 'Azúcar rubia',                  null,            'kg',     2.90, 3.50, 25, 5,  '10'],
            ['Abarrotes', 'Aceite Primor 1L',              '7751271021019', 'unidad', 8.50, 10.20, 18, 6, '10'],
            ['Abarrotes', 'Fideos Don Vittorio 500g',      '7751271031018', 'unidad', 2.60, 3.20, 30, 10, '10'],
            ['Abarrotes', 'Atún Florida 170g',             '7751271041017', 'unidad', 4.80, 5.90, 4,  6,  '10'],
            ['Abarrotes', 'Lentejas',                      null,            'kg',     5.20, 6.50, 12, 3,  '10'],
            ['Bebidas',   'Inca Kola 500ml',               '7750182000123', 'unidad', 1.90, 2.50, 48, 12, '10'],
            ['Bebidas',   'Coca Cola 1.5L',                '7750182000246', 'unidad', 4.60, 5.80, 20, 6,  '10'],
            ['Bebidas',   'Agua San Luis 625ml',           '7750182000369', 'unidad', 0.90, 1.50, 36, 12, '10'],
            ['Bebidas',   'Cerveza Pilsen 630ml',          '7750182000482', 'unidad', 5.00, 6.50, 0,  12, '10'],
            ['Lácteos',   'Leche Gloria tarro 400g',       '7751271051016', 'unidad', 3.60, 4.20, 60, 24, '10'],
            ['Lácteos',   'Yogurt Gloria fresa 1L',        '7751271061015', 'unidad', 5.40, 6.80, 8,  4,  '10'],
            ['Lácteos',   'Queso fresco',                  null,            'kg',     16.00, 20.00, 5, 1,  '10'],
            ['Lácteos',   'Huevos (unidad)',               null,            'unidad', 0.45, 0.60, 120, 30, '20'],
            ['Limpieza',  'Detergente Bolívar 500g',       '7751271071014', 'unidad', 5.80, 7.20, 15, 5,  '10'],
            ['Limpieza',  'Lejía Clorox 1L',               '7751271081013', 'unidad', 3.10, 4.00, 2,  4,  '10'],
            ['Limpieza',  'Papel higiénico Suave x4',      '7751271091012', 'unidad', 5.50, 6.90, 22, 6,  '10'],
            ['Snacks',    'Papas Lays clásicas 42g',       '7750182000505', 'unidad', 1.30, 1.80, 30, 10, '10'],
            ['Snacks',    'Galletas Soda Field',           '7750182000628', 'unidad', 0.70, 1.00, 50, 15, '10'],
            ['Snacks',    'Chocolate Sublime',             '7750182000741', 'unidad', 1.60, 2.20, 3,  10, '10'],
            [null,        'Pan francés',                   null,            'unidad', 0.15, 0.20, 100, 0, '10'],
            [null,        'Bolsa plástica',                null,            'unidad', 0.05, 0.10, 200, 50, '10'],
        ];

        DB::transaction(function () use ($company, $products, $stock) {
            foreach ($products as [$categoryName, $name, $barcode, $unit, $cost, $price, $initial, $min, $igv]) {
                $category = $categoryName
                    ? $company->categories()->firstOrCreate(['name' => $categoryName], ['active' => true])
                    : null;

                $product = $company->products()->firstOrCreate(['name' => $name], [
                    'category_id'     => $category?->id,
                    'barcode'         => $barcode,
                    'unit'            => $unit,
                    'purchase_price'  => $cost,
                    'sale_price'      => $price,
                    'min_stock'       => $min,
                    'igv_affectation' => $igv,
                    'stock'           => 0,
                ]);

                // El stock inicial queda en el historial, igual que al crear un producto desde el panel
                if ($product->wasRecentlyCreated && $initial > 0) {
                    $stock->move($product->id, $initial, StockMovement::ADJUSTMENT, null, 'Stock inicial');
                }
            }
        });
    }
}
