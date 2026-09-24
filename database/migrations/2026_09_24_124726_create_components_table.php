<?php

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->enum('type', array_column(ComponentType::cases(), 'value'));
            $table->string('code');
            $table->string('description');
            $table->enum('color', array_column(DoorColor::cases(), 'value'))->nullable();
            $table->string('unique_code')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
