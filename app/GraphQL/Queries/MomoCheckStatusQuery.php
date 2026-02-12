<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Http\Controllers\Api\MoMoController;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
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
            'orderId' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? null;
        if (!$user) {
            return ['status' => 'not_found', 'message' => 'Unauthorized'];
        }

        $request = Request::create('/api/momo/check-status/' . $args['orderId'], 'GET');
        $request->setUserResolver(fn () => $user);

        $response = app(MoMoController::class)->checkStatus($args['orderId']);
        return json_decode($response->getContent(), true) ?? ['status' => 'not_found'];
    }
}
