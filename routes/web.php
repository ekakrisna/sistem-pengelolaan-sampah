<?php
use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//generate users
Route::resource('users', UserController::class);
Route::patch('users/{id}/status', [UserController::class, 'status'])->name('users.status');
Route::get('users-datatable', [UserController::class, 'datatable'])->name('users.datatable');
