<?php

use App\Http\Controllers\ExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/exports/users.{format}', [ExportController::class, 'users'])
    ->whereIn('format', ['csv', 'json', 'xls', 'pdf'])
    ->name('exports.users');
