<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\IncomeGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

final readonly class IncomeGroupController
{
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', IncomeGroup::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'income_ids' => ['nullable', 'array'],
            'income_ids.*' => ['integer'],
        ]);

        $group = IncomeGroup::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
        ]);

        $this->attachIncomesToGroup($group, $validated['income_ids'] ?? []);

        Inertia::flash('toast', ['message' => 'Grupo criado!', 'type' => 'success']);

        return back();
    }

    public function update(Request $request, IncomeGroup $incomeGroup): RedirectResponse
    {
        Gate::authorize('update', $incomeGroup);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $incomeGroup->update($validated);

        Inertia::flash('toast', ['message' => 'Grupo renomeado!', 'type' => 'success']);

        return back();
    }

    public function destroy(IncomeGroup $incomeGroup): RedirectResponse
    {
        Gate::authorize('delete', $incomeGroup);

        $incomeGroup->delete();

        Inertia::flash('toast', ['message' => 'Grupo excluído!', 'type' => 'success']);

        return back();
    }

    public function attachIncomes(Request $request, IncomeGroup $incomeGroup): RedirectResponse
    {
        Gate::authorize('update', $incomeGroup);

        $validated = $request->validate([
            'income_ids' => ['required', 'array'],
            'income_ids.*' => ['integer'],
        ]);

        $this->attachIncomesToGroup($incomeGroup, $validated['income_ids']);

        Inertia::flash('toast', ['message' => 'Itens agrupados!', 'type' => 'success']);

        return back();
    }

    public function detachIncome(Income $income): RedirectResponse
    {
        Gate::authorize('detach', $income);

        $income->update(['group_id' => null]);

        Inertia::flash('toast', ['message' => 'Item removido do grupo!', 'type' => 'success']);

        return back();
    }

    private function attachIncomesToGroup(IncomeGroup $group, array $ids): void
    {
        Income::whereIn('id', $ids)
            ->where('user_id', auth()->id())
            ->update(['group_id' => $group->id]);
    }
}
