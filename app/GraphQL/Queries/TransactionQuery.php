<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Transaction;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class TransactionQuery extends Query
{
    protected $attributes = [
        'name' => 'transaction',
        'description' => 'Chi tiết một giao dịch',
    ];

    public function type(): Type
    {
        return GraphQL::type('Transaction');
    }

    public function args(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        if (!$user) {
            return null;
        }

        $t = Transaction::with(['agent', 'user', 'logs'])->find($args['id']);
        if (!$t || !$user->hasAgentAccess($t->agent)) {
            return null;
        }
        return $t;
    }
}
