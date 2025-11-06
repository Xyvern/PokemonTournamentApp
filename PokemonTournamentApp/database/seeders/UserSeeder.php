<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['username' => 'darren', 'password' => 'darren', 'nickname' => 'Darren', 'role' => 1],
            ['username' => 'chris', 'password' => 'chris', 'nickname' => 'Chris', 'role' => 1],
            ['username' => 'kevin', 'password' => 'kevin', 'nickname' => 'Kevin', 'role' => 1],
            ['username' => 'hans', 'password' => 'hans', 'nickname' => 'Hans', 'role' => 2],
            ['username' => 'hazel', 'password' => 'hazel', 'nickname' => 'Hazel', 'role' => 1],
            ['username' => 'angga', 'password' => 'angga', 'nickname' => 'Angga', 'role' => 1],
            ['username' => 'nando', 'password' => 'nando', 'nickname' => 'Nando', 'role' => 1],
            ['username' => 'harbi', 'password' => 'harbi', 'nickname' => 'Harbi', 'role' => 2],
            ['username' => 'jordan', 'password' => 'jordan', 'nickname' => 'Jordan', 'role' => 1],
            ['username' => 'ivan', 'password' => 'ivan', 'nickname' => 'Ivan', 'role' => 1],
            ['username' => 'febtry', 'password' => 'febtry', 'nickname' => 'Febtry', 'role' => 1],
            ['username' => 'fahmi', 'password' => 'fahmi', 'nickname' => 'Fahmi', 'role' => 1],
            ['username' => 'nanda', 'password' => 'nanda', 'nickname' => 'Nanda', 'role' => 1],
            ['username' => 'alif', 'password' => 'alif', 'nickname' => 'Alif', 'role' => 1],
            ['username' => 'marvin', 'password' => 'marvin', 'nickname' => 'Marvin', 'role' => 1],
            ['username' => 'marco', 'password' => 'marco', 'nickname' => 'Marco', 'role' => 1],
            ['username' => 'sean', 'password' => 'sean', 'nickname' => 'Sean', 'role' => 1],
            ['username' => 'kimi', 'password' => 'kimi', 'nickname' => 'Kimi', 'role' => 1],
            ['username' => 'adam', 'password' => 'adam', 'nickname' => 'Adam', 'role' => 1],
            ['username' => 'faishol', 'password' => 'faishol', 'nickname' => 'Faishol', 'role' => 1],
            ['username' => 'kent', 'password' => 'kent', 'nickname' => 'Kent', 'role' => 1],
            ['username' => 'maxi', 'password' => 'maxi', 'nickname' => 'Maxi', 'role' => 1],
            ['username' => 'aldo', 'password' => 'aldo', 'nickname' => 'Aldo', 'role' => 1],
            ['username' => 'sahril', 'password' => 'sahril', 'nickname' => 'Sahril', 'role' => 1],
            ['username' => 'morgan', 'password' => 'morgan', 'nickname' => 'Morgan', 'role' => 1],
            ['username' => 'daniel', 'password' => 'daniel', 'nickname' => 'Daniel', 'role' => 1],
            ['username' => 'ferdi', 'password' => 'ferdi', 'nickname' => 'Ferdi', 'role' => 1],
            ['username' => 'aaron', 'password' => 'aaron', 'nickname' => 'Aaron', 'role' => 1],
            ['username' => 'william', 'password' => 'william', 'nickname' => 'William', 'role' => 1],
            ['username' => 'rehan', 'password' => 'rehan', 'nickname' => 'Rehan', 'role' => 1],
            ['username' => 'jonathan', 'password' => 'jonathan', 'nickname' => 'Jonathan', 'role' => 1],
            ['username' => 'filbert', 'password' => 'filbert', 'nickname' => 'Filbert', 'role' => 1],
            ['username' => 'toha', 'password' => 'toha', 'nickname' => 'Toha', 'role' => 1],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert([
                'username' => $user['username'],
                'password' => Hash::make($user['password']), // Hash password for security
                'nickname' => $user['nickname'],
                'role' => $user['role'],
                'elo' => 1000,
                'matches_played' => 0,
                'matches_won' => 0,
                'matches_lost' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};