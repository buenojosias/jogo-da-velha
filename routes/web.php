<?php

use App\Games\Modality;
use App\Games\Participant;
use App\Games\ParticipantKind;
use App\Games\Participants;
use App\Games\PlayerMark;
use App\Games\Rules\Classic3x3Rules;
use App\Http\SessionMatchStore;
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
Route::view('/4-3-oculto', '4x3-hidden')->name('4x3-hidden');
Route::view('/4-mover', '4x4-move')->name('4x4-move');
Route::view('/2-linhas', '5x5-2-lines')->name('5x5-2-lines');
Route::view('/5-cheia', '5-full')->name('5-full');
Route::view('/dado', 'dice')->name('dice');
Route::view('/maluca', 'crazy')->name('crazy');
Route::view('/ultimate', 'ultimate')->name('ultimate');

if (app()->environment(['local', 'testing'])) {
    Route::view('/livewire-reference', 'livewire-reference')->name('livewire.reference');

    Route::get('/experimental/3-basico', function (SessionMatchStore $store, Classic3x3Rules $rules) {
        $state = $rules->initialState(
            new Modality('3-basic'),
            new Participants(
                new Participant(ParticipantKind::Guest, 'local-x'),
                new Participant(ParticipantKind::Guest, 'local-o'),
            ),
            PlayerMark::X,
        );

        return redirect()->route('experimental.classic.show', ['matchId' => $store->create($state)]);
    })->name('experimental.classic.create');

    Route::get('/experimental/3-basico/{matchId}', function (string $matchId, SessionMatchStore $store) {
        abort_if($store->find($matchId) === null, 404);

        return view('experimental-classic', ['matchId' => $matchId]);
    })->where('matchId', '[A-Za-z0-9]{40}')->name('experimental.classic.show');
}

Route::view('/teste', 'teste')->name('teste');
