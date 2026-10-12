<?php

use App\Games\Rules\Classic4x3Rules;
use App\Games\Rules\Move4x3Rules;
use App\Http\ClassicMatchManager;
use App\Http\MovementMatchManager;
use App\Http\SessionMatchStore;
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
Route::view('/3-rotacao', '3-rotation')->name('3-rotation');
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
Route::view('/4-3-rotacao', '4x3-rotation')->name('4x3-rotation');
Route::view('/4-3-bloqueio', '4x3-block')->name('4x3-block');
Route::view('/4-3-oculto', '4x3-hidden')->name('4x3-hidden');
Route::view('/4-mover', '4x4-move')->name('4x4-move');
Route::view('/2-linhas', '5x5-2-lines')->name('5x5-2-lines');
Route::view('/5-cheia', '5-full')->name('5-full');
Route::view('/dado', 'dice')->name('dice');
Route::view('/maluca', 'crazy')->name('crazy');
Route::view('/ultimate', 'ultimate')->name('ultimate');

if (app()->environment(['local', 'testing'])) {
    Route::view('/livewire-reference', 'livewire-reference')->name('livewire.reference');

    Route::get('/experimental/3-basico', function (ClassicMatchManager $matches) {
        return redirect()->route('experimental.classic.show', ['matchId' => $matches->create()]);
    })->name('experimental.classic.create');

    Route::get('/experimental/3-basico/{matchId}', function (string $matchId, SessionMatchStore $store) {
        abort_if($store->find($matchId) === null, 404);

        return view('experimental-classic', ['matchId' => $matchId]);
    })->where('matchId', '[A-Za-z0-9]{40}')->name('experimental.classic.show');

    Route::get('/experimental/4-3-basico', function (ClassicMatchManager $matches, Classic4x3Rules $rules) {
        return redirect()->route('experimental.classic4x3.show', ['matchId' => $matches->create4x3($rules)]);
    })->name('experimental.classic4x3.create');

    Route::get('/experimental/4-3-basico/{matchId}', function (string $matchId, SessionMatchStore $store) {
        try {
            $state = $store->find($matchId);
            $store->startingPlayer($matchId);
            $store->score($matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }

        abort_if($state === null || $state->modality->key !== '4x3-basic', 404);

        return view('experimental-classic-4x3', ['matchId' => $matchId]);
    })->where('matchId', '[A-Za-z0-9]{40}')->name('experimental.classic4x3.show');

    Route::get('/experimental/3-mover', function (MovementMatchManager $matches) {
        return redirect()->route('experimental.movement.show', ['matchId' => $matches->create()]);
    })->name('experimental.movement.create');

    Route::get('/experimental/3-mover/{matchId}', function (string $matchId, SessionMatchStore $store) {
        try {
            $state = $store->find($matchId);
            $store->startingPlayer($matchId);
            $store->score($matchId);
        } catch (InvalidArgumentException|UnexpectedValueException) {
            abort(404);
        }

        abort_if($state === null || $state->modality->key !== '3-move', 404);

        return view('experimental-move-3x3', ['matchId' => $matchId]);
    })->where('matchId', '[A-Za-z0-9]{40}')->name('experimental.movement.show');
}

Route::view('/teste', 'teste')->name('teste');
