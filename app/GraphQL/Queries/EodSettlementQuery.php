<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Agent;
use App\Services\SettlementService;
use Carbon\Carbon;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class EodSettlementQuery extends Query
{
    public function __construct(
        protected SettlementService $settlementService
    ) {}

    protected $attributes = [
        'name' => 'eodSettlement',
        'description' => 'Đối soát cuối ngày (tính toán)',
    ];

    public function type(): Type
    {
        return GraphQL::type('JsonResult');
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
        if (!$user) {
            return ['value' => json_encode([])];
        }

        $agent = Agent::find($args['agent_id']);
        if (!$agent || !$user->hasAgentAccess($agent)) {
            return ['value' => json_encode([])];
        }

        $date = Carbon::createFromFormat('d/m/Y', $args['date'])->format('Y-m-d');
        $result = $this->settlementService->calculateEndOfDaySettlement($agent, $date);
        return ['value' => json_encode($result)];
    }
}
