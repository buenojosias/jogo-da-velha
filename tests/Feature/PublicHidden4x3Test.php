<?php

use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Http\SessionMatchStore;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Random\Engine\Mt19937;
use Random\Randomizer;

function openPublicHidden(int $seed = 0): string
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    test()->get(route('4x3-hidden'))->assertOk();

    return session('hidden4x3.active_match_id');
}

test('creates and resumes a private match without the legacy script', function (int $seed, PlayerMark $starter) {
    $id = openPublicHidden($seed);
    $response = $this->get(route('4x3-hidden'));

    $response->assertOk()->assertViewIs('hidden-4x3')->assertSee('Vez do jogador '.$starter->name)
        ->assertDontSee('js/game.js')->assertSee('data-testid="new-match-button"', false);
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe(16);
    expect(session('hidden4x3.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with(['X' => [0, PlayerMark::X], 'O' => [1, PlayerMark::O]]);

test('keeps active cells indistinguishable in HTML and Livewire updates', function () {
    $id = openPublicHidden();
    $component = Livewire::test('hidden-4x3', ['matchId' => $id]);
    $component->call('choose', 0)->call('choose', 1);
    expect(app(SessionMatchStore::class)->find($id)->data['board'][0])->toBe('x');
    expect(app(SessionMatchStore::class)->find($id)->data['board'][1])->toBe('o');

    expect(array_keys($component->snapshot['data']))->toEqualCanonicalizing(['matchId', 'feedbackMessage']);
    expect(json_encode($component->snapshot))->not->toContain('"board"', '"order"');
    expect(json_encode($component->effects))->not->toContain('"board"', '"order"', 'data-mark=');
    $document = new DOMDocument;
    @$document->loadHTML($component->html());
    $cells = (new DOMXPath($document))->query('//*[@data-testid and starts-with(@data-testid, "cell-")]');
    expect($cells->length)->toBe(16);
    $classes = [];
    foreach ($cells as $cell) {
        expect($cell->textContent)->toBe('?');
        expect($cell->hasAttribute('data-mark'))->toBeFalse();
        expect($cell->hasAttribute('disabled'))->toBeFalse();
        expect($cell->getAttribute('aria-label'))->toMatch('/^Escolher casa linha [1-4], coluna [1-4]$/');
        $classes[] = $cell->getAttribute('class');
    }
    expect(array_unique($classes))->toHaveCount(1);
    $this->get(route('4x3-hidden'))->assertOk()->assertDontSee('data-mark=', false);
});

test('consumes the turn but leaves board and order unchanged on an opponent piece', function () {
    $id = openPublicHidden();
    $component = Livewire::test('hidden-4x3', ['matchId' => $id]);
    $component->call('choose', 0);
    $before = app(SessionMatchStore::class)->find($id);

    $component->call('choose', 0)->assertSee('Você perdeu a vez.');
    $after = app(SessionMatchStore::class)->find($id);
    expect($after->data)->toBe($before->data);
    expect($after->currentPlayer)->toBe(PlayerMark::X);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(0);
});

test('reveals the official board on victory and scores only once', function () {
    $id = openPublicHidden();
    $component = Livewire::test('hidden-4x3', ['matchId' => $id]);
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('choose', $cell);
    }
    $component->assertDispatched('hidden-round-finished')->assertSee('Jogador X venceu!');
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(substr_count($component->html(), 'data-winning-cell="true"'))->toBe(3);
    expect($component->html())->toContain('data-mark="x"', 'data-mark="o"');
    $component->call('choose', 8)->assertSee('A rodada terminou.');
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    $this->get(route('4x3-hidden'))->assertOk()->assertSee('data-mark="x"', false);
});

test('counts an O victory without revealing positions before completion', function () {
    $id = openPublicHidden(1);
    $component = Livewire::test('hidden-4x3', ['matchId' => $id]);
    foreach ([0, 4, 1, 5] as $cell) {
        $component->call('choose', $cell);
        expect($component->html())->not->toContain('data-mark=');
    }
    $component->call('choose', 2)->assertSee('Jogador O venceu!');
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 1, 'draws' => 0]);
});

test('alternates the starter on restart while preserving score', function () {
    $id = openPublicHidden();
    $component = Livewire::test('hidden-4x3', ['matchId' => $id]);
    foreach ([0, 4, 1, 5, 2] as $cell) {
        $component->call('choose', $cell);
    }
    $component->call('restart')->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, 16, ''));
    expect(app(SessionMatchStore::class)->score($id)['x'])->toBe(1);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe(PlayerMark::O);
    expect($component->html())->not->toContain('data-mark=');
});

test('creates a zero-score match and rejects stale-tab actions', function () {
    $id = openPublicHidden();
    $staleChoose = Livewire::test('hidden-4x3', ['matchId' => $id]);
    $staleRestart = Livewire::test('hidden-4x3', ['matchId' => $id]);
    $staleNewMatch = Livewire::test('hidden-4x3', ['matchId' => $id]);
    Livewire::test('hidden-4x3', ['matchId' => $id])->call('newMatch')->assertRedirect(route('4x3-hidden'));
    $nextId = session('hidden4x3.active_match_id');
    expect($nextId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($nextId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    $staleChoose->call('choose', 1)->assertNotFound();
    $staleRestart->call('restart')->assertNotFound();
    $staleNewMatch->call('newMatch')->assertNotFound();
});

test('isolates all nine public modalities and replaces corrupt pointers', function () {
    $routes = ['3-basic', '4x3-basic', '3-move', '4x3-move', '4x4-move', '3-rotation', '4x3-rotation', '4x3-block', '4x3-hidden'];
    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id', 'movement4x3.active_match_id', 'movement4x4.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id', 'block4x3.active_match_id', 'hidden4x3.active_match_id'];
    $ids = array_map(fn (string $key): string => session($key), $keys);
    expect(array_unique($ids))->toHaveCount(9);
    $store = app(SessionMatchStore::class);
    $before = array_map(fn (string $id): array => $store->find($id)->data, $ids);
    Livewire::test('hidden-4x3', ['matchId' => $ids[8]])->call('choose', 0);
    foreach (range(0, 7) as $index) {
        expect($store->find($ids[$index])->data)->toBe($before[$index]);
    }
    session()->put('hidden4x3.active_match_id', $ids[0]);
    $this->get(route('4x3-hidden'))->assertOk();
    expect(session('hidden4x3.active_match_id'))->not->toBe($ids[0]);
    $corruptId = session('hidden4x3.active_match_id');
    session()->put('experimental_matches.'.$corruptId.'.state.data.order.x', [0, 0]);
    $this->get(route('4x3-hidden'))->assertOk();
    expect(session('hidden4x3.active_match_id'))->not->toBe($corruptId);
    $invalidResultId = session('hidden4x3.active_match_id');
    session()->put('experimental_matches.'.$invalidResultId.'.state.status', 'completed');
    session()->put('experimental_matches.'.$invalidResultId.'.state.current_player', null);
    session()->put('experimental_matches.'.$invalidResultId.'.state.winner', 'x');
    $this->get(route('4x3-hidden'))->assertOk();
    expect(session('hidden4x3.active_match_id'))->not->toBe($invalidResultId);
});

test('locks official properties and denies another session access', function () {
    $id = openPublicHidden();
    $otherSession = new Store('hidden-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($otherSession))->find($id))->toBeNull();
    expect(fn () => Livewire::test('hidden-4x3', ['matchId' => $id])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => [], 'order' => [], 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99]] as $property => $value) {
        expect(fn () => Livewire::test('hidden-4x3', ['matchId' => $id])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
});
