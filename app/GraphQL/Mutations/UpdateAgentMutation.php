<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Events\DataUpdated;
use App\Models\Agent;
use App\Models\User;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class UpdateAgentMutation extends Mutation
{
    protected $attributes = [
        'name' => 'updateAgent',
        'description' => 'Cập nhật đại lý (admin). Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
            'status' => ['type' => Type::string()],
            'allowed_users' => ['type' => Type::string()],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Get authenticated user
        $user = $context['user'] ?? auth()->user();
        $requestId = Str::uuid()->toString();

        // Check authentication
        if (!$user) {
            event(new RequestCompleted($requestId, false, 'agents', 'updated', null, 'Unauthenticated'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Check admin permission
        if (!$user->is_admin) {
            event(new RequestCompleted($requestId, false, 'agents', 'updated', null, 'Chỉ admin mới có quyền cập nhật đại lý'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Find agent
        $agent = Agent::find($args['id']);
        if (!$agent) {
            event(new RequestCompleted($requestId, false, 'agents', 'updated', null, 'Không tìm thấy đại lý.'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        // Use transaction for data consistency
        DB::beginTransaction();
        try {
            // Update status if provided (matching controller with has check)
            if (array_key_exists('status', $args) && !empty($args['status'])) {
                $validStatuses = ['Active', 'Inactive', 'Deleted'];
                if (!in_array($args['status'], $validStatuses)) {
                    DB::rollBack();
                    event(new RequestCompleted($requestId, false, 'agents', 'updated', null, 'Trạng thái không hợp lệ.'));
                    return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
                }
                $agent->status = $args['status'];
            }

            // Update allowed_users if provided (matching controller logic)
            if (array_key_exists('allowed_users', $args)) {
                $allowedUsersStr = $args['allowed_users'] ?? '';
                $emails = array_map('trim', array_filter(explode(',', $allowedUsersStr)));
                // Filter out empty strings
                $emails = array_filter($emails, fn($email) => !empty($email));

                $agent->allowed_users = $emails;

                // Sync users (controller uses sync, not syncWithoutDetaching here)
                $userIds = User::whereIn('email', $emails)->pluck('id')->toArray();
                $agent->users()->sync($userIds);
            }

            $agent->save();
            DB::commit();

            // Fire success events
            event(new RequestCompleted(
                $requestId,
                true,
                'agents',
                'updated',
                [
                    'success' => true,
                    'message' => 'Đã cập nhật thành công',
                    'agent' => $agent->toArray()
                ],
                null
            ));
            event(new DataUpdated('agents', 'updated'));

            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã cập nhật thành công; kết quả gửi qua notification.'];
        } catch (\Exception $e) {
            DB::rollBack();

            event(new RequestCompleted($requestId, false, 'agents', 'updated', null, 'Lỗi khi cập nhật: ' . $e->getMessage()));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }
    }
}
