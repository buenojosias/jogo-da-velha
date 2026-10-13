<?php

use App\Games\Rules\Classic4x3Rules;
use App\Games\Rules\Move4x3Rules;
use App\Games\Rules\Move4x4Rules;
use App\Http\BlockMatchManager;
use App\Http\ClassicMatchManager;
use App\Http\CrazyMatchManager;
use App\Http\DiceMatchManager;
use App\Http\FullMatchManager;
use App\Http\HiddenMatchManager;
use App\Http\MovementMatchManager;
use App\Http\RotationMatchManager;
use App\Http\TwoLinesMatchManager;
use App\Http\UltimateMatchManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/3-basico', function (ClassicMatchManager $matches) {
    return view('experimental-classic', ['matchId' => $matches->resumeOrCreate(), 'publicEntry' => true]);
})->name('3-basic');
Route::get('/3-mover', function (MovementMatchManager $matches) {
    return view('experimental-move-3x3', ['matchId' => $matches->resumeOrCreate(), 'publicEntry' => true]);
})->name('3-move');
Route::get('/3-rotacao', function (RotationMatchManager $matches) {
    return view('rotation-match', ['matchId' => $matches->resumeOrCreate3x3(), 'modalityKey' => '3-rotation']);
})->name('3-rotation');
Route::get('/4-3-basico', function (ClassicMatchManager $matches, Classic4x3Rules $rules) {
    return view('experimental-classic-4x3', [
        'matchId' => $matches->resumeOrCreate4x3($rules),
        'publicEntry' => true,
    ]);
})->name('4x3-basic');
Route::get('/4-3-mover', function (MovementMatchManager $matches, Move4x3Rules $rules) {
    return view('experimental-move-4x3', [
        'matchId' => $matches->resumeOrCreate4x3($rules),
        'publicEntry' => true,
    ]);
})->name('4x3-move');
Route::get('/4-3-rotacao', function (RotationMatchManager $matches) {
    return view('rotation-match', ['matchId' => $matches->resumeOrCreate4x3(), 'modalityKey' => '4x3-rotation']);
})->name('4x3-rotation');
Route::get('/4-3-bloqueio', function (BlockMatchManager $matches) {
    return view('block-4x3', ['matchId' => $matches->resumeOrCreate()]);
})->name('4x3-block');
Route::get('/4-3-oculto', function (HiddenMatchManager $matches) {
    return view('hidden-4x3', ['matchId' => $matches->resumeOrCreate()]);
})->name('4x3-hidden');
Route::get('/4-mover', function (MovementMatchManager $matches, Move4x4Rules $rules) {
    return view('experimental-move-4x4', [
        'matchId' => $matches->resumeOrCreate4x4($rules),
        'publicEntry' => true,
    ]);
})->name('4x4-move');
Route::get('/2-linhas', function (TwoLinesMatchManager $matches) {
    return view('two-lines-5x5', ['matchId' => $matches->resumeOrCreate()]);
})->name('5x5-2-lines');
Route::get('/5-cheia', function (FullMatchManager $matches) {
    return view('full-5x5', ['matchId' => $matches->resumeOrCreate()]);
})->name('5-full');
Route::get('/dado', function (DiceMatchManager $matches) {
    return view('dice-match', ['matchId' => $matches->resumeOrCreate()]);
})->name('dice');
Route::get('/maluca', function (CrazyMatchManager $matches) {
    return view('crazy-match', ['matchId' => $matches->resumeOrCreate()]);
})->name('crazy');
Route::get('/ultimate', function (UltimateMatchManager $matches) {
    return view('ultimate-match', ['matchId' => $matches->resumeOrCreate()]);
})->name('ultimate');

if (app()->environment(['local', 'testing'])) {
    Route::view('/livewire-reference', 'livewire-reference')->name('livewire.reference');
}
