<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Agent;
use App\Models\Transaction;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class TransactionsQuery extends Query
{
    protected $attributes = [
        'name' => 'transactions',
        'description' => 'Danh sách giao dịch (có lọc, phân trang)',
    ];

    public function type(): Type
    {
        return GraphQL::type('TransactionPaginated');
    }

    public function args(): array
    {
        return [
            'agent_id' => ['type' => Type::int()],
            'status' => ['type' => Type::string()],
            'date_from' => ['type' => Type::string()],
            'date_to' => ['type' => Type::string()],
            'transaction_type' => ['type' => Type::string()],
            'page' => ['type' => Type::int(), 'defaultValue' => 1],
            'per_page' => ['type' => Type::int(), 'defaultValue' => 15],
        ];
    }

    public function resolve($root, array $args, $context, ResolveInfo $resolveInfo, Closure $getSelectFields)
    {
        // Get authenticated user
        $user = auth()->user();

        if (!$user) {
            throw new \Exception('Unauthenticated');
        }

        $query = Transaction::with(['agent', 'user']);

        // Filter by agent_id if provided
        if (isset($args['agent_id'])) {
            $agent = Agent::find($args['agent_id']);

            if (!$agent) {
                throw new \Exception('Agent not found');
            }

            if (!$user->hasAgentAccess($agent)) {
                throw new \Exception('Unauthorized access to agent');
            }

            $query->where('agent_id', $agent->id);
        } else {
            // Only show transactions for agents user has access to
            if (!$user->isAdmin()) {
                $agentIds = $user->agents()->pluck('agents.id')->toArray();

                if (empty($agentIds)) {
                    // User has no agents, return empty result
                    return [
                        'data' => [],
                        'paginatorInfo' => [
                            'currentPage' => 1,
                            'lastPage' => 1,
                            'perPage' => $args['per_page'],
                            'total' => 0,
                            'count' => 0,
                            'firstItem' => null,
                            'lastItem' => null,
                            'hasMorePages' => false,
                        ]
                    ];
                }

                $query->whereIn('agent_id', $agentIds);
            }
        }

        // Filter by status
        if (isset($args['status']) && $args['status'] !== '') {
            $query->where('status', $args['status']);
        }

        // Filter by date range
        if (isset($args['date_from']) && isset($args['date_to'])) {
            $query->whereBetween('transaction_date', [
                $args['date_from'],
                $args['date_to']
            ]);
        }

        // Filter by transaction type
        if (isset($args['transaction_type']) && $args['transaction_type'] !== '') {
            $query->where('transaction_type', $args['transaction_type']);
        }

        // Order by latest transaction date
        $query->orderBy('transaction_date', 'desc');

        // Paginate results
        $perPage = $args['per_page'];
        $page = $args['page'];

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Return in the format expected by TransactionPaginated type
        return [
            'data' => $paginator->items(),
            'paginatorInfo' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'firstItem' => $paginator->firstItem(),
                'lastItem' => $paginator->lastItem(),
                'hasMorePages' => $paginator->hasMorePages(),
            ]
        ];
    }
}
