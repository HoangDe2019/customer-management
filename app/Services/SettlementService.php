<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\DailyAdvance;
use App\Models\EodSettlement;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    /**
     * Get EOD settlement data for an agent on a date (only completed transactions).
     * Mirrors Google Apps Script getEndOfDaySettlement logic.
     */
    public function calculateEndOfDaySettlement(Agent $agent, string $date): array
    {
        return EodSettlement::getSettlementData($agent->id, $date);
    }

    /**
     * Save EOD settlement to history and settle daily advances.
     * Mirrors saveEodSettlement + settleDailyAdvances in GAS.
     */
    public function saveEndOfDaySettlement(
        Agent $agent,
        string $date,
        array $settlementData,
        User $user
    ): EodSettlement {
        return DB::transaction(function () use ($agent, $date, $settlementData, $user) {
            $eod = EodSettlement::create([
                'agent_id' => $agent->id,
                'settlement_date' => $date,
                'total_transactions' => $settlementData['total_transactions'] ?? 0,
                'total_amount' => $settlementData['total_amount'] ?? 0,
                'total_pos_fee' => $settlementData['total_pos_fee'] ?? 0,
                'total_agent_fee' => $settlementData['total_agent_fee'] ?? 0,
                'total_profit' => $settlementData['total_profit'] ?? 0,
                'total_refund_to_agent' => $settlementData['total_refund_to_agent'] ?? 0,
                'total_advance' => $settlementData['total_advance'] ?? 0,
                'net_settlement' => $settlementData['net_settlement'] ?? 0,
                'settled_by' => $user->id,
            ]);

            $this->settleDailyAdvances($agent, $date);

            return $eod;
        });
    }

    /**
     * Get daily advance summary for an agent on a date.
     */
    public function getDailyAdvanceSummary(Agent $agent, string $date): array
    {
        $summary = DailyAdvance::getSummaryForDate($agent->id, $date);
        $advances = DailyAdvance::where('agent_id', $agent->id)
            ->whereDate('advance_date', $date)
            ->where('is_settled', false)
            ->with('transaction:id,transaction_id')
            ->get()
            ->unique('transaction_id');      // <<< FIX DUP HERE
            
        $transactions = $advances->map(fn ($a) => [
            'transaction_id' => $a->transaction->transaction_id ?? null,
            'amount' => (float) $a->advance_amount,
        ])->values()->toArray();
        return [
            'total_advance' => (float) $summary['total_amount'],
            'transactions' => $transactions,
            'settled_amount' => (float) ($summary['settled_amount'] ?? 0),
            'unsettled_amount' => (float) ($summary['unsettled_amount'] ?? 0),
        ];
    }

    /**
     * Mark all unsettled daily advances for agent+date as settled.
     */
    public function settleDailyAdvances(Agent $agent, string $date): array
    {
        return DailyAdvance::batchSettleForDate($agent->id, $date, 'EOD_' . time());
    }
}
