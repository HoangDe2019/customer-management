<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class MeQuery extends Query
{
    protected $attributes = [
        'name' => 'me',
        'description' => 'User đang đăng nhập',
    ];

    public function type(): Type
    {
        return GraphQL::type('User');
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? null;
        if (!$user) {
            return null;
        }
        return $user;
    }
}
