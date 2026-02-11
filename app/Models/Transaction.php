<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    /** @var string */
    protected $table = 'transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_id',
        'agent_id',
        'user_id',
        'customer_name',
        'cccd_number',
        'total_amount',
        'transaction_type',
        'pos_fee_percent',
        'agent_fee_percent',
        'pos_fee_amount',
        'agent_fee_amount',
        'profit',
        'refund_to_agent',
        'agent_advance',
        'net_settlement',
        'status',
        'transaction_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'pos_fee_percent' => 'decimal:3',
        'agent_fee_percent' => 'decimal:3',
        'pos_fee_amount' => 'decimal:2',
        'agent_fee_amount' => 'decimal:2',
        'profit' => 'decimal:2',
        'refund_to_agent' => 'decimal:2',
        'agent_advance' => 'decimal:2',
        'net_settlement' => 'decimal:2',
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_completed', 'formatted_amount', 'status_color'];

    /**
     * Get the agent that owns the transaction.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Get the user who created the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the daily advances for this transaction.
     */
    public function dailyAdvances()
    {
        return $this->hasMany(DailyAdvance::class, 'transaction_id');
    }

    /**
     * Get the transaction logs for this transaction.
     */
    public function transactionLogs()
    {
        return $this->hasMany(TransactionLog::class, 'transaction_id');
    }

    /**
     * Alias for transactionLogs (used by API and logs).
     */
    public function logs()
    {
        return $this->hasMany(TransactionLog::class, 'transaction_id');
    }

    /**
     * Get the MoMo payment for this transaction.
     */
    public function momoPayment()
    {
        return $this->hasOne(MomoPayment::class, 'transaction_id');
    }

    /**
     * Scope a query to only include completed transactions.
     * CRITICAL: This is the main filter for statistics.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Hoàn thành');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by transaction type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to filter by agent.
     */
    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Chờ duyệt');
    }

    /**
     * Scope for approved transactions.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'Đã duyệt');
    }

    /**
     * Scope for processing transactions.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'Đang xử lý');
    }

    /**
     * Check if transaction is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'Hoàn thành';
    }

    /**
     * Get formatted amount for display.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->total_amount, 0, ',', '.') . ' ₫';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Hoàn thành' => 'success',
            'Đã duyệt' => 'primary',
            'Đang xử lý', 'Chờ DR' => 'warning',
            'Thất bại', 'Đã hủy' => 'error',
            default => 'info',
        };
    }

    /**
     * Calculate all fees and profit.
     * This preserves the original business logic exactly.
     */
    public function calculateFees(): void
    {
        // Calculate POS fee (VNĐ)
        // Calculate POS fee, agent fee, profit, refund, net settlement (VNĐ)
        $this->pos_fee_amount = (float) ($this->total_amount * ($this->pos_fee_percent / 100));
        $this->agent_fee_amount = (float) ($this->total_amount * ($this->agent_fee_percent / 100));
        $this->profit = (float) ($this->agent_fee_amount - $this->pos_fee_amount);
        $this->refund_to_agent = (float) ($this->total_amount - $this->agent_fee_amount);
        $this->net_settlement = (float) ($this->refund_to_agent - $this->agent_advance);
    }

    /**
     * Generate unique transaction ID.
     *
     * @return string
     */
    public static function generateTransactionId(): string
    {
        $timestamp = now()->timestamp;
        $random = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        
        return 'TXN' . $timestamp . $random;
    }

    /**
     * Generate unique customer ID.
     *
     * @return string
     */
    public static function generateCustomerId(): string
    {
        $timestamp = now()->timestamp;
        $random = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        
        return 'KH_' . $timestamp . $random;
    }

    /**
     * Validate CCCD uniqueness.
     * FIXED: Original validation was commented out.
     *
     * @param string $cccdNumber
     * @param int $agentId
     * @param string|null $currentTransactionId
     * @return array
     */
    public static function validateCCCD(string $cccdNumber, int $agentId, ?string $currentTransactionId = null): array
    {
        // Skip validation if CCCD is empty
        if (empty($cccdNumber)) {
            return ['valid' => true];
        }

        // Check length - must be exactly 12 digits
        if (strlen($cccdNumber) != 12 || !ctype_digit($cccdNumber)) {
            return [
                'valid' => false,
                'message' => 'CCCD phải là 12 chữ số'
            ];
        }

        // Check for duplicates in same agent
        $query = self::where('agent_id', $agentId)
            ->where('cccd_number', $cccdNumber);

        if ($currentTransactionId) {
            $query->where('transaction_id', '!=', $currentTransactionId);
        }

        if ($query->exists()) {
            return [
                'valid' => false,
                'message' => 'CCCD đã tồn tại trong hệ thống'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Update transaction status with logging.
     *
     * @param string $newStatus
     * @param int $userId
     * @return bool
     */
    public function updateStatus(string $newStatus, int $userId): bool
    {
        $oldStatus = $this->status;
        
        // Validate status
        $validStatuses = ['Chờ duyệt', 'Đã duyệt', 'Đang xử lý', 'Chờ DR', 'Hoàn thành', 'Thất bại', 'Đã hủy'];
        if (!in_array($newStatus, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$newStatus}");
        }

        $this->status = $newStatus;
        $saved = $this->save();

        if ($saved) {
            // Log the status change
            TransactionLog::create([
                'transaction_id' => $this->id,
                'agent_id' => $this->agent_id,
                'user_id' => $userId,
                'action' => 'status_changed',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'metadata' => json_encode([
                    'changed_at' => now()->toDateTimeString(),
                    'ip_address' => request()->ip(),
                ]),
            ]);
        }

        return $saved;
    }

    /**
     * Approve the transaction.
     */
    public function approve(int $userId): bool
    {
        return $this->updateStatus('Đã duyệt', $userId);
    }

    /**
     * Mark transaction as processing.
     */
    public function markAsProcessing(int $userId): bool
    {
        return $this->updateStatus('Đang xử lý', $userId);
    }

    /**
     * Complete the transaction.
     */
    public function complete(int $userId): bool
    {
        return $this->updateStatus('Hoàn thành', $userId);
    }

    /**
     * Cancel the transaction.
     */
    public function cancel(int $userId): bool
    {
        return $this->updateStatus('Đã hủy', $userId);
    }

    /**
     * Mark transaction as failed.
     */
    public function fail(int $userId): bool
    {
        return $this->updateStatus('Thất bại', $userId);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate IDs and calculate fees before creating
        static::creating(function ($transaction) {
            // Generate transaction ID if not set
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = self::generateTransactionId();
            }

            // Auto-generate customer name if empty
            if (empty($transaction->customer_name)) {
                $customerId = self::generateCustomerId();
                $transaction->customer_name = $customerId;
            }

            // Set transaction date if not set
            if (empty($transaction->transaction_date)) {
                $transaction->transaction_date = now();
            }

            // Set default fee percentages if not set
            if (empty($transaction->pos_fee_percent)) {
                $transaction->pos_fee_percent = 1.067;
            }
            if (empty($transaction->agent_fee_percent)) {
                $transaction->agent_fee_percent = 1.300;
            }
            if (empty($transaction->agent_advance)) {
                $transaction->agent_advance = 0;
            }

            // Calculate fees
            $transaction->calculateFees();

            // Set user_id if authenticated and not set
            if (empty($transaction->user_id) && \Illuminate\Support\Facades\Auth::hasUser()) {
                $transaction->user_id = \Illuminate\Support\Facades\Auth::id();
            }
        });

        // Recalculate fees when amounts change
        static::updating(function ($transaction) {
            if ($transaction->isDirty(['total_amount', 'pos_fee_percent', 'agent_fee_percent', 'agent_advance'])) {
                $transaction->calculateFees();
            }
        });

        // Create log entry and advance record after creation
        static::created(function ($transaction) {
            // Log creation
            TransactionLog::create([
                'transaction_id' => $transaction->id,
                'agent_id' => $transaction->agent_id,
                'user_id' => $transaction->user_id ?? \Illuminate\Support\Facades\Auth::id(),
                'action' => 'created',
                'old_value' => null,
                'new_value' => 'Transaction created',
                'metadata' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (string) $transaction->total_amount,
                    'type' => $transaction->transaction_type,
                ],
            ]);

            // Create daily advance record if advance amount > 0
            if ($transaction->agent_advance > 0) {
                DailyAdvance::create([
                    'transaction_id' => $transaction->id,
                    'agent_id' => $transaction->agent_id,
                    'advance_date' => $transaction->transaction_date->format('Y-m-d'),
                    'advance_amount' => $transaction->agent_advance,
                    'is_settled' => false,
                ]);
            }
        });
    }
}
