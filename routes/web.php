<?php

use App\Services\Authorization\CurrentRoleContextService;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', function (CurrentRoleContextService $contextService) {
        $context = $contextService->current(
            auth()->user()->person
        );
        return view('dashboard', compact('context'));
    })->name('dashboard');
});
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
