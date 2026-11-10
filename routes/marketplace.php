<?php

use App\Http\Controllers\MarketplaceGitController;
use Illuminate\Support\Facades\Route;

// Deliberately outside web: Git never starts sessions or receives cookies/CSRF challenges.
Route::any('/m/{token}/marketplace.git/{path?}', MarketplaceGitController::class)
    ->where('path', '.*')->name('marketplace.git');
