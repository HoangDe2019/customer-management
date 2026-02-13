<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Agent;
use App\Models\EodSettlement;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class SettlementHistoryQuery extends Query
{
    protected $attributes = [
        'name' => 'settlementHistory',
        'description' => 'Lịch sử đối soát',
    ];

    public function type(): Type
    {
        return GraphQL::type('SettlementHistoryPaginated');
    }

    public function args(): array
    {
        return [
            'agent_id' => ['type' => Type::int()],
            'page' => ['type' => Type::int()],
            'per_page' => ['type' => Type::int()],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        if (!$user) {
            return ['data' => [], 'current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0];
        }

        $query = EodSettlement::with(['agent', 'settler']);

        if (!empty($args['agent_id'])) {
            $agent = Agent::find($args['agent_id']);
            if (!$agent || !$user->hasAgentAccess($agent)) {
                return ['data' => [], 'current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 0];
            }
            $query->where('agent_id', $agent->id);
        } else {
            if (!$user->isAdmin()) {
                $agentIds = $user->agents()->pluck('agents.id');
                $query->whereIn('agent_id', $agentIds);
            }
        }

        $perPage = $args['per_page'] ?? 15;
        $paginator = $query->latest('settlement_date')->paginate($perPage, ['*'], 'page', $args['page'] ?? 1);

        return [
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
