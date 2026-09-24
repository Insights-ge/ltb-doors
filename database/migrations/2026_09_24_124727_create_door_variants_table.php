<?php

use App\Domains\Catalog\Enums\DoorColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('door_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('door_model_id')->constrained()->cascadeOnDelete();
            $table->string('product_code');
            $table->text('product_full_name');
            $table->enum('color', array_column(DoorColor::cases(), 'value'));
            $table->string('frame_design_code');
            $table->string('unique_code')->unique();
            $table->unsignedSmallInteger('height_range_min');
            $table->unsignedSmallInteger('height_range_max');
            $table->foreignId('side_profile_component_id')->constrained('components')->restrictOnDelete();
            $table->foreignId('top_rail_component_id')->constrained('components')->restrictOnDelete();
            $table->foreignId('bottom_rail_component_id')->constrained('components')->restrictOnDelete();
            $table->foreignId('partition_component_id')->nullable()->constrained('components')->restrictOnDelete();
            $table->foreignId('soft_close_mechanism_component_id')->constrained('components')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('door_variants');
    }
};
