<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('chat', function () {
    return Inertia::render('Chat');
})->name('chat');

Route::get('chatbot', function () {
    return Inertia::render('Chatbot');
})->name('chatbot');

require __DIR__.'/settings.php';
