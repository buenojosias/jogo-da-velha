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
use Tests\TestCase;

function rotationKey(string $modality): string
{
    return $modality === '3-rotation' ? 'rotation3x3.active_match_id' : 'rotation4x3.active_match_id';
}

function openRotation(TestCase $test, string $modality, int $seed = 0): string
{
    app()->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    $test->get(route($modality))->assertOk();

    return session(rotationKey($modality));
}

test('opens a public rotation match and draws either starter on the server', function (string $modality, int $size, int $seed, PlayerMark $starter) {
    app()->instance(Randomizer::class, new Randomizer(new Mt19937($seed)));
    $response = $this->get(route($modality, ['starter' => $starter === PlayerMark::X ? 'o' : 'x']));

    $response->assertOk()->assertViewIs('rotation-match')->assertSee('Vez do jogador '.$starter->name)
        ->assertSee('data-testid="new-match-button"', false)->assertDontSee('js/game.js');
    expect(substr_count($response->getContent(), 'data-testid="cell-'))->toBe($size);
    $id = session(rotationKey($modality));
    expect($id)->toBeString()->toHaveLength(40);
    expect(app(SessionMatchStore::class)->startingPlayer($id))->toBe($starter);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
})->with([
    '3x3 X' => ['3-rotation', 9, 0, PlayerMark::X], '3x3 O' => ['3-rotation', 9, 1, PlayerMark::O],
    '4x4 X' => ['4x3-rotation', 16, 0, PlayerMark::X], '4x4 O' => ['4x3-rotation', 16, 1, PlayerMark::O],
]);

test('resumes order, rotates oldest and preserves score across next round', function (string $modality, array $victoryMoves) {
    $id = openRotation($this, $modality);
    $component = Livewire::test('rotation-match', ['matchId' => $id, 'modalityKey' => $modality]);
    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $component->call('place', $cell);
    }
    $this->get(route($modality))->assertOk()->assertSee('Sua peça marcada SAI será removida automaticamente ao colocar outra.');
    expect(session(rotationKey($modality)))->toBe($id);
    expect(app(SessionMatchStore::class)->find($id)->data['order'])->toBe(['x' => [0, 2, 4], 'o' => [1, 3, 6]]);

    $component->call('place', 5)->assertSee('Vez do jogador O');
    expect(app(SessionMatchStore::class)->find($id)->data['order']['x'])->toBe([2, 4, 5]);
    expect(app(SessionMatchStore::class)->find($id)->data['board'][0])->toBe('');
    $this->get(route($modality))->assertOk()->assertSee('casa 3 será removida');

    $component->call('restart')->assertSee('Vez do jogador O')->assertSee('Coloque uma peça');
    expect(app(SessionMatchStore::class)->find($id)->data['order'])->toBe(['x' => [], 'o' => []]);
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    foreach ($victoryMoves as $cell) {
        $component->call('place', $cell);
    }
    $component->assertSee('Jogador O venceu!')->assertDispatched('rotation-round-finished');
    expect(app(SessionMatchStore::class)->find($id)->status)->toBe(MatchStatus::Completed);
    expect(app(SessionMatchStore::class)->score($id)['o'])->toBe(1);
    $component->call('place', 7)->assertSee('A rodada terminou. Inicie uma nova rodada para jogar novamente.');
    expect(app(SessionMatchStore::class)->score($id)['o'])->toBe(1);
})->with([
    '3x3' => ['3-rotation', [3, 0, 4, 1, 5]],
    '4x4' => ['4x3-rotation', [4, 0, 5, 1, 6]],
]);

test('distinguishes the oldest pieces by turn on :dataset', function (string $modality) {
    $id = openRotation($this, $modality);
    $component = Livewire::test('rotation-match', ['matchId' => $id, 'modalityKey' => $modality]);

    $this->get(route($modality))->assertOk()->assertDontSee('data-oldest-turn=', false)->assertDontSee('1ª');

    foreach ([0, 1, 2, 3, 4, 6] as $cell) {
        $component->call('place', $cell);
    }

    $response = $this->get(route($modality))->assertOk()
        ->assertSee('Sua peça marcada SAI será removida automaticamente ao colocar outra.')
        ->assertDontSee('1ª');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $oldestCurrent = $xpath->query('//button[@data-testid="cell-0"]')->item(0);
    $oldestOpponent = $xpath->query('//button[@data-testid="cell-1"]')->item(0);

    expect($oldestCurrent->getAttribute('data-oldest-turn'))->toBe('current');
    expect($oldestCurrent->getAttribute('class'))->toContain('border-dashed', 'ring-2', 'ring-ds-warning');
    expect($oldestCurrent->getAttribute('title'))->toBe('Esta peça será removida automaticamente quando você colocar a próxima.');
    expect($oldestCurrent->getAttribute('aria-label'))->toContain('sua peça mais antiga; será removida automaticamente');
    expect($oldestOpponent->getAttribute('data-oldest-turn'))->toBe('opponent');
    expect($oldestOpponent->getAttribute('class'))->toContain('ring-1', 'ring-ds-border');
    expect($oldestOpponent->getAttribute('title'))->toBe('Esta peça será removida quando o adversário colocar a próxima.');
    expect($oldestOpponent->getAttribute('aria-label'))->toContain('será removida quando ele colocar a próxima');
    $removalLabels = $xpath->query('//span[@data-testid="oldest-removal-label"]');
    expect($removalLabels->length)->toBe(2);
    expect($removalLabels->item(0)->textContent)->toBe('SAI');
    expect($removalLabels->item(1)->textContent)->toBe('SAI');

    $component->call('place', 5)->assertSee('Vez do jogador O');
    $response = $this->get(route($modality))->assertOk()->assertDontSee('1ª');
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//button[@data-testid="cell-0"]')->item(0)->getAttribute('data-oldest-cell'))->toBe('false');
    expect($xpath->query('//button[@data-testid="cell-2"]')->item(0)->getAttribute('data-oldest-turn'))->toBe('opponent');
    expect($xpath->query('//button[@data-testid="cell-1"]')->item(0)->getAttribute('data-oldest-turn'))->toBe('current');

})->with(['3x3' => ['3-rotation'], '4x4' => ['4x3-rotation']]);

test('new matches reset only their own scoreboard and reject stale tabs', function (string $modality) {
    $id = openRotation($this, $modality);
    $staleTabs = [];
    foreach (['place', 'restart', 'newMatch'] as $method) {
        $staleTabs[$method] = Livewire::test('rotation-match', ['matchId' => $id, 'modalityKey' => $modality]);
    }
    $newTab = Livewire::test('rotation-match', ['matchId' => $id, 'modalityKey' => $modality]);
    $other = $modality === '3-rotation' ? '4x3-rotation' : '3-rotation';
    $otherId = openRotation($this, $other);
    app()->instance(Randomizer::class, new Randomizer(new Mt19937(1)));

    $newTab->call('newMatch')->assertRedirect(route($modality));

    $newId = session(rotationKey($modality));
    expect($newId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->startingPlayer($newId))->toBe(PlayerMark::O);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect(session(rotationKey($other)))->toBe($otherId);
    $staleTabs['place']->call('place', 0)->assertNotFound();
    $staleTabs['restart']->call('restart')->assertNotFound();
    $staleTabs['newMatch']->call('newMatch')->assertNotFound();
    expect(app(SessionMatchStore::class)->find($id)->data['board'])->toBe(array_fill(0, $modality === '3-rotation' ? 9 : 16, ''));
})->with(['3x3' => ['3-rotation'], '4x4' => ['4x3-rotation']]);

test('rejects missing, wrong-modality and corrupt active records', function (string $modality) {
    $id = openRotation($this, $modality);
    $other = $modality === '3-rotation' ? '4x3-rotation' : '3-rotation';
    $otherId = openRotation($this, $other);
    session()->put(rotationKey($modality), $otherId);
    $this->get(route($modality))->assertOk();
    expect(session(rotationKey($modality)))->not->toBe($otherId);

    $corruptId = session(rotationKey($modality));
    session()->put('experimental_matches.'.$corruptId.'.state.data.order.x', [0, 0]);
    $this->get(route($modality))->assertOk();
    expect(session(rotationKey($modality)))->not->toBe($corruptId);
    expect(app(SessionMatchStore::class)->find($id)->modality->key)->toBe($modality);
})->with(['3x3' => ['3-rotation'], '4x4' => ['4x3-rotation']]);

test('rejects a stored order that does not match the board', function (string $modality, mixed $order) {
    $id = openRotation($this, $modality);
    session()->put('experimental_matches.'.$id.'.state.data.order', $order);

    expect(fn () => app(SessionMatchStore::class)->find($id))->toThrow(UnexpectedValueException::class);
    $this->get(route($modality))->assertOk();
    expect(session(rotationKey($modality)))->not->toBe($id);
})->with([
    ['3-rotation', ['x' => [0, 0], 'o' => []]],
    ['3-rotation', ['x' => [9], 'o' => []]],
    ['4x3-rotation', ['x' => [0], 'o' => []]],
    ['4x3-rotation', ['x' => [], 'o' => [], 'extra' => []]],
]);

test('cannot save a rotation state into another modality match', function () {
    $three = openRotation($this, '3-rotation');
    $four = openRotation($this, '4x3-rotation');
    $store = app(SessionMatchStore::class);

    expect(fn () => $store->save($three, $store->find($four)))->toThrow(InvalidArgumentException::class);
    expect(fn () => $store->restart($four, $store->find($three)))->toThrow(InvalidArgumentException::class);
    expect($store->score($three))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    expect($store->score($four))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('keeps all six public matches independent', function () {
    $routes = ['3-basic', '4x3-basic', '3-move', '4x3-move', '3-rotation', '4x3-rotation'];
    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }
    $keys = ['classic.active_match_id', 'classic4x3.active_match_id', 'movement3x3.active_match_id',
        'movement4x3.active_match_id', 'rotation3x3.active_match_id', 'rotation4x3.active_match_id'];
    $ids = array_map(fn (string $key): string => session($key), $keys);
    expect(array_unique($ids))->toHaveCount(6);
    $store = app(SessionMatchStore::class);
    $before = array_map(fn (string $id): array => $store->find($id)->data['board'], $ids);

    Livewire::test('rotation-match', ['matchId' => $ids[4], 'modalityKey' => '3-rotation'])->call('place', 0);

    expect($store->find($ids[4])->data['board'][0])->not->toBe('');
    foreach ([0, 1, 2, 3, 5] as $index) {
        expect($store->find($ids[$index])->data['board'])->toBe($before[$index]);
    }
    expect(array_map(fn (string $key): string => session($key), $keys))->toBe($ids);
});

test('locks official properties and isolates another session', function (string $modality) {
    $id = openRotation($this, $modality);
    $separate = new Store('rotation-test', new ArraySessionHandler(120), 'other');
    expect((new SessionMatchStore($separate))->find($id))->toBeNull();
    expect(fn () => Livewire::test('rotation-match', ['matchId' => $id, 'modalityKey' => $modality])->set('matchId', str_repeat('A', 40)))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    expect(fn () => Livewire::test('rotation-match', ['matchId' => $id, 'modalityKey' => $modality])->set('modalityKey', '3-basic'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
    foreach (['board' => [], 'winner' => 'x', 'currentPlayer' => 'o', 'score' => ['x' => 99], 'order' => []] as $property => $value) {
        expect(fn () => Livewire::test('rotation-match', ['matchId' => $id, 'modalityKey' => $modality])->set($property, $value))
            ->toThrow(PublicPropertyNotFoundException::class);
    }
})->with(['3x3' => ['3-rotation'], '4x4' => ['4x3-rotation']]);
