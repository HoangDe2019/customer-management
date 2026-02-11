<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : null;

        $defaultAgent = Agent::firstOrCreate(
            ['agent_id' => 'Khách hàng'],
            [
                'name' => 'Khách hàng (Mặc định)',
                'status' => 'Active',
                'allowed_users' => [],
                'created_by' => $adminId,
            ]
        );

        $agent2 = Agent::firstOrCreate(
            ['agent_id' => 'Đại lý 1'],
            [
                'name' => 'Đại lý 1',
                'status' => 'Active',
                'allowed_users' => ['agent1@example.com', 'test@example.com'],
                'created_by' => $adminId,
            ]
        );

        if ($admin) {
            $agent2->users()->syncWithoutDetaching([$admin->id]);
        }
        $agent1User = User::where('email', 'agent1@example.com')->first();
        if ($agent1User) {
            $agent2->users()->syncWithoutDetaching([$agent1User->id]);
        }
        $testUser = User::where('email', 'test@example.com')->first();
        if ($testUser) {
            $agent2->users()->syncWithoutDetaching([$testUser->id]);
            $defaultAgent->users()->syncWithoutDetaching([$testUser->id]);
        }
    }
}
