<?php

use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/plugins')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/plugins')->name('dashboard');
    Route::get('plugins', [MarketplaceController::class, 'show'])->name('plugins');
    Route::post('plugins/regenerate', [MarketplaceController::class, 'regenerate'])->name('plugins.regenerate');
    Route::get('admin/marketplace', [MarketplaceController::class, 'admin'])->name('marketplace.admin');
});

require __DIR__.'/settings.php';
