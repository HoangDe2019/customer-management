<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MomoPayment extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'momo_payments';

    /** @var array<int, string> */
    protected $fillable = [
        'momo_transaction_id',
        'transaction_id',
        'customer_name',
        'amount',
        'transaction_type',
        'order_id',
        'request_id',
        'qr_code_url',
        'pay_url',
        'deeplink',
        'result_code',
        'message',
        'status',
        'paid_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'amount' => 'decimal:2',
        'result_code' => 'integer',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsSuccess(int $resultCode = 0, string $message = 'Success'): bool
    {
        $this->status = 'success';
        $this->result_code = $resultCode;
        $this->message = $message;
        $this->paid_at = now();
        return $this->save();
    }

    public function markAsFailed(int $resultCode, string $message): bool
    {
        $this->status = 'failed';
        $this->result_code = $resultCode;
        $this->message = $message;
        return $this->save();
    }

    public static function generateMomoTransactionId(): string
    {
        return 'MOMO_' . time() . '_' . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    public static function generateOrderId(): string
    {
        return 'ORDER_' . time() . '_' . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    public static function generateRequestId(): string
    {
        return 'REQ_' . time() . '_' . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
    }

    public static function createWithQR(array $data): self
    {
        return self::create([
            'momo_transaction_id' => self::generateMomoTransactionId(),
            'transaction_id' => $data['transaction_id'] ?? null,
            'customer_name' => $data['customer_name'],
            'amount' => $data['amount'],
            'transaction_type' => $data['transaction_type'],
            'order_id' => self::generateOrderId(),
            'request_id' => self::generateRequestId(),
            'qr_code_url' => $data['qr_code_url'] ?? null,
            'pay_url' => $data['pay_url'] ?? null,
            'deeplink' => $data['deeplink'] ?? null,
            'status' => 'pending',
        ]);
    }

    public static function getStatistics(string $period = 'all'): array
    {
        $query = self::query();
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', now()->toDateString());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
                break;
        }
        return [
            'total_payments' => $query->count(),
            'successful_payments' => (clone $query)->where('status', 'success')->count(),
            'pending_payments' => (clone $query)->where('status', 'pending')->count(),
            'failed_payments' => (clone $query)->where('status', 'failed')->count(),
            'total_amount' => (float) (clone $query)->where('status', 'success')->sum('amount'),
        ];
    }
}
