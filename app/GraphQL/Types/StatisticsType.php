<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Type as GraphQLType;

class StatisticsType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Statistics',
        'description' => 'Thống kê',
    ];

    public function fields(): array
    {
        return [
            'total_transactions' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Tổng số giao dịch hoàn thành',
            ],
            'total_amount' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Tổng số tiền',
            ],
            'total_profit' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Tổng lợi nhuận',
            ],
            'total_agent_advance' => [
                'type' => Type::nonNull(Type::float()),
                'description' => 'Tổng ứng trước đại lý',
            ],
            'dao_count' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Số giao dịch Đáo',
            ],
            'rut_count' => [
                'type' => Type::nonNull(Type::int()),
                'description' => 'Số giao dịch Rút',
            ],
            'recent' => [
                'type' => Type::nonNull(Type::listOf(GraphQL::type('RecentStatsItem'))),
                'description' => 'Giao dịch gần đây (10 giao dịch)',
            ],
            'by_status' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'JSON object: status -> count',
            ],
        ];
    }
}