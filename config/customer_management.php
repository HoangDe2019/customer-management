<?php

return [
    'version' => '5.0.0',
    'timezone' => env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'admin_email' => env('ADMIN_EMAIL', 'lachithao123vn@gmail.com'),
    'default_pos_fee_percent' => (float) env('DEFAULT_POS_FEE', 1.067),
    'default_agent_fee_percent' => (float) env('DEFAULT_AGENT_FEE', 1.3),
    'status_values' => [
        'Chờ duyệt',
        'Đã duyệt',
        'Đang xử lý',
        'Chờ DR',
        'Hoàn thành',
        'Thất bại',
        'Đã hủy',
    ],
    'amount_suggestions' => [
        1_000_000,
        2_000_000,
        5_000_000,
        10_000_000,
        20_000_000,
        50_000_000,
        100_000_000,
    ],
];
