<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    /** @var string */
    protected $table = 'agents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agent_id',
        'name',
        'status',
        'allowed_users',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allowed_users' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_active'];

    /**
     * Get the creator of the agent.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users that belong to the agent.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'agent_users')
            ->withTimestamps();
    }

    /**
     * Get the transactions for the agent.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'agent_id');
    }

    /**
     * Get the daily advances for the agent.
     */
    public function dailyAdvances()
    {
        return $this->hasMany(DailyAdvance::class, 'agent_id');
    }

    /**
     * Get the EOD settlements for the agent.
     */
    public function eodSettlements()
    {
        return $this->hasMany(EodSettlement::class, 'agent_id');
    }

    /**
     * Get the transaction logs for the agent.
     */
    public function transactionLogs()
    {
        return $this->hasMany(TransactionLog::class, 'agent_id');
    }

    /**
     * Scope a query to only include active agents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Get the default "Khách hàng" agent (same as GAS).
     */
    public static function getDefaultAgent(): ?self
    {
        return self::where('agent_id', 'Khách hàng')->active()->first();
    }

    /**
     * Scope a query to only include inactive agents.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'Inactive');
    }

    /**
     * Scope a query to filter agents by user email.
     */
    public function scopeForUser($query, string $email)
    {
        return $query->where(function ($q) use ($email) {
            $q->whereJsonContains('allowed_users', $email)
              ->orWhereHas('users', function ($userQuery) use ($email) {
                  $userQuery->where('email', $email);
              });
        });
    }

    /**
     * Check if agent is active.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'Active';
    }

    /**
     * Check if a user has access to this agent.
     */
    public function hasUserAccess(string $email): bool
    {
        // Check in allowed_users JSON
        if (is_array($this->allowed_users) && in_array(strtolower($email), array_map('strtolower', $this->allowed_users))) {
            return true;
        }

        // Check in pivot table
        return $this->users()->where('email', strtolower($email))->exists();
    }

    /**
     * Add a user to this agent.
     */
    public function addUser(User $user): bool
    {
        // Add to pivot table if not exists
        if (!$this->users()->where('user_id', $user->id)->exists()) {
            $this->users()->attach($user->id);
        }

        // Also add to allowed_users JSON if not exists
        $allowedUsers = $this->allowed_users ?? [];
        if (!in_array(strtolower($user->email), array_map('strtolower', $allowedUsers))) {
            $allowedUsers[] = $user->email;
            $this->allowed_users = $allowedUsers;
            $this->save();
        }

        return true;
    }

    /**
     * Remove a user from this agent.
     */
    public function removeUser(User $user): bool
    {
        // Remove from pivot table
        $this->users()->detach($user->id);

        // Remove from allowed_users JSON
        if (is_array($this->allowed_users)) {
            $this->allowed_users = array_values(
                array_filter($this->allowed_users, function ($email) use ($user) {
                    return strtolower($email) !== strtolower($user->email);
                })
            );
            $this->save();
        }

        return true;
    }

    /**
     * Get completed transactions count (only "Hoàn thành" status).
     * FIXED: Original bug counted all transactions.
     */
    public function getCompletedTransactionsCount(): int
    {
        return $this->transactions()
            ->where('status', 'Hoàn thành')
            ->count();
    }

    /**
     * Get total revenue (only completed transactions).
     * FIXED: Original bug counted all transactions.
     */
    public function getTotalRevenue(): float
    {
        return (float) $this->transactions()
            ->where('status', 'Hoàn thành')
            ->sum('total_amount');
    }

    /**
     * Get total profit (only completed transactions).
     * FIXED: Original bug counted all transactions.
     */
    public function getTotalProfit(): float
    {
        return (float) $this->transactions()
            ->where('status', 'Hoàn thành')
            ->sum('profit');
    }

    /**
     * Get total advances (only completed transactions).
     */
    public function getTotalAdvances(): float
    {
        return (float) $this->transactions()
            ->where('status', 'Hoàn thành')
            ->sum('agent_advance');
    }

    /**
     * Get unsettled advances.
     */
    public function getUnsettledAdvances(): float
    {
        return (float) $this->dailyAdvances()
            ->where('is_settled', false)
            ->sum('advance_amount');
    }

    /**
     * Get transaction count by type (only completed).
     */
    public function getTransactionCountByType(string $type): int
    {
        return $this->transactions()
            ->where('status', 'Hoàn thành')
            ->where('transaction_type', $type)
            ->count();
    }

    /**
     * Get statistics for a period.
     */
    public function getStatistics(string $period = 'all'): array
    {
        $query = $this->transactions()
            ->where('status', 'Hoàn thành');

        // Apply period filter
        $query = $this->applyPeriodFilter($query, $period);

        return [
            'total_transactions' => $query->count(),
            'total_amount' => (float) $query->sum('total_amount'),
            'total_profit' => (float) $query->sum('profit'),
            'total_advance' => (float) $query->sum('agent_advance'),
            'dao_count' => (clone $query)->where('transaction_type', 'Đáo')->count(),
            'rut_count' => (clone $query)->where('transaction_type', 'Rút')->count(),
        ];  
    }

    /**
     * Apply period filter to query.
     */
    private function applyPeriodFilter($query, string $period)
    {
        $now = now();

        switch ($period) {
            case 'today':
                return $query->whereDate('transaction_date', $now->toDateString());
            case '3days':
                return $query->where('transaction_date', '>=', $now->copy()->subDays(3));
            case 'week':
                return $query->where('transaction_date', '>=', $now->copy()->subWeek());
            case 'month':
                return $query->whereMonth('transaction_date', $now->month)
                    ->whereYear('transaction_date', $now->year);
            default:
                return $query;
        }
    }

    /**
     * Activate the agent.
     */
    public function activate(): bool
    {
        $this->status = 'Active';
        return $this->save();
    }

    /**
     * Deactivate the agent.
     */
    public function deactivate(): bool
    {
        $this->status = 'Inactive';
        return $this->save();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate agent_id if not provided
        static::creating(function ($agent) {
            if (empty($agent->agent_id)) {
                // Use name as agent_id, or generate unique ID
                $agent->agent_id = $agent->name ?? 'AGENT_' . Str::random(8);
            }
        });

        // Prevent deletion if agent has transactions
        static::deleting(function ($agent) {
            if ($agent->transactions()->exists()) {
                throw new \Exception('Cannot delete agent with existing transactions. Please set status to Inactive instead.');
            }
        });
    }
}
