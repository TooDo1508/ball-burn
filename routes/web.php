<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/about', 'welcome');
Route::view('/blog', 'welcome');
Route::view('/booking', 'welcome');
Route::view('/contact', 'welcome');
