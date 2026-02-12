<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class ConfigType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Config',
        'description' => 'Cấu hình app',
    ];

    public function fields(): array
    {
        return [
            'version' => ['type' => Type::string()],
            'default_pos_fee' => ['type' => Type::float()],
            'default_agent_fee' => ['type' => Type::float()],
            'currency' => ['type' => Type::string()],
            'timezone' => ['type' => Type::string()],
            'status_values' => ['type' => Type::listOf(Type::string())],
            'amount_suggestions' => ['type' => Type::listOf(Type::int())],
        ];
    }
}
