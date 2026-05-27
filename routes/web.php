<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/3-basico', '3-basic');
Route::view('/3-livre', '3-free');
Route::view('/3-rotacao', '3-rotation');
Route::view('/4-4-3', '4x4-default');
Route::view('/4-3-normal', '4x3-normal');
Route::view('/4-3-rotacao', '4x3-rotation');
Route::view('/4-3-bloqueio', '4x4-block');
Route::view('/4-padrao', '4x4-default');
Route::view('/5-cheia', '5-full');