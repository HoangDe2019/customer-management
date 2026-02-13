<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Events\DataUpdated;
use App\Models\Agent;
use App\Services\TransactionService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CreateTransactionMutation extends Mutation
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    protected $attributes = [
        'name' => 'createTransaction',
        'description' => 'Tạo giao dịch. Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'agent_id' => ['type' => Type::nonNull(Type::int())],
            'customer_name' => ['type' => Type::string()],
            'cccd_number' => ['type' => Type::string()],
            'total_amount' => ['type' => Type::nonNull(Type::float())],
            'transaction_type' => ['type' => Type::nonNull(Type::string())],
            'pos_fee_percent' => ['type' => Type::float()],
            'agent_fee_percent' => ['type' => Type::float()],
            'agent_advance' => ['type' => Type::float(), 'defaultValue' => 0],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Get authenticated user
        $user = $context['user'] ?? auth()->user();
        $requestId = Str::uuid()->toString();

        // Check authentication
        if (!$user) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Unauthenticated'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Validate transaction_type (matching controller validation)
        if (!in_array($args['transaction_type'], ['Đáo', 'Rút'])) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Loại giao dịch không hợp lệ. Chỉ chấp nhận: Đáo, Rút'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Validate total_amount (matching controller: min:0)
        if ($args['total_amount'] < 0) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Tổng số tiền phải lớn hơn hoặc bằng 0'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Validate agent_advance (matching controller: min:0)
        $agentAdvance = $args['agent_advance'] ?? 0;
        if ($agentAdvance < 0) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Số tiền ứng phải lớn hơn hoặc bằng 0'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Validate customer_name (matching controller: max:255)
        if (isset($args['customer_name']) && mb_strlen($args['customer_name']) > 255) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Tên khách hàng không được vượt quá 255 ký tự'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Validate cccd_number (matching controller: max:12)
        if (isset($args['cccd_number']) && mb_strlen($args['cccd_number']) > 12) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Số CCCD không được vượt quá 12 ký tự'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Find and validate agent
        $agent = Agent::find($args['agent_id']);
        if (!$agent) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Không tìm thấy đại lý'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Check agent access permission
        if (!$user->hasAgentAccess($agent)) {
            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Bạn không có quyền tạo giao dịch cho đại lý này'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Prepare data for service (matching controller)
        $data = [
            'agent_id' => $args['agent_id'],
            'customer_name' => $args['customer_name'] ?? null,
            'cccd_number' => $args['cccd_number'] ?? null,
            'total_amount' => $args['total_amount'],
            'transaction_type' => $args['transaction_type'],
            'pos_fee_percent' => $args['pos_fee_percent'] ?? null,
            'agent_fee_percent' => $args['agent_fee_percent'] ?? null,
            'agent_advance' => $agentAdvance,
        ];

        // Use transaction for data consistency (matching controller)
        DB::beginTransaction();
        try {
            // Create transaction via service
            $transaction = $this->transactionService->createTransaction($data, $user);

            DB::commit();

            // Load relationships and prepare payload
            $payload = $transaction->load(['agent', 'user'])->toArray();

            // Fire success events
            event(new RequestCompleted($requestId, true, 'transactions', 'created', $payload, null));
            event(new DataUpdated('transactions', 'created'));

            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã tạo giao dịch thành công; kết quả gửi qua notification.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            event(new RequestCompleted($requestId, false, 'transactions', 'created', null, 'Lỗi khi tạo giao dịch: ' . $e->getMessage()));
            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi; xem kết quả qua notification.'
            ];
        }
    }
}
