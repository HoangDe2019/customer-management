<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class RequestAckType extends GraphQLType
{
    protected $attributes = [
        'name' => 'RequestAck',
        'description' => 'Xác nhận request đã nhận; kết quả thực tế gửi qua WebSocket (notification)',
    ];

    public function fields(): array
    {
        return [
            'accepted' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Request đã được chấp nhận',
            ],
            'request_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'ID để client lắng nghe notification trùng request_id',
            ],
            'message' => [
                'type' => Type::string(),
                'description' => 'Thông báo ngắn',
            ],
        ];
    }
}
