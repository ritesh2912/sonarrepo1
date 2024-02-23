<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subscriptions')->insert([
            [
            'id' => 1,
            'name' => 'Platinum',
            'slug' => 'platinum',
            'is_platinum' => 1,
            'created_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'name' => 'Gold',
                'slug' => 'gold',
                'is_platinum' => 0,
                'created_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'name' => 'Silver',
                'slug' => 'silver',
                'is_platinum' => 0,
                'created_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'name' => 'Bronze',
                'slug' => 'bronze',
                'is_platinum' => 0,
                'created_at'=>date('Y-m-d H:i:s'),
            ]
        ]
        );

        DB::table('subscription_plans')->insert([
            [
            'id' => 1,
            'subscription_id' => 1,
            'name' => '12 Months Plan',
            'duration' => 12,
            'duration_days' => 30*12,
            'assignment_request'=>100,
            'file_download'=>100,
            'price'=>1200,
            'is_active'=>1,
            'created_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'subscription_id' => 2,
                'name' => '6 Months Plan',
                'duration' => 6,
                'duration_days' => 30*6,
                'assignment_request'=>80,
                'file_download'=>80,
                'price'=>700,
                'is_active'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'subscription_id' => 3,
                'name' => '3 Months Plan',
                'duration' => 3,
                'duration_days' => 30*3,
                'assignment_request'=>80,
                'file_download'=>50,
                'price'=>500,
                'is_active'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'subscription_id' => 4,
                'name' => '1 Month Plan',
                'duration' => 1,
                'duration_days' => 30*1,
                'assignment_request'=>10,
                'file_download'=>10,
                'price'=>200,
                'is_active'=>1,
                'created_at'=>date('Y-m-d H:i:s'),
            ]
        ]
        );
    }
}
