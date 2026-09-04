<?php

Route::livewire('/register', 'pages::auth.register')->name('register');

Route::middleware('auth')->group(function () {
    Route::livewire('/select-role', 'pages::auth.select-context')->name('role-context.select');
});
