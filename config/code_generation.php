<?php

return [
    'separator' => '-',
    'sequence_length' => (int) env('CODE_SEQUENCE_LENGTH', 4),
    'max_create_attempts' => 3,

    'prefixes' => [
        'factory_unit' => env('CODE_PREFIX_FACTORY_UNIT', 'FU'),
        'employee' => env('CODE_PREFIX_EMPLOYEE', 'EMP'),
        'location' => env('CODE_PREFIX_LOCATION', 'LOC'),
        'professional_role' => env('CODE_PREFIX_PROFESSIONAL_ROLE', 'ROLE'),
        'product' => env('CODE_PREFIX_PRODUCT', 'PRD'),
        'material' => env('CODE_PREFIX_MATERIAL', 'MAT'),
        'customer' => env('CODE_PREFIX_CUSTOMER', 'CUST'),
        'supplier' => env('CODE_PREFIX_SUPPLIER', 'SUP'),
    ],
];
