<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('system_settings')->insert([
            [
            'id' => 1,
            'teacher_cool_weightage'=> 10,
            'rate_per_assignment'=> 100,
            'hourly_rate_it_coding'=>100,
            'actual_word_present'=>10,
            'word_conversion_rate'=> 1,
            'created_at'=>date('Y-m-d H:i:s'),
            ]
        ]
        );
    }
}
