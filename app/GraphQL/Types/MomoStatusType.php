<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class MomoStatusType extends GraphQLType
{
    protected $attributes = [
        'name' => 'MomoStatus',
    ];

    public function fields(): array
    {
        return [
            'status' => ['type' => Type::string()],
            'resultCode' => ['type' => Type::int()],
            'message' => ['type' => Type::string()],
        ];
    }
}
