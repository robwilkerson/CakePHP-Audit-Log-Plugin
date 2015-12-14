<?php
use Cake\Routing\Router;

Router::plugin(
    'AuditLog',
    ['path' => '/audit-log'],
    function ($routes) {
        $routes->fallbacks('DashedRoute');
    }
);
