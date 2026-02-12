<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Http\Controllers\Api\MoMoController;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class GenerateMoMoQRMutation extends Mutation
{
    protected $attributes = [
        'name' => 'generateMoMoQR',
        'description' => 'Tạo QR MoMo. Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'customer_name' => ['type' => Type::nonNull(Type::string())],
            'total_amount' => ['type' => Type::nonNull(Type::float())],
            'transaction_type' => ['type' => Type::nonNull(Type::string())],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = auth()->user();
        $requestId = Str::uuid()->toString();

        if (!$user) {
            event(new RequestCompleted($requestId, false, 'momo', 'generate', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        $request = Request::create('/api/momo/generate-qr', 'POST', $args);
        $request->setUserResolver(fn () => $user);

        try {
            $response = app(MoMoController::class)->generateQR($request);
            $data = json_decode($response->getContent(), true);
            event(new RequestCompleted($requestId, true, 'momo', 'generate', $data ?? [], null));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã tạo QR; kết quả gửi qua notification.'];
        } catch (\Throwable $e) {
            event(new RequestCompleted($requestId, false, 'momo', 'generate', null, $e->getMessage()));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }
    }
}
