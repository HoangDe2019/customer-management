<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type as GraphQLTypeDefinition;
use Rebing\GraphQL\Support\Type as GraphQLType;

class CloneStatusType extends GraphQLType
{
    protected $attributes = [
        'name' => 'CloneStatus',
        'description' => 'Trạng thái clone DB',
    ];

    public function fields(): array
    {
        return [
            'last_run' => ['type' => GraphQLTypeDefinition::nullable(GraphQL::type('CloneLastRun'))],
        ];
    }
}
