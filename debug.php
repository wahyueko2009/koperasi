<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check users
$users = \App\Models\User::all();
echo "Total Users: " . count($users) . "\n";
foreach ($users as $user) {
    echo "- {$user->email} ({$user->role})\n";
}

// Check routes
$routes = app('router')->getRoutes();
echo "\nTotal Routes: " . count($routes) . "\n";
foreach ($routes->getRoutes() as $route) {
    if (strpos($route->uri, 'api/login') !== false) {
        echo "Found: " . $route->uri . " [" . implode(',', $route->methods()) . "]\n";
    }
}
