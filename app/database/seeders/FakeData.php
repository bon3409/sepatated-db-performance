<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FakeData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->seedUser();
        $this->seedOrder();
        
    }

    public function seedUser()
    {
        for ($i = 0; $i < 100; $i ++) {
            $data = [];
            for ($j = 0; $j < 100; $j ++) {
                $data[] = [
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'email_verified_at' => now(),
                    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                    'remember_token' => Str::random(10),
                ];
            }

            DB::table('users')->insert($data);
        }
    }

    public function seedOrder()
    {
        for ($i = 0; $i < 1000; $i ++) {
            $data = [];
            for ($j = 0; $j < 100; $j ++) {
                $data[] = [
                    'name' => fake()->name(),
                    'user_id' => fake()->numberBetween(0, 10000),
                    'amount'  => fake()->numberBetween(100, 99999),
                    'created_at' => fake()->dateTime(),
                    'updated_at' => fake()->dateTime()
                ];
            }

            DB::table('orders')->insert($data);
        }
    }
}
