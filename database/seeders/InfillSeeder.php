<?php

namespace Database\Seeders;

use App\Enums\InfillTexture;
use App\Models\Infill;
use Illuminate\Database\Seeder;
use RuntimeException;

class InfillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $handle = fopen(database_path('seeders/data/infills.csv'), 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open infills CSV.');
        }

        try {
            if (fgetcsv($handle, escape: '\\') !== ['code', 'description', 'unique_code', 'visual_color', 'visual_texture']) {
                throw new RuntimeException('Invalid infills CSV header.');
            }

            Infill::query()->getConnection()->transaction(function () use ($handle): void {
                while (($row = fgetcsv($handle, escape: '\\')) !== false) {
                    if (count($row) !== 5 || in_array('', $row, true) || in_array(null, $row, true)) {
                        throw new RuntimeException('Each infill requires a code, description, and supplier unique code.');
                    }

                    if (preg_match('/\A#[0-9A-F]{6}\z/', $row[3]) !== 1 || InfillTexture::tryFrom($row[4]) === null) {
                        throw new RuntimeException('Each infill requires a valid visual colour and texture.');
                    }

                    Infill::query()->updateOrCreate(
                        ['unique_code' => $row[2]],
                        ['code' => $row[0], 'description' => $row[1], 'visual_color' => $row[3], 'visual_texture' => $row[4]],
                    );
                }
            });
        } finally {
            fclose($handle);
        }
    }
}
