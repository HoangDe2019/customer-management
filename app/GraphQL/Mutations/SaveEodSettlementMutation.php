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

class SaveEodSettlementMutation extends Mutation
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    protected $attributes = [
        'name' => 'saveEodSettlement',
        'description' => 'Lưu đối soát cuối ngày. Kết quả gửi qua notification.',
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
            'settlement_data' => ['type' => Type::string(), 'description' => 'JSON object'],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = auth()->user();
        $requestId = Str::uuid()->toString();

        if (!$user) {
            event(new RequestCompleted($requestId, false, 'settlements', 'saved', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        $agent = Agent::find($args['agent_id']);
        if (!$agent || (!$user->isAdmin() && !$user->hasAgentAccess($agent))) {
            event(new RequestCompleted($requestId, false, 'settlements', 'saved', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        $settlementData = json_decode($args['settlement_data'] ?? '{}', true) ?? [];
        if (empty($settlementData)) {
            event(new RequestCompleted($requestId, false, 'settlements', 'saved', null, 'settlement_data required'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        try {
            $date = Carbon::createFromFormat('d/m/Y', $args['date'])->format('Y-m-d');
            $eod = $this->settlementService->saveEndOfDaySettlement($agent, $date, $settlementData, $user);
            event(new RequestCompleted($requestId, true, 'settlements', 'saved', $eod->toArray(), null));
            event(new DataUpdated('settlements', 'saved'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã lưu; kết quả gửi qua notification.'];
        } catch (\Throwable $e) {
            event(new RequestCompleted($requestId, false, 'settlements', 'saved', null, $e->getMessage()));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }
    }
}
