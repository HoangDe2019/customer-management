<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class UserAgentsQuery extends Query
{
    protected $attributes = [
        'name' => 'userAgents',
        'description' => 'Danh sách đại lý của user hiện tại (cho dropdown)',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Agent'));
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Get authenticated user
        $user = $context['user'] ?? auth()->user();

        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        // Get accessible agents (matching controller userAgents method)
        $agents = $user->getAccessibleAgents();

        return $agents->values();
    }
}
