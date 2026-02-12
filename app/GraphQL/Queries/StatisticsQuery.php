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
            'period' => ['type' => Type::string()],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        $agent = Agent::find($args['agent_id']);
        if (!$agent || !$user->hasAgentAccess($agent)) {
            return null;
        }

        $period = $args['period'] ?? 'all';
        $query = Transaction::where('agent_id', $agent->id)->where('status', 'Hoàn thành');
        $query = $this->applyPeriod($query, $period);

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

        $recent = Transaction::where('agent_id', $agent->id)
            ->whereNotNull('customer_name')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get(['customer_name', 'total_amount', 'transaction_type', 'transaction_date', 'status'])
            ->map(fn ($t) => [
                'name' => $t->customer_name,
                'amount' => (float) $t->total_amount,
                'type' => $t->transaction_type,
                'date' => $t->transaction_date?->format('d/m/Y H:i:s'),
                'status' => $t->status,
            ])
            ->values()
            ->toArray();

        return [
            'total_transactions' => $completedCount,
            'total_amount' => $totalAmount,
            'total_profit' => $totalProfit,
            'total_agent_advance' => $totalAgentAdvance,
            'dao_count' => $daoCount,
            'rut_count' => $rutCount,
            'recent' => $recent,
            'by_status' => json_encode($byStatus),
        ];
    }

    private function applyPeriod($query, string $period)
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
                return $query->where('transaction_date', '>=', $now->copy()->subMonth());
            default:
                return $query;
        }
    }
}
