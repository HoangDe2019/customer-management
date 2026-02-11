<?php
// app/Services/TransactionService.php

namespace App\Services;

use App\Models\Transaction;
use App\Models\DailyAdvance;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TransactionService
{
    public function createTransaction(array $data, User $user): Transaction
    {
        // Auto-generate customer name if empty
        if (empty($data['customer_name'])) {
            $data['customer_name'] = $this->generateUniqueCustomerId();
        }

        // Generate transaction ID
        $transactionId = $this->generateTransactionId();

        // Calculate fees
        $calculations = $this->calculateFees($data);

        // Create transaction
        $transaction = Transaction::create([
            'transaction_id' => $transactionId,
            'agent_id' => $data['agent_id'],
            'user_id' => $user->id,
            'customer_name' => $data['customer_name'],
            'cccd_number' => $data['cccd_number'] ?? null,
            'total_amount' => $data['total_amount'],
            'transaction_type' => $data['transaction_type'],
            'pos_fee_percent' => $data['pos_fee_percent'] ?? 1.067,
            'agent_fee_percent' => $data['agent_fee_percent'] ?? 1.3,
            'pos_fee_amount' => $calculations['pos_fee'],
            'agent_fee_amount' => $calculations['agent_fee'],
            'profit' => $calculations['profit'],
            'refund_to_agent' => $calculations['refund_to_agent'],
            'agent_advance' => $data['agent_advance'] ?? 0,
            'net_settlement' => $calculations['refund_to_agent'] - ($data['agent_advance'] ?? 0),
            'status' => 'Chờ duyệt',
            'transaction_date' => now(),
        ]);

        // Record daily advance if applicable
        if (($data['agent_advance'] ?? 0) > 0) {
            $this->recordDailyAdvance($transaction, $data['agent_advance']);
        }

        // Log transaction creation
        $transaction->logs()->create([
            'agent_id' => $transaction->agent_id,
            'user_id' => $user->id,
            'action' => 'created',
            'metadata' => json_encode($data),
        ]);

        return $transaction;
    }

    protected function generateTransactionId(): string
    {
        return 'TXN' . time() . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
    }

    protected function generateUniqueCustomerId(): string
    {
        return 'KH_' . time() . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
    }

    protected function calculateFees(array $data): array
    {
        $amount = $data['total_amount'];
        $posFeePercent = $data['pos_fee_percent'] ?? 1.067;
        $agentFeePercent = $data['agent_fee_percent'] ?? 1.3;

        $posFee = round($amount * ($posFeePercent / 100), 2);
        $agentFee = round($amount * ($agentFeePercent / 100), 2);
        $profit = round($agentFee - $posFee, 2);
        $refundToAgent = round($amount - $agentFee, 2);

        return [
            'pos_fee' => $posFee,
            'agent_fee' => $agentFee,
            'profit' => $profit,
            'refund_to_agent' => $refundToAgent,
        ];
    }

    protected function recordDailyAdvance(Transaction $transaction, float $amount): void
    {
        DailyAdvance::create([
            'advance_date' => $transaction->transaction_date->format('Y-m-d'),
            'agent_id' => $transaction->agent_id,
            'transaction_id' => $transaction->id,
            'advance_amount' => $amount,
            'is_settled' => false,
        ]);
    }

    /**
     * Scan CCCD image using Gemini 2.0 Flash. Accepts raw base64 or data URL.
     */
    public function scanCCCD(string $imageBase64): array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            throw new \Exception('Chưa cấu hình Gemini API Key (GEMINI_API_KEY)');
        }

        // Normalize: strip data URL prefix if present and detect mime type
        $mimeType = 'image/jpeg';
        $data = $imageBase64;
        if (preg_match('/^data:([^;]+);base64,(.+)$/', $imageBase64, $m)) {
            $mimeType = $m[1];
            $data = $m[2];
        }
        if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            $mimeType = 'image/jpeg';
        }
        $paramsURL = [
            'key' => $apiKey,
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?" . http_build_query($paramsURL);
        $payload = [
            'contents' => [[
                'parts' => [
                    [
                        'text' => 'Đây là ảnh CCCD Việt Nam. Trích xuất chính xác thông tin sau và trả về đúng định dạng JSON (không thêm text thừa, không markdown):' . "\n"
                            . '{"cccdNumber": "số CCCD 12 chữ số", "fullName": "họ và tên đầy đủ (bao gồm dấu tiếng Việt)"}',
                    ],
                    [
                        'inlineData' => [
                            'mimeType' => $mimeType,
                            'data' => $data,
                        ],
                    ],
                ],
            ]],
        ];

        $response = Http::withOptions(['verify' => config('app.disable_ssl_verify') ? false : true])
            ->timeout(120)
            ->post($url, $payload);

        if (!$response->successful()) {
            $body = $response->json();
            $message = $body['error']['message'] ?? $response->body();
            throw new \Exception('Gemini lỗi: ' . $message);
        }

        $result = $response->json();
        if (!empty($result['error'])) {
            throw new \Exception('Gemini lỗi: ' . ($result['error']['message'] ?? 'Unknown'));
        }

        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $jsonMatch = preg_match('/\{[\s\S]*\}/', $text, $matches);

        if ($jsonMatch && !empty($matches[0])) {
            $extracted = json_decode($matches[0], true);
            return [
                'cccdNumber' => $extracted['cccdNumber'] ?? '',
                'fullName' => $extracted['fullName'] ?? '',
                'confidence' => 85,
            ];
        }

        throw new \Exception('Không trích xuất được thông tin từ Gemini');
    }
}
