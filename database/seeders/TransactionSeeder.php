<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $testUser = User::where('email', 'test@example.com')->first();
        $user = $admin ?? $testUser;
        if (!$user) {
            return;
        }

        $defaultAgent = Agent::where('agent_id', 'Khách hàng')->first();
        $agent2 = Agent::where('agent_id', 'Đại lý 1')->first();
        $agents = array_filter([$defaultAgent, $agent2]);

        $samples = [
            [
                'customer_name' => 'Nguyễn Văn A',
                'cccd_number' => '001099001234',
                'total_amount' => 10000000,
                'transaction_type' => 'Đáo',
                'pos_fee_percent' => 1.067,
                'agent_fee_percent' => 1.3,
                'agent_advance' => 0,
                'status' => 'Hoàn thành',
            ],
            [
                'customer_name' => 'Trần Thị B',
                'cccd_number' => '001099005678',
                'total_amount' => 5000000,
                'transaction_type' => 'Rút',
                'pos_fee_percent' => 1.067,
                'agent_fee_percent' => 1.3,
                'agent_advance' => 500000,
                'status' => 'Hoàn thành',
            ],
            [
                'customer_name' => 'Lê Văn C',
                'cccd_number' => null,
                'total_amount' => 20000000,
                'transaction_type' => 'Đáo',
                'pos_fee_percent' => 1.067,
                'agent_fee_percent' => 1.3,
                'agent_advance' => 0,
                'status' => 'Chờ duyệt',
            ],
        ];

        foreach ($agents as $agent) {
            if (!$agent) {
                continue;
            }
            foreach ($samples as $index => $sample) {
                $amount = (float) $sample['total_amount'];
                $posPct = (float) $sample['pos_fee_percent'];
                $agentPct = (float) $sample['agent_fee_percent'];
                $advance = (float) ($sample['agent_advance'] ?? 0);
                $posFee = round($amount * ($posPct / 100), 2);
                $agentFee = round($amount * ($agentPct / 100), 2);
                $profit = round($agentFee - $posFee, 2);
                $refund = round($amount - $agentFee, 2);
                $netSettlement = round($refund - $advance, 2);

                Transaction::firstOrCreate(
                    [
                        'transaction_id' => 'SEED_' . $agent->id . '_' . $index,
                    ],
                    [
                        'agent_id' => $agent->id,
                        'user_id' => $user->id,
                        'customer_name' => $sample['customer_name'],
                        'cccd_number' => $sample['cccd_number'],
                        'total_amount' => $amount,
                        'transaction_type' => $sample['transaction_type'],
                        'pos_fee_percent' => $posPct,
                        'agent_fee_percent' => $agentPct,
                        'pos_fee_amount' => $posFee,
                        'agent_fee_amount' => $agentFee,
                        'profit' => $profit,
                        'refund_to_agent' => $refund,
                        'agent_advance' => $advance,
                        'net_settlement' => $netSettlement,
                        'status' => $sample['status'],
                        'transaction_date' => Carbon::now()->subDays(rand(0, 5)),
                    ]
                );
            }
        }
    }
}
