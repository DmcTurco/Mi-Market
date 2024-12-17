<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'category_id',
        'name',
        'current_quantity',
        'profit_margin',
        'purchase_price',
        'package_purchase_price',
        'sale_price',
        'package_sale_price',
        'sales_form',
        'utility',
        'value_for_quantity',
        'minimum_unit',
        'maximum_unit',
        'tax_product',
        'expiration_date',
        'state',
        'unit_measurement',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


}
