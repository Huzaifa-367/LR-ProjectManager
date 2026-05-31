<?php

use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('user-roles', [UserRoleController::class, 'index'])
        ->name('user-roles.index');

    Route::post('user-roles', [UserRoleController::class, 'store'])
        ->name('user-roles.store');

    Route::put('user-roles/{user}', [UserRoleController::class, 'update'])
        ->name('user-roles.update');

    Route::resource('roles', RoleController::class)
        ->except(['show']);
});

require __DIR__.'/command_centre.php';
require __DIR__.'/settings.php';
