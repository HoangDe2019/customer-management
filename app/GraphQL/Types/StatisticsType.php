<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class StatisticsType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Statistics',
        'description' => 'Thống kê',
    ];

    public function fields(): array
    {
        return [
            'total_transactions' => ['type' => Type::int()],
            'total_amount' => ['type' => Type::float()],
            'total_profit' => ['type' => Type::float()],
            'total_agent_advance' => ['type' => Type::float()],
            'dao_count' => ['type' => Type::int()],
            'rut_count' => ['type' => Type::int()],
            'recent' => ['type' => Type::listOf(GraphQL::type('RecentStatsItem'))],
            'by_status' => ['type' => Type::string(), 'description' => 'JSON object: status -> count'],
        ];
    }
}
