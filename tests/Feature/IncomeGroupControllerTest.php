<?php

use App\Models\Income;
use App\Models\IncomeGroup;
use App\Models\User;

use function Pest\Laravel\actingAs;

function makeTestIncome(User $user, string $name = 'Salário'): Income
{
    return Income::create(['user_id' => $user->id, 'name' => $name]);
}

it('creates a group and attaches the selected incomes', function () {
    $user = User::factory()->create();
    $a = makeTestIncome($user, 'Aluguel');
    $b = makeTestIncome($user, 'Freela');

    actingAs($user)->post(route('incomes.groups.store'), [
        'name' => 'Renda fixa',
        'income_ids' => [$a->id, $b->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('income_groups', ['user_id' => $user->id, 'name' => 'Renda fixa']);
    $this->assertDatabaseHas('incomes', ['id' => $a->id, 'group_id' => IncomeGroup::first()->id]);
    $this->assertDatabaseHas('incomes', ['id' => $b->id, 'group_id' => IncomeGroup::first()->id]);
});

it('creates a group without incomes', function () {
    $user = User::factory()->create();

    actingAs($user)->post(route('incomes.groups.store'), [
        'name' => 'Projetos',
    ])->assertRedirect();

    $this->assertDatabaseHas('income_groups', ['user_id' => $user->id, 'name' => 'Projetos']);
});

it('renames a group', function () {
    $user = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $user->id, 'name' => 'Antigo']);

    actingAs($user)->patch(route('incomes.groups.update', $group), [
        'name' => 'Novo',
    ])->assertRedirect();

    $this->assertDatabaseHas('income_groups', ['id' => $group->id, 'name' => 'Novo']);
});

it('deleting a group leaves its members ungrouped', function () {
    $user = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $user->id, 'name' => 'Grupo']);
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário', 'group_id' => $group->id]);

    actingAs($user)->delete(route('incomes.groups.destroy', $group))->assertRedirect();

    $this->assertDatabaseMissing('income_groups', ['id' => $group->id]);
    $this->assertDatabaseHas('incomes', ['id' => $income->id, 'group_id' => null]);
});

it('attaches incomes to an existing group', function () {
    $user = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $user->id, 'name' => 'Grupo']);
    $income = makeTestIncome($user, 'Freela');

    actingAs($user)->post(route('incomes.groups.attach', $group), [
        'income_ids' => [$income->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('incomes', ['id' => $income->id, 'group_id' => $group->id]);
});

it('detaches an income from its group', function () {
    $user = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $user->id, 'name' => 'Grupo']);
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário', 'group_id' => $group->id]);

    actingAs($user)->delete(route('incomes.group-detach', $income))->assertRedirect();

    $this->assertDatabaseHas('incomes', ['id' => $income->id, 'group_id' => null]);
});

it('does not allow managing another user group', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $owner->id, 'name' => 'Dono']);
    $income = Income::create(['user_id' => $owner->id, 'name' => 'Salário', 'group_id' => $group->id]);

    actingAs($other)
        ->patch(route('incomes.groups.update', $group), ['name' => 'Hack'])
        ->assertForbidden();
    actingAs($other)
        ->delete(route('incomes.groups.destroy', $group))
        ->assertForbidden();
    actingAs($other)
        ->post(route('incomes.groups.attach', $group), ['income_ids' => [$income->id]])
        ->assertForbidden();
    actingAs($other)
        ->delete(route('incomes.group-detach', $income))
        ->assertForbidden();
});

it('does not attach another user income to own group', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $other->id, 'name' => 'Grupo']);
    $foreignIncome = Income::create(['user_id' => $owner->id, 'name' => 'Salário']);

    actingAs($other)->post(route('incomes.groups.attach', $group), [
        'income_ids' => [$foreignIncome->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('incomes', ['id' => $foreignIncome->id, 'group_id' => null]);
});

it('validates name is required', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('incomes.groups.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

it('stores an income with a group id', function () {
    $user = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $user->id, 'name' => 'Grupo']);

    actingAs($user)->post(route('incomes.store'), [
        'name' => 'Salário',
        'amount' => 100,
        'start_month' => 1,
        'start_year' => 2026,
        'repeat_count' => 1,
        'group_id' => $group->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('incomes', ['name' => 'Salário', 'group_id' => $group->id]);
});

it('ignores a group id that belongs to another user when storing', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $group = IncomeGroup::create(['user_id' => $owner->id, 'name' => 'Dono']);

    actingAs($other)->post(route('incomes.store'), [
        'name' => 'Salário',
        'amount' => 100,
        'start_month' => 1,
        'start_year' => 2026,
        'repeat_count' => 1,
        'group_id' => $group->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('incomes', ['name' => 'Salário', 'group_id' => null]);
});
