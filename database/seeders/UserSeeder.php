<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'owner',
            'email' => 'owner@example.com',
            'role' => 'owner',
            'rfid_id' => '177013',
            'nomor_telepon' => '083897135235',
            'password' => Hash::make('Password123'),
        ]);
    }
}