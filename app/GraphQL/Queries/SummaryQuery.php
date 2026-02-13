<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Agent;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class SummaryQuery extends Query
{
    protected $attributes = [
        'name' => 'summary',
        'description' => 'Tổng hợp theo đại lý (dashboard)',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('SummaryItem'));
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        if (!$user) {
            return [];
        }

        $agents = $user->is_admin
            ? Agent::active()->get()
            : $user->getAccessibleAgents();

        return $agents->map(function (Agent $agent) {
            $stats = $agent->getStatistics('all');
            return [
                'agent_id' => $agent->agent_id,
                'name' => $agent->name,
                'total_transactions' => $stats['total_transactions'],
                'total_amount' => $stats['total_amount'],
                'total_profit' => $stats['total_profit'],
            ];
        })->toArray();
    }
}
