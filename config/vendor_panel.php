<?php

return [
    'navigation' => [
        [
            'group' => 'Overview',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'vendor.dashboard', 'icon' => 'home', 'active' => 'dashboard'],
            ],
        ],
        [
            'group' => 'Commerce',
            'items' => [
                ['label' => 'Products', 'route' => 'vendor.products.index', 'icon' => 'shopping-bag', 'active' => 'products'],
                ['label' => 'Orders', 'route' => 'vendor.orders.index', 'icon' => 'clipboard-list', 'active' => 'orders'],
                ['label' => 'Coupons', 'route' => 'vendor.coupons.index', 'icon' => 'discount-2', 'active' => 'coupons'],
            ],
        ],
    ],
];
