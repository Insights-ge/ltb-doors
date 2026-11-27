<?php

namespace Database\Seeders;

use App\Enums\ComponentType;
use App\Models\Component;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OverlapWidthSeeder extends Seeder
{
    private const WIDTHS = [
        'ალდ4722' => 50,
        'გალუ4014' => 50,
        'გალუ4108' => 20,
        'გალუ4111' => 15,
        'გალუ4171' => 15,
        'გალუ4181' => 15,
        'გალუ4206' => 55,
        'გალუ4312' => 20,
        'გალუ4323' => 40,
        'გალუ4355' => 27,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::WIDTHS as $code => $width) {
                Component::query()
                    ->where('type', ComponentType::SideProfile)
                    ->where('code', $code)
                    ->whereNull('overlap_width')
                    ->update(['overlap_width' => $width]);
            }
        });
    }
}
