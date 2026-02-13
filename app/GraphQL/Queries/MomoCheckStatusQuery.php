<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Services\MoMoService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class MomoCheckStatusQuery extends Query
{
    protected $attributes = [
        'name' => 'momoCheckStatus',
        'description' => 'Kiểm tra trạng thái thanh toán MoMo theo orderId',
    ];

    public function type(): Type
    {
        return GraphQL::type('MomoStatus');
    }

    public function args(): array
    {
        return [
            'order_id' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Mã đơn hàng MoMo',
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = auth()->user();
        $requestId = Str::uuid()->toString();

        if (!$user) {
            throw new \Exception('Unauthorized');
        }

        // Resolve service from container inside resolve method
        $momoService = app(MoMoService::class);
        $result = $momoService->checkStatus($args['order_id']);

        return [
            'status' => $result['status'],
            'result_code' => $result['resultCode'],
            'message' => $result['message'],
        ];
    }
}
