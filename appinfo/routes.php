<?php
declare(strict_types=1);

return ['routes' => [
    ['name' => 'indicator#index', 'url' => '/api/v1/indicators', 'verb' => 'GET'],
    ['name' => 'admin#status', 'url' => '/api/v1/admin/status', 'verb' => 'GET'],
    ['name' => 'admin#rebuild', 'url' => '/api/v1/admin/rebuild', 'verb' => 'POST'],
]];
