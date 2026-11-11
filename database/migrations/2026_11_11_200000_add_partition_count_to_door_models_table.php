<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('door_models', function (Blueprint $table): void {
            $table->unsignedTinyInteger('partition_count')->default(1)->after('max_width');
        });
    }

    public function down(): void
    {
        Schema::table('door_models', function (Blueprint $table): void {
            $table->dropColumn('partition_count');
        });
    }
};
