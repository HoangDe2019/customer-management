<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Agent;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class AgentType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Agent',
        'description' => 'Agent (đại lý)',
        'model' => Agent::class,
    ];

    public function fields(): array
    {
        return [
            'id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID',
            ],
            'agent_id' => [
                'type' => Type::string(),
                'description' => 'Mã đại lý',
            ],
            'name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Tên đại lý',
            ],
            'status' => [
                'type' => Type::string(),
                'description' => 'Trạng thái',
            ],
            'allowed_users' => [
                'type' => Type::listOf(Type::string()),
                'description' => 'Danh sách email được phép',
            ],
        ];
    }
}
