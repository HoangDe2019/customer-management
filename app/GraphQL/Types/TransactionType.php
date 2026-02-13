<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class TransactionType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Transaction',
        'description' => 'Giao dịch',
    ];

    public function fields(): array
    {
        return [
            'id' => ['type' => Type::nonNull(Type::int())],
            'transaction_id' => ['type' => Type::string()],
            'agent_id' => ['type' => Type::int()],
            'user_id' => ['type' => Type::int()],
            'customer_name' => ['type' => Type::string()],
            'cccd_number' => ['type' => Type::string()],
            'total_amount' => ['type' => Type::float()],
            'transaction_type' => ['type' => Type::string()],
            'pos_fee_percent' => ['type' => Type::float()],
            'agent_fee_percent' => ['type' => Type::float()],
            'pos_fee_amount' => ['type' => Type::float()],
            'agent_fee_amount' => ['type' => Type::float()],
            'profit' => ['type' => Type::float()],
            'refund_to_agent' => ['type' => Type::float()],
            'agent_advance' => ['type' => Type::float()],
            'net_settlement' => ['type' => Type::float()],
            'status' => ['type' => Type::string()],
            'transaction_date' => ['type' => Type::string()],
            'agent' => ['type' => GraphQL::type('Agent')],
            'user' => ['type' => GraphQL::type('User')],
        ];
    }
}
