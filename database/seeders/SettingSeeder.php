<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Setting::updateOrCreate(
            ['key' => 'contribution_fee'],
            ['value' => '4000']
        );
        Setting::updateOrCreate(
            ['key' => 'installment_fee'],
            ['value' => '4000']
        );
    }
}
