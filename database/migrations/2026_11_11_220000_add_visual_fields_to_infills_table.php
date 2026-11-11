<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('infills', function (Blueprint $table): void {
            $table->string('visual_color', 7)->default('#B8B8B8');
            $table->string('visual_texture')->default('solid');
        });
    }

    public function down(): void
    {
        Schema::table('infills', function (Blueprint $table): void {
            $table->dropColumn(['visual_color', 'visual_texture']);
        });
    }
};
