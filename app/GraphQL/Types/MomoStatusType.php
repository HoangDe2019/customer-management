<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class MomoStatusType extends GraphQLType
{
    protected $attributes = [
        'name' => 'MomoStatus',
        'description' => 'Trạng thái thanh toán MoMo',
    ];

    public function fields(): array
    {
        return [
            'status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Trạng thái: pending, success, failed, not_found',
            ],
            'result_code' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Mã kết quả từ MoMo',
            ],
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Thông báo kết quả',
            ],
        ];
    }
}