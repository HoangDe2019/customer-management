<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\DataUpdated;
use App\Events\RequestCompleted;
use App\Models\Agent;
use App\Models\User;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class CreateAgentMutation extends Mutation
{
    protected $attributes = [
        'name' => 'createAgent',
        'description' => 'Tạo đại lý (admin). Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'name' => ['type' => Type::nonNull(Type::string())],
            'allowed_users' => ['type' => Type::string(), 'defaultValue' => ''],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Generate unique request ID for tracking
        $requestId = Str::uuid()->toString();

        // Get authenticated user
        $user = auth()->user();

        // Check authentication
        if (!$user) {
            event(new RequestCompleted(
                $requestId,
                false,
                'agents',
                'created',
                null,
                'Unauthenticated'
            ));
            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi; xem kết quả qua notification.'
            ];
        }

        // Check admin permission
        if (!$user->is_admin) {
            event(new RequestCompleted(
                $requestId,
                false,
                'agents',
                'created',
                null,
                'Chỉ admin mới có quyền tạo đại lý'
            ));
            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi; xem kết quả qua notification.'
            ];
        }

        // Validate and prepare name
        $name = trim($args['name']);

        if (empty($name)) {
            event(new RequestCompleted(
                $requestId,
                false,
                'agents',
                'created',
                null,
                'Tên đại lý không được để trống'
            ));
            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi; xem kết quả qua notification.'
            ];
        }

        // Check for duplicate agent (matching controller validation)
        if (Agent::where('agent_id', $name)->orWhere('name', $name)->exists()) {
            event(new RequestCompleted(
                $requestId,
                false,
                'agents',
                'created',
                null,
                'Tên đại lý đã tồn tại. Vui lòng chọn tên khác.'
            ));
            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi; xem kết quả qua notification.'
            ];
        }

        // Parse allowed users (matching controller)
        $allowedUsers = [];
        if (!empty($args['allowed_users'])) {
            $allowedUsers = array_map('trim', array_filter(explode(',', $args['allowed_users'])));
            // Remove empty strings
            $allowedUsers = array_filter($allowedUsers, fn($email) => !empty($email));
        }

        // Use transaction for data consistency
        DB::beginTransaction();
        try {
            // Create agent
            $agent = Agent::create([
                'agent_id' => $name,
                'name' => $name,
                'status' => 'Active',
                'allowed_users' => $allowedUsers,
                'created_by' => $user->id,
            ]);

            // Sync users with agent using syncWithoutDetaching like controller
            // Controller uses foreach with syncWithoutDetaching
            foreach ($allowedUsers as $email) {
                $foundUser = User::where('email', $email)->first();
                if ($foundUser) {
                    $agent->users()->syncWithoutDetaching([$foundUser->id]);
                }
            }

            DB::commit();

            // Fire success events
            event(new RequestCompleted(
                $requestId,
                true,
                'agents',
                'created',
                [
                    'success' => true,
                    'agent_id' => $agent->agent_id,
                    'message' => 'Đã tạo đại lý thành công',
                    'agent' => $agent->toArray(),
                ],
                null
            ));
            event(new DataUpdated('agents', 'created'));

            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã tạo đại lý thành công; kết quả gửi qua notification.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            event(new RequestCompleted(
                $requestId,
                false,
                'agents',
                'created',
                null,
                'Lỗi khi tạo đại lý: ' . $e->getMessage()
            ));

            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi; xem kết quả qua notification.'
            ];
        }
    }
}
