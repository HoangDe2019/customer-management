<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class SettlementRecordType extends GraphQLType
{
    protected $attributes = [
        'name' => 'SettlementRecord',
    ];

    public function fields(): array
    {
        return [
            'id' => ['type' => Type::int()],
            'agent_id' => ['type' => Type::int()],
            'settlement_date' => ['type' => Type::string()],
            'agent' => ['type' => GraphQL::type('Agent')],
            'settler' => ['type' => GraphQL::type('User')],
        ];
    }
}
