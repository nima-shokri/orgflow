<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OperatonController;
use App\Http\Controllers\ProcessDefinitionController;
use App\Http\Controllers\ProcessRuntimeController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'ok',
        'stage' => 'stage-01-bootstrap',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/health', function () {
    return response()->json([
        'app' => config('app.name'),
        'php' => PHP_VERSION,
        'laravel' => app()->version(),
        'database' => config('database.default'),
        'queue' => config('queue.default'),
        'cache' => config('cache.default'),
        'status' => 'healthy',
    ]);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin', function () {
        return view('admin');
    })->middleware('role:admin')->name('admin.dashboard');

    Route::middleware('role:admin')
        ->prefix('process-definitions')
        ->name('process-definitions.')
        ->group(function () {
            Route::get('/', [ProcessDefinitionController::class, 'index'])->name('index');
            Route::get('/create', [ProcessDefinitionController::class, 'create'])->name('create');
            Route::post('/', [ProcessDefinitionController::class, 'store'])->name('store');
            Route::get('/{processDefinition}', [ProcessDefinitionController::class, 'show'])->name('show');
            Route::post('/{processDefinition}/publish', [ProcessDefinitionController::class, 'publish'])->name('publish');
            Route::post('/{processDefinition}/deploy', [OperatonController::class, 'deploy'])->name('deploy');
            Route::post('/{processDefinition}/start', [ProcessRuntimeController::class, 'start'])->name('start');
            Route::get('/{processDefinition}/versions/create', [ProcessDefinitionController::class, 'createVersion'])->name('versions.create');
            Route::post('/{processDefinition}/versions', [ProcessDefinitionController::class, 'storeVersion'])->name('versions.store');
        });

    Route::get('/operaton', [OperatonController::class, 'dashboard'])
        ->middleware('role:admin')
        ->name('operaton.dashboard');

    Route::middleware('role:admin')
        ->prefix('runtime')
        ->name('runtime.')
        ->group(function () {
            Route::get('/instances', [ProcessRuntimeController::class, 'index'])->name('instances.index');
            Route::get('/instances/{instanceId}', [ProcessRuntimeController::class, 'show'])->name('instances.show');
        });

    Route::middleware('role:admin')
        ->prefix('tasks')
        ->name('tasks.')
        ->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::get('/{taskId}', [TaskController::class, 'show'])->name('show');
            Route::post('/{taskId}/claim', [TaskController::class, 'claim'])->name('claim');
            Route::post('/{taskId}/release', [TaskController::class, 'release'])->name('release');
            Route::post('/{taskId}/complete', [TaskController::class, 'complete'])->name('complete');
        });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
