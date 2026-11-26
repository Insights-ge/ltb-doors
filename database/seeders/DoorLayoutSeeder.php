<?php

namespace Database\Seeders;

use App\Models\DoorModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DoorLayoutSeeder extends Seeder
{
    public function run(): void
    {
        $layouts = File::json(database_path('seeders/data/sliding-door-panel-layouts.json'), JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($layouts): void {
            foreach ($layouts as $name => $layout) {
                $model = DoorModel::query()->where('name', $name)->sole(['id']);

                DoorModel::query()->whereKey($model->id)->whereNull('panel_layout')
                    ->update(['panel_layout' => json_encode($layout, JSON_THROW_ON_ERROR)]);
            }
        });
    }
}
