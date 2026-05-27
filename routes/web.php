<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::view('/3-basico', '3-basic')->name('3-basic');
Route::view('/3-livre', '3-free')->name('3-free');
Route::view('/3-rotacao', '3-rotation')->name('3-rotation');
Route::view('/4-3-normal', '4x3-normal')->name('4x3-normal');
Route::view('/4-3-rotacao', '4x3-rotation')->name('4x3-rotation');
// FALTA 4x3 escolhendo onde vai tirar a peça
Route::view('/4-3-bloqueio', '4x4-block')->name('4x4-block');
Route::view('/4-rotacao', '4x4-rotation')->name('4x4-rotation');
Route::view('/5-cheia', '5-full')->name('5-full');
Route::view('/dado', 'dice')->name('dice');
Route::view('/maluca', 'crazy')->name('crazy');
Route::view('/ultimate', 'ultimate')->name('ultimate');