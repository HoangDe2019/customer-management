<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\MomoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoMoController extends Controller
{
    /**
     * Generate MoMo QR for payment (mirrors generateMoMoQR in GAS).
     */
    public function generateQR(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'total_amount' => 'required|numeric|min:1000',
            'transaction_type' => 'required|in:Đáo,Rút',
        ]);

        $amount = (int) $request->total_amount;
        $orderInfo = 'Thanh toan ' . $request->transaction_type . ' - ' . $request->customer_name;

        // Create initial MoMo payment record with pending status
        $payment = MomoPayment::createWithQR([
            'transaction_id'   => null,
            'customer_name'    => $request->customer_name,
            'amount'           => $amount,
            'transaction_type' => $request->transaction_type,
        ]);

        $requestId = $payment->request_id;
        $orderId = $payment->order_id;

        $partnerCode = config('services.momo.partner_code');
        $accessKey = config('services.momo.access_key');
        $secretKey = config('services.momo.secret_key');
        $redirectUrl = config('services.momo.redirect_url');
        $ipnUrl = config('services.momo.ipn_url');
        $apiUrl = config('services.momo.api_url');

        $rawSignature = "accessKey={$accessKey}&amount={$amount}&extraData=&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType=captureWallet";
        $signature = $this->createHMAC($rawSignature, $secretKey);

        $body = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'requestType' => 'captureWallet',
            'extraData' => '',
            'signature' => $signature,
            'lang' => 'vi',
        ];

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->withOptions(['verify' => false])
                ->timeout(120)
                ->post($apiUrl, $body);

            $result = $response->json();
            $resultCode = (int) ($result['resultCode'] ?? -1);
            $message = $result['message'] ?? 'Unknown error';

            // Update local payment record with MoMo response data
            $payment->update([
                'qr_code_url' => $result['qrCodeUrl'] ?? null,
                'pay_url' => $result['payUrl'] ?? null,
                'deeplink' => $result['deeplink'] ?? null,
                'result_code' => $resultCode,
                'message' => $message,
                'status' => $resultCode === 0 ? 'pending' : 'failed',
            ]);

            if ($response->successful() && $resultCode === 0) {
                return response()->json([
                    'paymentId' => $payment->id,
                    'orderId' => $orderId,
                    'qrCodeUrl' => $payment->qr_code_url,
                    'payUrl' => $payment->pay_url,
                    'deeplink' => $payment->deeplink,
                    'status' => $payment->status,
                    'resultCode' => $resultCode,
                    'message' => $message,
                ]);
            }

            return response()->json([
                'paymentId' => $payment->id,
                'orderId' => $orderId,
                'qrCodeUrl' => null,
                'payUrl' => null,
                'deeplink' => null,
                'status' => 'failed',
                'resultCode' => $resultCode,
                'message' => $message,
            ], 400);
        } catch (\Throwable $e) {
            Log::error('MoMo generateQR error: ' . $e->getMessage());

            $payment->update([
                'status' => 'failed',
                'message' => 'Exception when calling MoMo: ' . $e->getMessage(),
            ]);

            return response()->json([
                'paymentId' => $payment->id,
                'orderId' => $orderId,
                'qrCodeUrl' => null,
                'payUrl' => null,
                'deeplink' => null,
                'status' => 'failed',
                'resultCode' => -1,
                'message' => 'Không thể kết nối tới MoMo',
            ], 500);
        }
    }

    /**
     * MoMo IPN/Webhook (no auth) - mirrors doPost in GAS.
     */
    public function webhook(Request $request)
    {
        try {
            $data = $request->all();
            $secretKey = config('services.momo.secret_key');
            $signature = $this->buildWebhookSignature($data, $secretKey);

            if (empty($data['signature']) || $signature !== $data['signature']) {
                return response()->json(['resultCode' => 97, 'message' => 'Invalid signature'], 400);
            }

            $resultCode = (int) ($data['resultCode'] ?? -1);
            $orderId = $data['orderId'] ?? null;
            $message = $data['message'] ?? 'MoMo webhook callback';

            if ($orderId) {
                // Update local MoMo payment record
                $payment = MomoPayment::where('order_id', $orderId)->first();
                if ($payment) {
                    $payment->momo_transaction_id = $data['transId'] ?? $payment->momo_transaction_id;
                    if ($resultCode === 0) {
                        $payment->markAsSuccess($resultCode, $message);
                    } else {
                        $payment->markAsFailed($resultCode, $message);
                    }
                }

                // Optionally also update linked transaction record if exists
                $transaction = Transaction::where('transaction_id', $orderId)->first();
                if ($transaction) {
                    if ($resultCode === 0) {
                        $transaction->update(['status' => 'Đã duyệt']);
                    } else {
                        $transaction->update(['status' => 'Thất bại']);
                    }
                }
            }

            return response()->json(['resultCode' => 0, 'message' => 'Success']);
        } catch (\Throwable $e) {
            Log::error('MoMo webhook error: ' . $e->getMessage());
            return response()->json(['resultCode' => 99, 'message' => 'Error'], 500);
        }
    }

    private function createHMAC(string $data, string $key): string
    {
        $hash = hash_hmac('sha256', $data, $key, true);
        return bin2hex($hash);
    }

    private function buildWebhookSignature(array $data, string $secretKey): string
    {
        $parts = [
            'accessKey', 'amount', 'extraData', 'message', 'orderId', 'orderInfo',
            'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime',
            'resultCode', 'transId',
        ];
        $arr = [];
        foreach ($parts as $p) {
            $arr[] = $p . '=' . ($data[$p] ?? '');
        }
        return $this->createHMAC(implode('&', $arr), $secretKey);
    }

    public function callback(Request $request)
    {
        $data = $request->all();
        $secretKey = config('services.momo.secret_key');
        $signature = $this->buildCallbackSignature($data, $secretKey);

        if (empty($data['signature']) || $signature !== $data['signature']) {
            return response()->json(['resultCode' => 97, 'message' => 'Invalid signature'], 400);
        }

        return response()->json(['resultCode' => 0, 'message' => 'Success']);
    }

    private function buildCallbackSignature(array $data, string $secretKey): string
    {
        // Sort keys alphabetically and build key=value string (excluding provided signature)
        ksort($data);
        $parts = [];
        foreach ($data as $key => $value) {
            if ($key === 'signature') {
                continue;
            }
            $parts[] = $key . '=' . $value;
        }

        return $this->createHMAC(implode('&', $parts), $secretKey);
    }

    public function webhookCallback(Request $request)
    {
        $data = $request->all();
        $secretKey = config('services.momo.secret_key');
        $signature = $this->buildWebhookSignature($data, $secretKey);

        if (empty($data['signature']) || $signature !== $data['signature']) {
            return response()->json(['resultCode' => 97, 'message' => 'Invalid signature'], 400);
        }

        return response()->json(['resultCode' => 0, 'message' => 'Success']);
    }

    /**
     * Check payment status by MoMo orderId (polled from frontend).
     */
    public function checkStatus(string $orderId)
    {
        $payment = MomoPayment::where('order_id', $orderId)->first();

        if (! $payment) {
            return response()->json([
                'status' => 'not_found',
                'resultCode' => 1,
                'message' => 'Payment not found',
            ], 404);
        }

        return response()->json([
            'status' => $payment->status,
            'resultCode' => $payment->result_code,
            'message' => $payment->message,
        ]);
    }
}
