<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CardRequest;
use App\Models\Card;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CardController
{
    public function index(): Response
    {
        $cards = auth()->user()->cards()->latest()->get();

        return Inertia::render('Cards/Index', [
            'cards' => $cards,
        ]);
    }

    public function purchases(Card $card): Response
    {
        Gate::authorize('view', $card);

        $month = (int) request()->input('month', now()->month);
        $year = (int) request()->input('year', now()->year);

        $allPurchases = Purchase::where('user_id', auth()->id())
            ->where('card_id', $card->id)
            ->with('card')
            ->get();

        $purchases = $allPurchases->filter(fn ($p) => $p->isActiveInMonth($year, $month));

        $monthlyTotals = collect();
        for ($i = -3; $i <= 3; $i++) {
            $date = Carbon::create($year, $month, 1)->addMonths($i);
            $monthlyTotals->push([
                'month' => (int) $date->month,
                'year' => (int) $date->year,
                'total' => (float) $allPurchases->filter(fn ($p) => $p->isActiveInMonth((int) $date->year, (int) $date->month))->sum(fn ($p) => $p->installments_total ? ($p->amount / $p->installments_total) : $p->amount),
            ]);
        }

        $cards = auth()->user()->cards()->latest()->get();

        return Inertia::render('Cards/Purchases', [
            'card' => $card,
            'purchases' => $purchases->values(),
            'monthlyTotals' => $monthlyTotals->values(),
            'month' => $month,
            'year' => $year,
            'cards' => $cards,
        ]);
    }

    public function store(CardRequest $request): RedirectResponse
    {
        auth()->user()->cards()->create($request->validated());

        return to_route('cards.index')->with('flash', ['message' => 'Cartão criado com sucesso!', 'type' => 'success']);
    }

    public function update(CardRequest $request, Card $card): RedirectResponse
    {
        Gate::authorize('update', $card);

        $card->update($request->validated());

        return to_route('cards.index')->with('flash', ['message' => 'Cartão atualizado com sucesso!', 'type' => 'success']);
    }

    public function destroy(Card $card): RedirectResponse
    {
        Gate::authorize('delete', $card);

        $card->delete();

        return to_route('cards.index')->with('flash', ['message' => 'Cartão excluído com sucesso!', 'type' => 'success']);
    }

    public function bulkDestroy(): RedirectResponse
    {
        $ids = request()->input('ids', []);

        $cards = Card::whereIn('id', $ids)->get();

        $cards->each(fn (Card $card) => Gate::authorize('delete', $card));

        $cards->each->delete();

        return to_route('cards.index')->with('flash', ['message' => 'Cartões excluídos com sucesso!', 'type' => 'success']);
    }
}
