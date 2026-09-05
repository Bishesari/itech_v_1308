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
//'C1407D9A-998C-4C8A-99FA-F38CD24FA212--C1407D9A-998C-4C8A-99FA-F38CD24FA212'
