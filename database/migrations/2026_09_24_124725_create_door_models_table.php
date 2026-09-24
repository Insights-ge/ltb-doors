<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('door_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('min_height');
            $table->unsignedSmallInteger('max_height');
            $table->unsignedSmallInteger('min_width');
            $table->unsignedSmallInteger('max_width');
            $table->unsignedSmallInteger('not_recommended_height_min')->nullable();
            $table->unsignedSmallInteger('not_recommended_height_max')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('door_models');
    }
};
