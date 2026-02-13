<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class MomoQRResponseType extends GraphQLType
{
    protected $attributes = [
        'name' => 'MomoQRResponse',
        'description' => 'Kết quả tạo QR code MoMo',
    ];

    public function fields(): array
    {
        return [
            'payment_id' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'ID bản ghi payment trong database',
            ],
            'order_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Mã đơn hàng từ MoMo',
            ],
            'qr_code_url' => [
                'type' => Type::string(),
                'description' => 'URL của QR code (null nếu thất bại)',
            ],
            'pay_url' => [
                'type' => Type::string(),
                'description' => 'URL trang thanh toán (null nếu thất bại)',
            ],
            'deeplink' => [
                'type' => Type::string(),
                'description' => 'Deeplink mở app MoMo (null nếu thất bại)',
            ],
            'status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Trạng thái: pending, failed',
            ],
            'result_code' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Mã kết quả từ MoMo API (0 = thành công)',
            ],
            'message' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Thông báo từ MoMo',
            ],

            // ===== Thêm hai field đang bị lỗi query =====
            'accepted' => [
                'type' => Type::boolean(),
                'description' => 'Server đã chấp nhận yêu cầu hay chưa (true/false)',
            ],
            'request_id' => [
                'type' => Type::string(),
                'description' => 'Request ID dùng để trace/debug',
            ],
        ];
    }
}