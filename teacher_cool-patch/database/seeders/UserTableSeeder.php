<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $count = 10;

        User::factory()->count($count)->create();

        for($i = 0; $i< $count; $i++){
            DB::table('user_details')->insert([
                'id' => $i+1,
                'user_id' => $i+1,
                'gender' => 'male',
                'age' => rand(21, 49),
                'contact' => rand(7162100000, 9962113343),
                'city'=>'mohali',
                'state'=>'punjab',
                'country'=>'india',
                'qualification'=>'BA',
                'university'=>'PU',
                'created_at'=>date('Y-m-d H:i:s', rand(1662100000, 1662113343)),
            ]);
        }

        for($i = 0; $i< 5; $i++){
            DB::table('teacher_settings')->insert([
                'id' => $i+1,
                'user_id' => $i+1,
                'working_hours' => rand(4, 8),
                'expected_income' => rand(8, 20),
                'preferred_currency' => 'USD',
            ]);
        }
    }
}
