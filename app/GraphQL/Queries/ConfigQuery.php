<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ConfigQuery extends Query
{
    protected $attributes = [
        'name' => 'config',
        'description' => 'Cấu hình app',
    ];

    public function type(): Type
    {
        return GraphQL::type('Config');
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        return [
            'version' => config('customer_management.version'),
            'default_pos_fee' => config('customer_management.default_pos_fee_percent'),
            'default_agent_fee' => config('customer_management.default_agent_fee_percent'),
            'currency' => 'VND',
            'timezone' => config('customer_management.timezone'),
            'status_values' => config('customer_management.status_values'),
            'amount_suggestions' => config('customer_management.amount_suggestions'),
        ];
    }
}
