<?php

use App\Games\MatchState;
use App\Games\MatchStatus;
use App\Games\PlayerMark;
use App\Http\SessionMatchStore;
use Livewire\Livewire;

function openPublicCrazy(): string
{
    test()->get(route('crazy'))->assertOk();

    return session('crazy.active_match_id');
}

test('creates and resumes a public match without loading legacy JavaScript', function () {
    $id = openPublicCrazy();
    $response = $this->get(route('crazy'));
    $response->assertOk()->assertViewIs('crazy-match')->assertSee('Conquista Maluca')->assertDontSee('js/game.js');
    expect(session('crazy.active_match_id'))->toBe($id);
    expect(app(SessionMatchStore::class)->find($id)->data['stacks'])->toBe(array_fill(0, 9, []));
    expect(app(SessionMatchStore::class)->score($id))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
});

test('places and covers pieces, rejects smaller covers and retains progress across requests', function () {
    $id = openPublicCrazy();
    $starter = app(SessionMatchStore::class)->find($id)->currentPlayer;
    $other = $starter === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
    $component = Livewire::test('crazy-match', ['matchId' => $id]);
    $component->call('place', 1, 0)->assertDispatched('crazy-selection-clear');
    $component->call('place', 2, 0);
    $component->call('place', 2, 0)->assertSee('maior');
    expect(app(SessionMatchStore::class)->find($id)->data['stacks'][0])->toBe([$starter->value.'-1-1', $other->value.'-2-1']);
    $this->get(route('crazy'))->assertSee('data-stack-count="2"', escape: false);
    expect(app(SessionMatchStore::class)->find($id)->currentPlayer)->toBe($starter);
});

test('renders three size selectors per player after the board and before the score', function () {
    $id = openPublicCrazy();
    $html = Livewire::test('crazy-match', ['matchId' => $id])->html();

    expect(substr_count($html, 'data-size='))->toBe(6);
    foreach (['x', 'o'] as $player) {
        foreach ([1, 2, 3] as $size) {
            expect($html)->toContain('data-testid="piece-'.$player.'-'.$size.'"');
        }
    }
    expect(substr_count($html, 'data-remaining="3"'))->toBe(6);
    expect($html)->not->toMatch('/<span[^>]*>(Pequena|Média|Grande)<\/span>/');
    expect($html)->toContain('aria-label="Pequena do jogador X, 3 restantes');
    expect($html)->toContain('aria-label="Média do jogador O, 3 restantes');
    expect(strpos($html, 'data-testid="crazy-board"'))->toBeLessThan(strpos($html, 'data-testid="piece-selector"'));
    expect(strpos($html, 'data-testid="piece-selector"'))->toBeLessThan(strpos($html, 'data-testid="participant-x"'));
});

test('resolves the first unused copy from current inventory and rejects exhausted or forged sizes', function () {
    $id = openPublicCrazy();
    $store = app(SessionMatchStore::class);
    $state = $store->find($id);
    $player = $state->currentPlayer;
    $other = $player === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
    $stacks = array_fill(0, 9, []);
    $stacks[0] = [$player->value.'-1-1'];
    $stacks[1] = [$player->value.'-1-2'];
    $stacks[3] = [$other->value.'-1-1'];
    $stacks[4] = [$other->value.'-1-2'];
    $store->save($id, new MatchState($state->modality, $state->participants, MatchStatus::InProgress, $player, ['stacks' => $stacks]));
    $component = Livewire::test('crazy-match', ['matchId' => $id]);

    $component->assertSee('data-remaining="1"', escape: false)
        ->call('place', $other->value.'-1-1', 2)->assertSee('Escolha uma peça disponível');
    $component->call('place', 1, 2)->assertDispatched('crazy-selection-clear');
    expect($store->find($id)->data['stacks'][2])->toBe([$player->value.'-1-3']);
    expect($component->html())->toMatch('/data-testid="piece-'.$player->value.'-1"[^>]*data-stock-state="used"[^>]*disabled/s');

    $component->call('place', 2, 2)->assertDispatched('crazy-selection-clear');
    expect($store->find($id)->data['stacks'][2])->toBe([$player->value.'-1-3', $other->value.'-2-1']);
    $current = $store->find($id);
    $component->call('place', 1, 3)->assertSee('Escolha uma peça disponível');
    expect($store->find($id)->data['stacks'])->toBe($current->data['stacks']);

    $component->call('restart');
    expect($store->find($id)->data['stacks'])->toBe(array_fill(0, 9, []));
    expect(substr_count($component->html(), 'data-remaining="3"'))->toBe(6);
});

test('awards definitive victory only once and preserves score after restart', function () {
    $id = openPublicCrazy();
    $store = app(SessionMatchStore::class);
    $initial = $store->find($id);
    $stacks = array_fill(0, 9, []);
    $mark = $initial->currentPlayer;
    $other = $mark === PlayerMark::X ? PlayerMark::O : PlayerMark::X;
    $stacks[0] = [$mark->value.'-3-1'];
    $stacks[1] = [$mark->value.'-3-2'];
    $stacks[3] = [$other->value.'-1-1'];
    $stacks[4] = [$other->value.'-1-2'];
    $store->save($id, new MatchState($initial->modality, $initial->participants, MatchStatus::InProgress, $mark, ['stacks' => $stacks]));
    $component = Livewire::test('crazy-match', ['matchId' => $id]);
    $component->call('place', 3, 2)->assertSee('venceu!')->assertDispatched('crazy-round-finished');
    expect($store->score($id)[$mark->value])->toBe(1);
    $component->call('place', 3, 5)->assertSee('A rodada terminou');
    expect($store->score($id)[$mark->value])->toBe(1);
    $component->call('restart');
    expect($store->find($id)->data['stacks'])->toBe(array_fill(0, 9, []));
    expect($store->startingPlayer($id))->toBe($other);
    expect($store->score($id)[$mark->value])->toBe(1);
});

test('new match resets score and a stale tab cannot mutate the old match', function () {
    $id = openPublicCrazy();
    $component = Livewire::test('crazy-match', ['matchId' => $id]);
    $component->call('newMatch')->assertRedirect(route('crazy'));
    $newId = session('crazy.active_match_id');
    expect($newId)->not->toBe($id);
    expect(app(SessionMatchStore::class)->score($newId))->toBe(['x' => 0, 'o' => 0, 'draws' => 0]);
    Livewire::test('crazy-match', ['matchId' => $id])->assertNotFound();
    $this->get(route('crazy'))->assertOk();
    expect(session('crazy.active_match_id'))->toBe($newId);
});

test('rejects corrupted stacks and protects other modalities active identifiers', function () {
    $classic = $this->get(route('3-basic'));
    $classic->assertOk();
    $classicId = session('classic.active_match_id');
    $id = openPublicCrazy();
    $stored = session('experimental_matches.'.$id);
    $stored['state']['data']['stacks'][0] = ['x-2-1', 'o-1-1'];
    session()->put('experimental_matches.'.$id, $stored);
    $this->get(route('crazy'))->assertOk();
    expect(session('crazy.active_match_id'))->not->toBe($id);
    expect(session('classic.active_match_id'))->toBe($classicId);
});
