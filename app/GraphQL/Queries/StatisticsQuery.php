<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Agent;
use App\Models\Transaction;
use Carbon\Carbon;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class StatisticsQuery extends Query
{
    protected $attributes = [
        'name' => 'statistics',
        'description' => 'Thống kê theo đại lý (chỉ giao dịch Hoàn thành)',
    ];

    public function type(): Type
    {
        return GraphQL::type('Statistics');
    }

    public function args(): array
    {
        return [
            'agent_id' => ['type' => Type::nonNull(Type::int())],
            'period' => ['type' => Type::string(), 'defaultValue' => 'all'],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = $context['user'] ?? auth()->user();
        if (!$user) {
            throw new \Exception('Unauthorized');
        }

        // Use findOrFail to match controller behavior
        $agent = Agent::findOrFail($args['agent_id']);
        
        if (!$user->hasAgentAccess($agent)) {
            throw new \Exception('Bạn không có quyền truy cập đại lý này');
        }

        $period = $args['period'] ?? 'all';
        
        // Base query for completed transactions
        $query = Transaction::where('agent_id', $agent->id)
            ->where('status', 'Hoàn thành');
        
        $query = $this->applyPeriodFilter($query, $period);

        // Calculate statistics
        $totalAmount = (float) (clone $query)->sum('total_amount');
        $totalProfit = (float) (clone $query)->sum('profit');
        $totalAgentAdvance = (float) (clone $query)->sum('agent_advance');
        $daoCount = (clone $query)->where('transaction_type', 'Đáo')->count();
        $rutCount = (clone $query)->where('transaction_type', 'Rút')->count();
        $completedCount = (clone $query)->count();

        // Get all status counts (not filtered by period)
        $byStatus = Transaction::where('agent_id', $agent->id)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // Get recent transactions
        $recentTransactions = Transaction::where('agent_id', $agent->id)
            ->whereNotNull('customer_name')
            ->whereNotNull('total_amount')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get(['customer_name', 'total_amount', 'transaction_type', 'transaction_date', 'status']);

        $recent = $recentTransactions->map(function ($t) {
            return [
                'name' => $t->customer_name,
                'amount' => (float) $t->total_amount,
                'type' => $t->transaction_type,
                'date' => $t->transaction_date?->format('d/m/Y H:i:s') ?? null,
                'status' => $t->status,
            ];
        })->values()->toArray();

        return [
            'total_transactions' => $completedCount,
            'total_amount' => $totalAmount,
            'total_profit' => $totalProfit,
            'total_agent_advance' => $totalAgentAdvance,
            'dao_count' => $daoCount,
            'rut_count' => $rutCount,
            'recent' => $recent,
            'by_status' => json_encode($byStatus), // Convert to JSON string to match Type definition
        ];
    }

    /**
     * Apply period filter - matches REST API controller logic exactly
     */
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