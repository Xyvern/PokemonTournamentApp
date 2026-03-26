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
            ['username' => 'hans', 'password' => 'hans', 'nickname' => 'Hans', 'role' => 2],
            ['username' => 'harbi', 'password' => 'harbi', 'nickname' => 'Harbi', 'role' => 2],
            ['username' => 'darren', 'password' => 'darren', 'nickname' => 'Darren', 'role' => 1],
            ['username' => 'chris', 'password' => 'chris', 'nickname' => 'Chris', 'role' => 1],
            ['username' => 'kevin', 'password' => 'kevin', 'nickname' => 'Kevin', 'role' => 1],
            ['username' => 'hazel', 'password' => 'hazel', 'nickname' => 'Hazel', 'role' => 1],
            ['username' => 'angga', 'password' => 'angga', 'nickname' => 'Angga', 'role' => 1],
            ['username' => 'nando', 'password' => 'nando', 'nickname' => 'Nando', 'role' => 1],
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
            ['username' => 'ken', 'password' => 'ken', 'nickname' => 'Ken', 'role' => 1],
            ['username' => 'indra', 'password' => 'indra', 'nickname' => 'Indra', 'role' => 1],
            ['username' => 'nico', 'password' => 'nico', 'nickname' => 'Nico', 'role' => 1],
            ['username' => 'dwi', 'password' => 'dwi', 'nickname' => 'Dwi', 'role' => 1],
            ['username' => 'ivander', 'password' => 'ivander', 'nickname' => 'Ivander', 'role' => 1],
            ['username' => 'kholid', 'password' => 'kholid', 'nickname' => 'Kholid', 'role' => 1],
            ['username' => 'yohanes', 'password' => 'yohanes', 'nickname' => 'Yohanes', 'role' => 1],
            ['username' => 'primus', 'password' => 'primus', 'nickname' => 'Primus', 'role' => 1],
            ['username' => 'raymond', 'password' => 'raymond', 'nickname' => 'Raymond', 'role' => 1],
            ['username' => 'stephen', 'password' => 'stephen', 'nickname' => 'Stephen', 'role' => 1],
            ['username' => 'rendy', 'password' => 'rendy', 'nickname' => 'Rendy', 'role' => 1],
            ['username' => 'rio', 'password' => 'rio', 'nickname' => 'Rio', 'role' => 1],
            ['username' => 'ten', 'password' => 'ten', 'nickname' => 'Ten', 'role' => 1],
            ['username' => 'huann', 'password' => 'huann', 'nickname' => 'Huann', 'role' => 1],
            ['username' => 'vianney', 'password' => 'vianney', 'nickname' => 'Vianney', 'role' => 1],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert([
                'username' => $user['username'],
                'password' => Hash::make($user['password']),
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