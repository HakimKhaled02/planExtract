<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [App\Http\Controllers\ProjectController::class, 'dashboard'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('projects', App\Http\Controllers\ProjectController::class);
    Route::get('/projects/{project}/history', [App\Http\Controllers\ProjectController::class, 'history'])->name('projects.history');
    Route::delete('/projects/{project}/history', [App\Http\Controllers\ProjectController::class, 'deleteHistory'])->name('projects.history.delete');
    Route::get('/projects/{project}/travel-claim', [App\Http\Controllers\ProjectController::class, 'travelClaim'])->name('projects.travel-claim');
    Route::patch('/projects/cells/{cellValue}', [App\Http\Controllers\ProjectController::class, 'updateCell'])->name('projects.cells.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
