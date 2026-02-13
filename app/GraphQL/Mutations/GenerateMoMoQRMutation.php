<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Events\DataUpdated;
use App\Events\RequestCompleted;
use App\Services\MoMoService;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Mutation;

class GenerateMoMoQRMutation extends Mutation
{
    protected $attributes = [
        'name' => 'generateMoMoQR',
        'description' => 'Tạo mã QR MoMo (async) - kết quả gửi qua notification',
    ];

    public function type(): Type
    {
        // ✅ Return RequestAck thay vì MomoQRResponse
        return GraphQL::type('RequestAck');
    }

    public function args(): array
    {
        return [
            'customer_name' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Tên khách hàng',
            ],
            'total_amount' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Số tiền thanh toán (tối thiểu 1000)',
            ],
            'transaction_type' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Loại giao dịch: Đáo hoặc Rút',
            ],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        $requestId = Str::uuid()->toString();

        if (!$user) {
            event(new RequestCompleted($requestId, false, 'momo_payments', 'generateQR', null, 'Unauthenticated'));

            // ✅ Return RequestAck format
            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi yêu cầu; xem kết quả qua notification.',
            ];
        }

        // Validate inputs
        $validator = Validator::make($args, [
            'customer_name'    => 'required|string|max:255',
            'total_amount'     => 'required|numeric|min:1000',
            'transaction_type' => 'required|in:Đáo,Rút',
        ]);

        if ($validator->fails()) {
            event(new RequestCompleted($requestId, false, 'momo_payments', 'generateQR', null, $validator->errors()->first()));

            // ✅ Return RequestAck format
            return [
                'accepted' => true,
                'request_id' => $requestId,
                'message' => 'Đã gửi yêu cầu; xem kết quả qua notification.',
            ];
        }

        // Process in background (hoặc dispatch job)
        try {
            DB::beginTransaction();

            /** @var MoMoService $momoService */
            $momoService = app(MoMoService::class);

            $result = $momoService->generateQR(
                $args['customer_name'],
                (float) $args['total_amount'],
                $args['transaction_type']
            );

            DB::commit();

            // Map kết quả để gửi qua notification
            $responseData = [
                'payment_id'  => (int) ($result['paymentId'] ?? 0),
                'order_id'    => $result['orderId'] ?? '',
                'qr_code_url' => $result['qrCodeUrl'] ?? null,
                'pay_url'     => $result['payUrl'] ?? null,
                'deeplink'    => $result['deeplink'] ?? null,
                'status'      => $result['status'] ?? 'failed',
                'result_code' => (int) ($result['resultCode'] ?? -1),
                'message'     => $result['message'] ?? 'Unknown error',
            ];

            // ✅ Fire event với kết quả đầy đủ
            event(new RequestCompleted(
                $requestId,
                true,
                'momo_payments',
                'generateQR',
                $responseData,
                null
            ));

            event(new DataUpdated('momo_payments', 'generateQR'));

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('generateMoMoQR failed', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'args'  => $args,
            ]);

            // ✅ Fire event với error
            event(new RequestCompleted(
                $requestId,
                false,
                'momo_payments',
                'generateQR',
                null,
                'Lỗi khi tạo QR: ' . $e->getMessage()
            ));
        }

        // ✅ Return RequestAck format
        return [
            'accepted' => true,
            'request_id' => $requestId,
            'message' => 'Đã khởi tạo yêu cầu tạo QR MoMo; kết quả sẽ gửi qua notification.',
        ];
    }
}
