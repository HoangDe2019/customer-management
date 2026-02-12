<?php
// app/Http/Controllers/Api/TransactionController.php

namespace App\Http\Controllers\Api;

use App\Events\DataUpdated;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Agent;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $query = Transaction::with(['agent', 'user']);

        // Filter by agent
        if ($request->has('agent_id')) {
            $agent = Agent::findOrFail($request->agent_id);

            // Check access
            if (!$request->user()->hasAgentAccess($agent)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $query->where('agent_id', $agent->id);
        } else {
            // Only show transactions for agents user has access to
            if (!$request->user()->isAdmin()) {
                $agentIds = $request->user()->agents()->pluck('agents.id');
                $query->whereIn('agent_id', $agentIds);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('transaction_date', [
                $request->date_from,
                $request->date_to
            ]);
        }

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $transactions = $query->latest('transaction_date')->paginate($perPage);

        return response()->json($transactions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'customer_name' => 'nullable|string|max:255',
            'cccd_number' => 'nullable|string|max:12',
            'total_amount' => 'required|numeric|min:0',
            'transaction_type' => 'required|in:Đáo,Rút',
            'pos_fee_percent' => 'nullable|numeric',
            'agent_fee_percent' => 'nullable|numeric',
            'agent_advance' => 'nullable|numeric|min:0',
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);

        // Check access
        if (!$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            $transaction = $this->transactionService->createTransaction($validated, $request->user());

            DB::commit();
            event(new DataUpdated('transactions', 'created'));

            return response()->json($transaction->load(['agent', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to create transaction',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Transaction $transaction)
    {
        // Check access
        if (!request()->user()->hasAgentAccess($transaction->agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($transaction->load(['agent', 'user', 'dailyAdvances', 'logs']));
    }

    public function updateStatus(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'required|in:Chờ duyệt,Đã duyệt,Đang xử lý,Chờ DR,Hoàn thành,Thất bại,Đã hủy'
        ]);

        // Check access
        if (!$request->user()->hasAgentAccess($transaction->agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $oldStatus = $transaction->status;
        $transaction->update(['status' => $request->status]);

        // Log the change
        $transaction->logs()->create([
            'agent_id' => $transaction->agent_id,
            'user_id' => $request->user()->id,
            'action' => 'status_changed',
            'old_value' => $oldStatus,
            'new_value' => $request->status,
        ]);
        event(new DataUpdated('transactions', 'updated'));

        return response()->json($transaction);
    }

    public function export(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date_from' => 'required|date_format:d/m/Y',
            'date_to' => 'required|date_format:d/m/Y',
            'status' => 'nullable|string|in:all,Chờ duyệt,Đã duyệt,Đang xử lý,Chờ DR,Hoàn thành,Thất bại,Đã hủy',
            'type' => 'nullable|string|in:all,Đáo,Rút',
        ]);

        $agent = Agent::findOrFail($request->agent_id);
        if (!$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $from = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_from)->startOfDay();
        $to = \Carbon\Carbon::createFromFormat('d/m/Y', $request->date_to)->endOfDay();

        $query = Transaction::where('agent_id', $agent->id)
            ->whereBetween('transaction_date', [$from, $to]);
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->type && $request->type !== 'all') {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderBy('transaction_date')->get();
        $summary = [
            'total_amount' => (float) $transactions->sum('total_amount'),
            'total_profit' => (float) $transactions->sum('profit'),
            'total_refund_to_agent' => (float) $transactions->sum('refund_to_agent'),
            'total_advance' => (float) $transactions->sum('agent_advance'),
            'net_settlement' => (float) $transactions->sum('net_settlement'),
        ];

        return response()->json([
            'success' => true,
            'record_count' => $transactions->count(),
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'summary' => $summary,
            'transactions' => $transactions->toArray(),
        ]);
    }

    public function scanCCCD(Request $request)
    {
        $request->validate([
            'image' => 'required|string', // Base64 image
        ]);

        // Call Gemini API similar to scanCCCD function
        $result = $this->transactionService->scanCCCD($request->image);

        return response()->json($result);
    }
}
