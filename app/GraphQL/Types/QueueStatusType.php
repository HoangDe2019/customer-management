<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class QueueStatusType extends GraphQLType
{
    protected $attributes = [
        'name' => 'QueueStatus',
        'description' => 'Trạng thái queue',
    ];

    public function fields(): array
    {
        return [
            'connection' => ['type' => Type::string()],
            'driver' => ['type' => Type::string()],
            'pending_count' => ['type' => Type::int()],
            'message' => ['type' => Type::string()],
        ];
    }
}
