<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EodSettlement extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'eod_settlements';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'settlement_date',
        'total_transactions',
        'total_amount',
        'total_pos_fee',
        'total_agent_fee',
        'total_profit',
        'total_refund_to_agent',
        'total_advance',
        'net_settlement',
        'settled_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settlement_date' => 'date',
        'total_transactions' => 'integer',
        'total_amount' => 'decimal:2',
        'total_pos_fee' => 'decimal:2',
        'total_agent_fee' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'total_refund_to_agent' => 'decimal:2',
        'total_advance' => 'decimal:2',
        'net_settlement' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the agent that owns the settlement.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Get the user who performed the settlement.
     */
    public function settler()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * Scope for specific agent.
     */
    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope for specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('settlement_date', $date);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('settlement_date', [$from, $to]);
    }

    /**
     * Create EOD settlement from transactions.
     * FIXED: Ensures only completed transactions are included.
     *
     * @param int $agentId
     * @param string $date
     * @param int $settledBy User ID
     * @return self
     */
    public static function createFromTransactions(int $agentId, string $date, int $settledBy): self
    {
        // Get ONLY completed transactions for the date
        $transactions = Transaction::where('agent_id', $agentId)
            ->where('status', 'Hoàn thành')
            ->whereDate('transaction_date', $date)
            ->get();

        return self::createFromTransactionCollection($agentId, $date, $transactions, $settledBy);
    }

    /**
     * Create EOD settlement from transaction collection.
     *
     * @param int $agentId
     * @param string $date
     * @param Collection $transactions
     * @param int $settledBy
     * @return self
     */
    public static function createFromTransactionCollection(
        int $agentId, 
        string $date, 
        Collection $transactions,
        int $settledBy
    ): self {
        $totals = [
            'total_transactions' => $transactions->count(),
            'total_amount' => 0,
            'total_pos_fee' => 0,
            'total_agent_fee' => 0,
            'total_profit' => 0,
            'total_refund_to_agent' => 0,
            'total_advance' => 0,
        ];

        foreach ($transactions as $tx) {
            $totals['total_amount'] += $tx->total_amount;
            $totals['total_pos_fee'] += $tx->pos_fee_amount;
            $totals['total_agent_fee'] += $tx->agent_fee_amount;
            $totals['total_profit'] += $tx->profit;
            $totals['total_refund_to_agent'] += $tx->refund_to_agent;
            $totals['total_advance'] += $tx->agent_advance;
        }

        $totals['net_settlement'] = $totals['total_refund_to_agent'] - $totals['total_advance'];

        return self::create([
            'agent_id' => $agentId,
            'settlement_date' => $date,
            'settled_by' => $settledBy,
            ...$totals,
        ]);
    }

    /**
     * Get settlement data for a specific date.
     * FIXED: Only includes completed transactions.
     *
     * @param int $agentId
     * @param string $date
     * @return array
     */
    public static function getSettlementData(int $agentId, string $date): array
    {
        // Get completed transactions
        $transactions = Transaction::where('agent_id', $agentId)
            ->where('status', 'Hoàn thành')
            ->whereDate('transaction_date', $date)
            ->get();

        if ($transactions->isEmpty()) {
            return [
                'total_transactions' => 0,
                'total_amount' => 0,
                'total_pos_fee' => 0,
                'total_agent_fee' => 0,
                'total_profit' => 0,
                'total_refund_to_agent' => 0,
                'total_advance' => 0,
                'net_settlement' => 0,
                'transactions' => [],
            ];
        }

        // Calculate totals
        $totals = [
            'total_transactions' => $transactions->count(),
            'total_amount' => (float) $transactions->sum('total_amount'),
            'total_pos_fee' => (float) $transactions->sum('pos_fee_amount'),
            'total_agent_fee' => (float) $transactions->sum('agent_fee_amount'),
            'total_profit' => (float) $transactions->sum('profit'),
            'total_refund_to_agent' => (float) $transactions->sum('refund_to_agent'),
            'total_advance' => (float) $transactions->sum('agent_advance'),
        ];

        $totals['net_settlement'] = $totals['total_refund_to_agent'] - $totals['total_advance'];

        // Format transaction list
        $txList = $transactions->map(function ($tx) {
            return [
                'id' => $tx->id,
                'transaction_id' => $tx->transaction_id,
                'customer_name' => $tx->customer_name,
                'amount' => (float) $tx->total_amount,
                'type' => $tx->transaction_type,
                'refund' => (float) $tx->refund_to_agent,
                'advance' => (float) $tx->agent_advance,
                'net_settlement' => (float) $tx->net_settlement,
                'status' => $tx->status,
            ];
        })->toArray();

        return [
            ...$totals,
            'transactions' => $txList,
        ];
    }

    /**
     * Get settlement history for an agent.
     *
     * @param int $agentId
     * @param int $limit
     * @return Collection
     */
    public static function getHistory(int $agentId, int $limit = 30): Collection
    {
        return self::where('agent_id', $agentId)
            ->with('settler:id,name,email')
            ->orderBy('settlement_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if settlement exists for date.
     *
     * @param int $agentId
     * @param string $date
     * @return bool
     */
    public static function existsForDate(int $agentId, string $date): bool
    {
        return self::where('agent_id', $agentId)
            ->whereDate('settlement_date', $date)
            ->exists();
    }

    /**
     * Get monthly summary.
     *
     * @param int $agentId
     * @param int $year
     * @param int $month
     * @return array
     */
    public static function getMonthlySummary(int $agentId, int $year, int $month): array
    {
        $settlements = self::where('agent_id', $agentId)
            ->whereYear('settlement_date', $year)
            ->whereMonth('settlement_date', $month)
            ->get();

        return [
            'total_days_settled' => $settlements->count(),
            'total_transactions' => $settlements->sum('total_transactions'),
            'total_amount' => (float) $settlements->sum('total_amount'),
            'total_profit' => (float) $settlements->sum('total_profit'),
            'total_advance' => (float) $settlements->sum('total_advance'),
            'net_settlement' => (float) $settlements->sum('net_settlement'),
        ];
    }
}
