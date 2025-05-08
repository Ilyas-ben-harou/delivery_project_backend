<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('livreurs', function (Blueprint $table) {
            $table->date('unavailable_start')->nullable();
            $table->date('unavailable_end')->nullable();
            $table->string('unavailable_reason', 255)->nullable();
        });
    }

    public function down()
    {
        Schema::table('livreurs', function (Blueprint $table) {
            $table->dropColumn(['unavailable_start', 'unavailable_end', 'unavailable_reason']);
        });
    }
};
