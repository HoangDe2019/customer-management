<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Agent;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class AgentsQuery extends Query
{
    protected $attributes = [
        'name' => 'agents',
        'description' => 'Danh sách đại lý (theo quyền user)',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Agent'));
    }

    public function args(): array
    {
        return [
            'status' => [
                'type' => Type::string(),
                'description' => 'Lọc theo trạng thái (Active, Inactive, Deleted)',
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Get authenticated user
        $user = $context['user'] ?? auth()->user();

        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        // Match controller logic: admin sees all active, others see accessible
        if ($user->is_admin) {
            $agents = Agent::active()->orderBy('name')->get();
        } else {
            $agents = $user->getAccessibleAgents();
        }

        // Apply status filter if provided
        if (isset($args['status']) && $args['status'] !== '') {
            $agents = $agents->filter(function($agent) use ($args) {
                return $agent->status === $args['status'];
            });
        }

        return $agents->values();
    }
}
