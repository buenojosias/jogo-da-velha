<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/basic', 'basic');
Route::view('/3-livre', '3-free');
Route::view('/3-rotacao', '3-rotation');
Route::view('/4-padrao', '4x4-default');
Route::view('/4-4-3', '4x4-default');
Route::view('/5-cheia', '5-full');