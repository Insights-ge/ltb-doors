<?php

namespace Database\Seeders;

use App\Enums\ComponentType;
use App\Models\Component;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HeightDeductionSeeder extends Seeder
{
    private const DEDUCTIONS = [
        'ალდ4722' => 30,
        'გალუ4014' => 38,
        'გალუ4108' => 38,
        'გალუ4111' => 38,
        'გალუ4171' => 38,
        'გალუ4181' => 38,
        'გალუ4206' => 38,
        'გალუ4312' => 38,
        'გალუ4323' => 38,
        'გალუ4355' => 38,
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::DEDUCTIONS as $code => $deduction) {
                Component::query()
                    ->where('type', ComponentType::SideProfile)
                    ->where('code', $code)
                    ->whereNull('height_deduction')
                    ->update(['height_deduction' => $deduction]);
            }
        });
    }
}
