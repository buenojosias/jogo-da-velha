<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::view('/3-basico', '3-basic')->name('3-basic');
Route::view('/3-mover', '3-move')->name('3-move');
Route::view('/3-rotacao', '3-rotation')->name('3-rotation');
Route::view('/4-3-basico', '4x3-basic')->name('4x3-basic');
Route::view('/4-3-mover', '4x3-move')->name('4x3-move');
Route::view('/4-3-rotacao', '4x3-rotation')->name('4x3-rotation');
Route::view('/4-3-bloqueio', '4x3-block')->name('4x3-block');
Route::view('/4-rotacao', '4x4-rotation')->name('4x4-rotation');
Route::view('/5-cheia', '5-full')->name('5-full');
Route::view('/dado', 'dice')->name('dice');
Route::view('/maluca', 'crazy')->name('crazy');
Route::view('/ultimate', 'ultimate')->name('ultimate');

Route::view('/teste', 'teste')->name('teste');