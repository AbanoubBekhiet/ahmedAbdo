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
        Schema::create('orders_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId("order_id")->constrained("orders")->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId("product_id")->constrained("products")->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer("number_of_units")->default(0);
            $table->decimal("unit_price",10,2);
            $table->decimal("total_price",10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_products');
    }
};
