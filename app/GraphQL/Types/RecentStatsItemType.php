<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class RecentStatsItemType extends GraphQLType
{
    protected $attributes = [
        'name' => 'RecentStatsItem',
        'description' => 'Giao dịch gần đây trong thống kê',
    ];

    public function fields(): array
    {
        return [
            'name' => ['type' => Type::string()],
            'amount' => ['type' => Type::float()],
            'type' => ['type' => Type::string()],
            'date' => ['type' => Type::string()],
            'status' => ['type' => Type::string()],
        ];
    }
}
