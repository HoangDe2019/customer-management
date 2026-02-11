<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAdvance extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'daily_advances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_id',
        'agent_id',
        'advance_date',
        'advance_amount',
        'is_settled',
        'settled_at',
        'settlement_transaction_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'advance_date' => 'date',
        'advance_amount' => 'decimal:2',
        'is_settled' => 'boolean',
        'settled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the transaction that owns the daily advance.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /**
     * Get the agent that owns the daily advance.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Scope for unsettled advances.
     */
    public function scopeUnsettled($query)
    {
        return $query->where('is_settled', false);
    }

    /**
     * Scope for settled advances.
     */
    public function scopeSettled($query)
    {
        return $query->where('is_settled', true);
    }

    /**
     * Scope for specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('advance_date', $date);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('advance_date', [$from, $to]);
    }

    /**
     * Scope for specific agent.
     */
    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Settle this advance.
     *
     * @param string|null $settlementTransactionId
     * @return bool
     */
    public function settle(?string $settlementTransactionId = null): bool
    {
        $this->is_settled = true;
        $this->settled_at = now();
        $this->settlement_transaction_id = $settlementTransactionId ?? 'SETTLEMENT_' . time();
        
        return $this->save();
    }

    /**
     * Unsettle this advance.
     *
     * @return bool
     */
    public function unsettle(): bool
    {
        $this->is_settled = false;
        $this->settled_at = null;
        $this->settlement_transaction_id = null;
        
        return $this->save();
    }

    /**
     * Get summary for a specific agent and date.
     *
     * @param int $agentId
     * @param string $date
     * @return array
     */
    public static function getSummaryForDate(int $agentId, string $date): array
    {
        $advances = self::where('agent_id', $agentId)
            ->whereDate('advance_date', $date)
            ->get();

        $total = $advances->sum('advance_amount');
        $settled = $advances->where('is_settled', true)->sum('advance_amount');
        $unsettled = $advances->where('is_settled', false)->sum('advance_amount');

        return [
            'total_advances' => $advances->count(),
            'total_amount' => (float) $total,
            'settled_amount' => (float) $settled,
            'unsettled_amount' => (float) $unsettled,
            'advances' => $advances,
        ];
    }

    /**
     * Batch settle advances for a date.
     *
     * @param int $agentId
     * @param string $date
     * @param string|null $settlementTransactionId
     * @return array
     */
    public static function batchSettleForDate(int $agentId, string $date, ?string $settlementTransactionId = null): array
    {
        $settlementId = $settlementTransactionId ?? 'EOD_' . time();
        
        $advances = self::where('agent_id', $agentId)
            ->whereDate('advance_date', $date)
            ->where('is_settled', false)
            ->get();

        $count = 0;
        $totalAmount = 0;

        foreach ($advances as $advance) {
            if ($advance->settle($settlementId)) {
                $count++;
                $totalAmount += $advance->advance_amount;
            }
        }

        return [
            'settled_count' => $count,
            'total_amount' => (float) $totalAmount,
            'settlement_id' => $settlementId,
        ];
    }
}
