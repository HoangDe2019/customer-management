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

        $requestId = 'MOMO_' . time();
        $orderId = $requestId;
        $orderInfo = 'Thanh toan ' . $request->transaction_type . ' - ' . $request->customer_name;
        $amount = (int) $request->total_amount;
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

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->withOptions(['verify' => false])
            ->timeout(120)
            ->post($apiUrl, $body);

        $result = $response->json();
        $resultCode = $result['resultCode'] ?? -1;
        $message = $result['message'] ?? 'Unknown error';

        if ($response->successful() && ($resultCode === 0 || $resultCode === '0')) {
            return response()->json([
                'qrCodeUrl' => $result['qrCodeUrl'] ?? null,
                'payUrl' => $result['payUrl'] ?? null,
                'deeplink' => $result['deeplink'] ?? null,
                'resultCode' => $resultCode,
                'message' => $message,
            ]);
        }

        return response()->json([
            'resultCode' => $resultCode,
            'message' => $message,
            'qrCodeUrl' => null,
            'payUrl' => null,
            'deeplink' => null,
        ], 400);
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

            if ($orderId) {
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
        return $this->createHMAC(implode('&', $data), $secretKey);
    }
}
