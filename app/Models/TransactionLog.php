<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'transaction_logs';

    /** @var array<int, string> */
    protected $fillable = [
        'transaction_id',
        'agent_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeStatusChanges($query)
    {
        return $query->where('action', 'status_changed');
    }

    public function scopeCreated($query)
    {
        return $query->where('action', 'created');
    }

    public function scopeUpdated($query)
    {
        return $query->where('action', 'updated');
    }

    public static function log(array $data): self
    {
        return self::create([
            'transaction_id' => $data['transaction_id'],
            'agent_id' => $data['agent_id'],
            'user_id' => $data['user_id'] ?? \Illuminate\Support\Facades\Auth::id(),
            'action' => $data['action'],
            'old_value' => $data['old_value'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'metadata' => array_merge(
                $data['metadata'] ?? [],
                [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toDateTimeString(),
                ]
            ),
        ]);
    }

    public static function getAuditTrail(int $transactionId)
    {
        return self::where('transaction_id', $transactionId)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
