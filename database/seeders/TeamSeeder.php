<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $team = Team::firstOrCreate(
            ['slug' => 'latacunga'],
            ['name' => 'Latacunga', 'is_personal' => false],
        );

        $users = User::orderBy('id')->get();

        foreach ($users as $index => $user) {
            DB::table('team_members')->updateOrInsert(
                ['team_id' => $team->id, 'user_id' => $user->id],
                [
                    'role' => $index === 0 ? TeamRole::Owner->value : TeamRole::Member->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            DB::table('users')->where('id', $user->id)->update(['current_team_id' => $team->id]);
        }
    }
}
