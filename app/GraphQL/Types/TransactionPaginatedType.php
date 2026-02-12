<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class TransactionPaginatedType extends GraphQLType
{
    protected $attributes = [
        'name' => 'TransactionPaginated',
        'description' => 'Danh sách giao dịch có phân trang',
    ];

    public function fields(): array
    {
        return [
            'data' => ['type' => Type::listOf(GraphQL::type('Transaction'))],
            'current_page' => ['type' => Type::int()],
            'last_page' => ['type' => Type::int()],
            'per_page' => ['type' => Type::int()],
            'total' => ['type' => Type::int()],
        ];
    }
}
