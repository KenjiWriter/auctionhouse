<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'loginPage'])->name('login');
    Route::post('login/magic', [AuthController::class, 'sendMagicLink'])->name('login.magic');
    Route::get('login/verify/{user}', [AuthController::class, 'verifyMagicLink'])->name('login.verify');
});

Route::middleware('auth')->group(function () {
    Route::get('register/complete', [AuthController::class, 'completeProfilePage'])->name('register.complete');
    Route::post('register/complete', [AuthController::class, 'completeProfile']);
    
    Route::get('my-auctions', [\App\Http\Controllers\AuctionsController::class, 'myAuctions'])->name('auctions.mine');
    Route::get('my-wins', [\App\Http\Controllers\AuctionsController::class, 'myWins'])->name('auctions.wins');
    Route::resource('auctions', \App\Http\Controllers\AuctionsController::class);
    Route::post('auctions/{auction}/bid', [\App\Http\Controllers\AuctionsController::class, 'bid'])->name('auctions.bid');
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
