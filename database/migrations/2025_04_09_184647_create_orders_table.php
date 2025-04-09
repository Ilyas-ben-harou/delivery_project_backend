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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->text('description')->nullable();
            $table->string('designation_product');
            $table->string('product_width')->nullable();
            $table->string('product_height')->nullable();
            $table->string('weight')->nullable();
            $table->date('collection_date');
            $table->string('status')->default('pending');
            $table->float('amount');
            $table->date('delivery_date')->nullable();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('livreur_id')->nullable()->constrained();
            $table->foreignId('customer_info_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
