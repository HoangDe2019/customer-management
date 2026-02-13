<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class CloneLastRunType extends GraphQLType
{
    protected $attributes = [
        'name' => 'CloneLastRun',
    ];

    public function fields(): array
    {
        return [
            'status' => ['type' => Type::string()],
            'message' => ['type' => Type::string()],
            'started_at' => ['type' => Type::string()],
            'finished_at' => ['type' => Type::string()],
            'exit_code' => ['type' => Type::int()],
        ];
    }
}
