<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->float('current_quantity');          //cantidad o stock actual
            $table->float('profit_margin');             //margen de utilidad

            $table->float('purchase_price');            //precio de compra
            $table->float('package_purchase_price');    //precio de compra por paquete

            $table->float('sale_price');                //precio de venta
            $table->float('package_sale_price');        //precio de venta por paquete

            $table->string('sales_form');               //forma de venta

            $table->float('utility');                   //Utilidad
            $table->float('value_for_quantity');        //valor por cantidad
            $table->integer('minimum_unit');            //unidad minima
            $table->integer('maximum_unit');            //unidad maxima

            $table->integer('tax_product');             //Producto Gravado, 0 no : 1 si
            $table->date('expiration_date');            //Fecha de Vencimiento
            $table->integer('state');                   //estado
            $table->string('unit_measurement');         // unidad de medida
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
