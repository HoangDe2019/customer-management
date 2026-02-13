<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Events\DataUpdated;
use App\Models\Agent;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class DeleteAgentMutation extends Mutation
{
    protected $attributes = [
        'name' => 'deleteAgent',
        'description' => 'Xóa (soft) đại lý. Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Get authenticated user
        $user = $context['user'] ?? auth()->user();
        $requestId = Str::uuid()->toString();

        // Check authentication
        if (!$user) {
            event(new RequestCompleted($requestId, false, 'agents', 'deleted', null, 'Unauthenticated'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Check admin permission
        if (!$user->is_admin) {
            event(new RequestCompleted($requestId, false, 'agents', 'deleted', null, 'Chỉ admin mới có quyền xóa đại lý'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Find agent
        $agent = Agent::find($args['id']);
        if (!$agent) {
            event(new RequestCompleted($requestId, false, 'agents', 'deleted', null, 'Không tìm thấy đại lý'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Prevent deleting default agent (matching controller check)
        if (strtolower($agent->agent_id) === 'khách hàng') {
            event(new RequestCompleted($requestId, false, 'agents', 'deleted', null, 'Không thể xóa đại lý mặc định'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Use transaction for data consistency
        DB::beginTransaction();
        try {
            // Soft delete by setting status to 'Deleted'
            $agent->status = 'Deleted';
            $agent->save();

            DB::commit();

            // Fire success events
            event(new RequestCompleted(
                $requestId,
                true,
                'agents',
                'deleted',
                [
                    'success' => true,
                    'message' => 'Đã xóa đại lý',
                    'id' => $agent->id
                ],
                null
            ));
            event(new DataUpdated('agents', 'deleted'));

            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã xóa đại lý thành công; kết quả gửi qua notification.'];
        } catch (\Exception $e) {
            DB::rollBack();

            event(new RequestCompleted($requestId, false, 'agents', 'deleted', null, 'Lỗi khi xóa: ' . $e->getMessage()));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }
    }
}
