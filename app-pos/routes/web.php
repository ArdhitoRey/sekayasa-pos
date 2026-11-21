<?php

use Illuminate\Support\Facades\Route;

// Public 4-Page Editorial Website (DESIGN_SYSTEM.md: Seka Yasa Warm Javanese Modern Studio)
Route::get('/', function () {
    return view('site.home');
})->name('home');

Route::get('/projects', function () {
    return view('site.projects');
})->name('projects');

Route::get('/about', function () {
    return view('site.about');
})->name('about');

Route::get('/contact', function () {
    return view('site.contact');
})->name('contact');

// Operational Applications
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/client', function () {
    return view('client.index');
})->name('client');

Route::get('/pos', function () {
    return view('pos.index');
})->name('pos');

Route::get('/dashboard', function () {
    return view('dashboard.index');
})->name('dashboard');
