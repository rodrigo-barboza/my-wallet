<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IncomeRequest;
use App\Models\Income;
use App\Models\IncomeMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class IncomeController
{
    public function index(): Response
    {
        $year = (int) request()->input('year', now()->year);

        $incomes = Income::where('user_id', auth()->id())
            ->with('incomeMonths')
            ->latest()
            ->get()
            ->map(function ($income) {
                $months = $income->incomeMonths
                    ->groupBy(fn ($m) => $m->year)
                    ->map(fn ($months) => $months->keyBy('month')->map(fn ($m) => [
                        'id' => $m->id,
                        'amount' => (float) $m->amount,
                    ]));

                return [
                    'id' => $income->id,
                    'name' => $income->name,
                    'months' => $months,
                ];
            })
            ->values();

        return Inertia::render('Incomes/Index', [
            'incomes' => $incomes,
            'year' => $year,
        ]);
    }

    public function store(IncomeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $income = Income::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
        ]);

        $startMonth = (int) $validated['start_month'];
        $startYear = (int) $validated['start_year'];
        $repeatCount = (int) $validated['repeat_count'];
        $amount = (float) $validated['amount'];

        for ($i = 0; $i < $repeatCount; $i++) {
            $month = $startMonth + $i;
            $year = $startYear;
            if ($month > 12) {
                $month -= 12;
                $year++;
            }

            $income->incomeMonths()->create([
                'month' => $month,
                'year' => $year,
                'amount' => $amount,
            ]);
        }

        Inertia::flash('toast', ['message' => 'Entrada criada com sucesso!', 'type' => 'success']);

        return to_route('incomes.index', ['year' => $startYear]);
    }

    public function update(Request $request, Income $income): RedirectResponse
    {
        Gate::authorize('update', $income);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $income->update($validated);

        Inertia::flash('toast', ['message' => 'Entrada atualizada com sucesso!', 'type' => 'success']);

        return back();
    }

    public function destroy(Income $income): RedirectResponse
    {
        Gate::authorize('delete', $income);

        $income->delete();

        Inertia::flash('toast', ['message' => 'Entrada excluída com sucesso!', 'type' => 'success']);

        return back();
    }

    public function duplicate(Income $income): RedirectResponse
    {
        Gate::authorize('update', $income);

        $income->load('incomeMonths');

        $copy = Income::create([
            'user_id' => auth()->id(),
            'name' => $income->name.' (cópia)',
        ]);

        foreach ($income->incomeMonths as $month) {
            $copy->incomeMonths()->create([
                'month' => $month->month,
                'year' => $month->year,
                'amount' => $month->amount,
            ]);
        }

        Inertia::flash('toast', ['message' => 'Entrada duplicada com sucesso!', 'type' => 'success']);

        return back();
    }

    public function updateMonth(Request $request, IncomeMonth $incomeMonth): RedirectResponse
    {
        Gate::authorize('update', $incomeMonth->income);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $incomeMonth->update(['amount' => $validated['amount']]);

        Inertia::flash('toast', ['message' => 'Valor atualizado!', 'type' => 'success']);

        return back();
    }

    public function fillMonths(Request $request, Income $income): RedirectResponse
    {
        Gate::authorize('update', $income);

        $validated = $request->validate([
            'start_month' => ['required', 'integer', 'min:1', 'max:12'],
            'start_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'repeat_count' => ['required', 'integer', 'min:1', 'max:12'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $startMonth = (int) $validated['start_month'];
        $startYear = (int) $validated['start_year'];
        $repeatCount = (int) $validated['repeat_count'];
        $amount = (float) $validated['amount'];

        for ($i = 0; $i < $repeatCount; $i++) {
            $month = $startMonth + $i;
            $year = $startYear;
            if ($month > 12) {
                $month -= 12;
                $year++;
            }

            $income->incomeMonths()->updateOrCreate(
                ['month' => $month, 'year' => $year],
                ['amount' => $amount],
            );
        }

        Inertia::flash('toast', ['message' => 'Meses preenchidos com sucesso!', 'type' => 'success']);

        return back();
    }

    public function deleteMonth(IncomeMonth $incomeMonth): RedirectResponse
    {
        Gate::authorize('update', $incomeMonth->income);

        $incomeMonth->delete();

        Inertia::flash('toast', ['message' => 'Mês removido!', 'type' => 'success']);

        return back();
    }
}
