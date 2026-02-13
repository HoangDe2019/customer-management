<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Events\DataUpdated;
use App\Models\Transaction;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UpdateTransactionStatusMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateTransactionStatus',
        'description' => 'Cập nhật trạng thái giao dịch. Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
            'status' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        $requestId = Str::uuid()->toString();

        if (!$user) {
            event(new RequestCompleted($requestId, false, 'transactions', 'updated', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        $transaction = Transaction::with('agent')->find($args['id']);
        if (!$transaction || !$user->hasAgentAccess($transaction->agent)) {
            event(new RequestCompleted($requestId, false, 'transactions', 'updated', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        $oldStatus = $transaction->status;
        $transaction->update(['status' => $args['status']]);
        $transaction->logs()->create([
            'agent_id' => $transaction->agent_id,
            'user_id' => $user->id,
            'action' => 'status_changed',
            'old_value' => $oldStatus,
            'new_value' => $args['status'],
        ]);

        $payload = $transaction->load(['agent', 'user'])->toArray();
        event(new RequestCompleted($requestId, true, 'transactions', 'updated', $payload, null));
        event(new DataUpdated('transactions', 'updated'));

        return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã cập nhật; kết quả gửi qua notification.'];
    }
}
