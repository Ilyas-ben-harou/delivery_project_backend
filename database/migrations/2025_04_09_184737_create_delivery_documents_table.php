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
        Schema::create('delivery_documents', function (Blueprint $table) {
            $table->id();
            $table->string('order_number');
            $table->foreign('order_number')->references('order_number')->on('orders');
            $table->string('designation_product');
            $table->string('customer_phone');
            $table->string('customer_address');
            $table->float('amount');
            $table->string('qr_code')->nullable();
            $table->foreignId('order_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_documents');
    }
};
