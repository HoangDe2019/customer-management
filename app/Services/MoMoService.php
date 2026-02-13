<?php

namespace App\Services;

use App\Models\MomoPayment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoMoService
{
    /**
     * Generate MoMo QR code for payment
     */
    public function generateQR(string $customerName, float $totalAmount, string $transactionType): array
    {
        $amount = (int) $totalAmount;
        $orderInfo = 'Thanh toan ' . $transactionType . ' - ' . $customerName;

        // Create initial MoMo payment record with pending status
        $payment = MomoPayment::createWithQR([
            'transaction_id'   => null,
            'customer_name'    => $customerName,
            'amount'           => $amount,
            'transaction_type' => $transactionType,
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
                'status' => $resultCode == 0 ? 'pending' : 'failed',
            ]);

            if ($response->successful() && $resultCode == 0) {
                return [
                    'success' => true,
                    'paymentId' => $payment->id,
                    'orderId' => $orderId,
                    'qrCodeUrl' => $payment->qr_code_url,
                    'payUrl' => $payment->pay_url,
                    'deeplink' => $payment->deeplink,
                    'status' => $payment->status,
                    'resultCode' => $resultCode,
                    'message' => $message,
                ];
            }

            return [
                'success' => false,
                'paymentId' => $payment->id,
                'orderId' => $orderId,
                'qrCodeUrl' => null,
                'payUrl' => null,
                'deeplink' => null,
                'status' => 'failed',
                'resultCode' => $resultCode,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            Log::error('MoMo generateQR error: ' . $e->getMessage());

            $payment->update([
                'status' => 'failed',
                'message' => 'Exception when calling MoMo: ' . $e->getMessage(),
            ]);

            return [
                'success' => false,
                'paymentId' => $payment->id,
                'orderId' => $orderId,
                'qrCodeUrl' => null,
                'payUrl' => null,
                'deeplink' => null,
                'status' => 'failed',
                'resultCode' => -1,
                'message' => 'Không thể kết nối tới MoMo',
            ];
        }
    }

    /**
     * Process webhook callback from MoMo
     */
    public function processWebhook(array $data): array
    {
        $resultCode = (int) ($data['resultCode'] ?? -1);
        $orderId = $data['orderId'] ?? null;
        $message = $data['message'] ?? 'MoMo webhook callback';

        if (!$orderId) {
            return [
                'resultCode' => 98,
                'message' => 'Missing orderId',
            ];
        }

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

        return [
            'resultCode' => 0,
            'message' => 'Success',
        ];
    }

    /**
     * Check payment status by orderId
     */
    public function checkStatus(string $orderId): array
    {
        $payment = MomoPayment::where('order_id', $orderId)->first();

        if (!$payment) {
            return [
                'found' => false,
                'status' => 'not_found',
                'resultCode' => 1,
                'message' => 'Payment not found',
            ];
        }

        return [
            'found' => true,
            'status' => $payment->status,
            'resultCode' => $payment->result_code ?? 0,
            'message' => $payment->message ?? 'OK',
        ];
    }

    /**
     * Create HMAC signature
     */
    public function createHMAC(string $data, string $key): string
    {
        $hash = hash_hmac('sha256', $data, $key, true);
        return bin2hex($hash);
    }

    /**
     * Build webhook signature for verification
     */
    public function buildWebhookSignature(array $data, string $secretKey): string
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

    /**
     * Build callback signature for verification
     */
    public function buildCallbackSignature(array $data, string $secretKey): string
    {
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

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(array $data): bool
    {
        $secretKey = config('services.momo.secret_key');
        $providedSignature = $data['signature'] ?? '';

        if (empty($providedSignature)) {
            return false;
        }

        $calculatedSignature = $this->buildWebhookSignature($data, $secretKey);
        return hash_equals($calculatedSignature, $providedSignature);
    }

    /**
     * Verify callback signature
     */
    public function verifyCallbackSignature(array $data): bool
    {
        $secretKey = config('services.momo.secret_key');
        $providedSignature = $data['signature'] ?? '';

        if (empty($providedSignature)) {
            return false;
        }

        $calculatedSignature = $this->buildCallbackSignature($data, $secretKey);
        return hash_equals($calculatedSignature, $providedSignature);
    }
}
