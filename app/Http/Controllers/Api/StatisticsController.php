<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Statistics for an agent (only completed transactions - mirrors getStatistics in GAS).
     */
    public function index(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'period' => 'nullable|in:all,today,3days,week,month',
        ]);
        $agent = Agent::findOrFail($request->agent_id);
        if (!$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Bạn không có quyền truy cập đại lý này'], 403);
        }

        $period = $request->get('period', 'all');
        $query = Transaction::where('agent_id', $agent->id)->where('status', 'Hoàn thành');

        $query = $this->applyPeriodFilter($query, $period);

        $totalAmount = (float) (clone $query)->sum('total_amount');
        $totalProfit = (float) (clone $query)->sum('profit');
        $totalAgentAdvance = (float) (clone $query)->sum('agent_advance');
        $daoCount = (clone $query)->where('transaction_type', 'Đáo')->count();
        $rutCount = (clone $query)->where('transaction_type', 'Rút')->count();
        $completedCount = (clone $query)->count();

        $byStatus = Transaction::where('agent_id', $agent->id)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $recentQuery = Transaction::where('agent_id', $agent->id)
            ->whereNotNull('customer_name')
            ->whereNotNull('total_amount')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get(['customer_name', 'total_amount', 'transaction_type', 'transaction_date', 'status']);
        $recent = $recentQuery->map(fn ($t) => [
            'name' => $t->customer_name,
            'amount' => (float) $t->total_amount,
            'type' => $t->transaction_type,
            'date' => $t->transaction_date?->timezone('Asia/Bangkok')->format('d/m/Y H:i:s'),
            'status' => $t->status,
        ])->values()->toArray();

        return response()->json([
            'total_transactions' => $completedCount,
            'total_amount' => $totalAmount,
            'total_profit' => $totalProfit,
            'total_agent_advance' => $totalAgentAdvance,
            'dao_count' => $daoCount,
            'rut_count' => $rutCount,
            'recent' => $recent,
            'by_status' => $byStatus,
        ]);
    }

    /**
     * Summary for dashboard (optional).
     */
    public function summary(Request $request)
    {
        $agents = $request->user()->is_admin
            ? Agent::active()->get()
            : $request->user()->getAccessibleAgents();

        $data = $agents->map(function (Agent $agent) {
            $stats = $agent->getStatistics('all');
            return [
                'agent_id' => $agent->agent_id,
                'name' => $agent->name,
                'total_transactions' => $stats['total_transactions'],
                'total_amount' => $stats['total_amount'],
                'total_profit' => $stats['total_profit'],
            ];
        });
        return response()->json($data);
    }

    private function applyPeriodFilter($query, string $period)
    {
        $now = Carbon::now();
        switch ($period) {
            case 'today':
                return $query->whereDate('transaction_date', $now->toDateString());
            case '3days':
                return $query->where('transaction_date', '>=', $now->copy()->subDays(3));
            case 'week':
                return $query->where('transaction_date', '>=', $now->copy()->subWeek());
            case 'month':
                return $query->whereMonth('transaction_date', $now->month)
                    ->whereYear('transaction_date', $now->year);
            default:
                return $query;
        }
    }
}
