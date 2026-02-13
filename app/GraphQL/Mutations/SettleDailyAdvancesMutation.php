<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Events\DataUpdated;
use App\Models\Agent;
use App\Services\SettlementService;
use Carbon\Carbon;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class SettleDailyAdvancesMutation extends Mutation
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    protected $attributes = [
        'name' => 'settleDailyAdvances',
        'description' => 'Đối soát tạm ứng theo ngày. Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'agent_id' => ['type' => Type::nonNull(Type::int())],
            'date' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        $requestId = Str::uuid()->toString();

        if (!$user) {
            event(new RequestCompleted($requestId, false, 'settlements', 'settled', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        $agent = Agent::find($args['agent_id']);
        if (!$agent || (!$user->isAdmin() && !$user->hasAgentAccess($agent))) {
            event(new RequestCompleted($requestId, false, 'settlements', 'settled', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y', $args['date'])->format('Y-m-d');
            $result = $this->settlementService->settleDailyAdvances($agent, $date);
            event(new RequestCompleted($requestId, true, 'settlements', 'settled', $result ? (array) $result : [], null));
            event(new DataUpdated('settlements', 'settled'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã đối soát; kết quả gửi qua notification.'];
        } catch (\Throwable $e) {
            event(new RequestCompleted($requestId, false, 'settlements', 'settled', null, $e->getMessage()));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }
    }
}
