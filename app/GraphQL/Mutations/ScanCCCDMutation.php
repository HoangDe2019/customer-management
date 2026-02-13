<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\RequestCompleted;
use App\Http\Controllers\Api\TransactionController;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class ScanCCCDMutation extends Mutation
{
    protected $attributes = [
        'name' => 'scanCCCD',
        'description' => 'Quét CCCD (Gemini). Kết quả gửi qua notification.',
    ];

    public function type(): Type
    {
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'image' => ['type' => Type::nonNull(Type::string()), 'description' => 'Base64 image'],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        $requestId = Str::uuid()->toString();

        if (!$user) {
            event(new RequestCompleted($requestId, false, 'cccd', 'scan', null, 'Unauthorized'));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }

        $request = Request::create('/api/cccd/scan', 'POST', ['image' => $args['image']]);
        $request->setUserResolver(fn () => $user);

        try {
            $response = app(TransactionController::class)->scanCCCD($request);
            $data = json_decode($response->getContent(), true);
            event(new RequestCompleted($requestId, true, 'cccd', 'scan', $data ?? [], null));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã quét; kết quả gửi qua notification.'];
        } catch (\Throwable $e) {
            event(new RequestCompleted($requestId, false, 'cccd', 'scan', null, $e->getMessage()));
            return ['accepted' => true, 'request_id' => $requestId, 'message' => 'Đã gửi; xem kết quả qua notification.'];
        }
    }
}
