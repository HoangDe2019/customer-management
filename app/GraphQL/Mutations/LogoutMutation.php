<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Auth;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class LogoutMutation extends Mutation
{
    protected $attributes = [
        'name' => 'logout',
        'description' => 'Đăng xuất',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        Auth::guard('api')->logout();
        return ['accepted' => true, 'request_id' => (string) \Illuminate\Support\Str::uuid(), 'message' => 'Đã đăng xuất.'];
    }
}
