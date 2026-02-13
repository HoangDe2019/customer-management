<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class JsonResultType extends GraphQLType
{
    protected $attributes = [
        'name' => 'JsonResult',
        'description' => 'Kết quả JSON (chuỗi)',
    ];

    public function fields(): array
    {
        return [
            'value' => [
                'type' => Type::string(),
                'description' => 'JSON string',
            ],
        ];
    }
}
