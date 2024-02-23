<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('content_types')->insert(
            [
            'id' => 1,
            'name' => 'Books',
            'slug' => 'books',
            'created_at'=>date('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'name' => 'Notes',
                'slug' => 'notes',
                'created_at'=>date('Y-m-d H:i:s'),
            ]
        );
    }
}
