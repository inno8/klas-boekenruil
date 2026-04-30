<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\SwapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('books', BookController::class);

Route::post('/books/{book}/swap', [SwapController::class, 'requestSwap'])->name('swaps.request');
Route::get('/my/swaps', [SwapController::class, 'myRequests'])->name('swaps.mine');
Route::post('/swaps/{swap}/accept', [SwapController::class, 'acceptSwap'])->name('swaps.accept');
Route::post('/swaps/{swap}/reject', [SwapController::class, 'rejectSwap'])->name('swaps.reject');
