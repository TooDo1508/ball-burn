<?php

use App\Http\Controllers\Court\CourtController;
use Illuminate\Support\Facades\Route;

Route::get('/courts', [CourtController::class, 'index'])->name('courts.index');
Route::get('/courts/{court:slug}', [CourtController::class, 'show'])->name('courts.show');
