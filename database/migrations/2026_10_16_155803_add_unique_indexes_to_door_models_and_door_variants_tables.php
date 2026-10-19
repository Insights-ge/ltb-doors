<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The door variant import finds door models by name and keeps product codes apart, so both must be unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('door_models', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('door_variants', function (Blueprint $table) {
            $table->unique('product_code');
        });
    }

    public function down(): void
    {
        Schema::table('door_variants', function (Blueprint $table) {
            $table->dropUnique(['product_code']);
        });

        Schema::table('door_models', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
