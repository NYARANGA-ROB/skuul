<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/test-db', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();
        
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        
        // Get users count
        $usersCount = DB::table('users')->count();
        
        return response()->json([
            'status' => 'success',
            'database' => DB::connection()->getDatabaseName(),
            'tables' => $tables,
            'users_count' => $usersCount
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'config' => [
                'database' => config('database.connections.mysql.database'),
                'username' => config('database.connections.mysql.username'),
                'host' => config('database.connections.mysql.host'),
                'port' => config('database.connections.mysql.port')
            ]
        ], 500);
    }
});
