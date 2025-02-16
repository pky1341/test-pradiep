<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactFormController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contact', [ContactFormController::class, 'index'])->name('contact.form');
Route::post('/contact', [ContactFormController::class, 'store'])->name('contact.store');
Route::get('/contact/success', [ContactFormController::class, 'success'])->name('contact.success');
