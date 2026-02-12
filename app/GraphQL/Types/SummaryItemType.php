<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class SummaryItemType extends GraphQLType
{
    protected $attributes = [
        'name' => 'SummaryItem',
        'description' => 'Tổng hợp một đại lý',
    ];

    public function fields(): array
    {
        return [
            'agent_id' => ['type' => Type::string()],
            'name' => ['type' => Type::string()],
            'total_transactions' => ['type' => Type::int()],
            'total_amount' => ['type' => Type::float()],
            'total_profit' => ['type' => Type::float()],
        ];
    }
}
