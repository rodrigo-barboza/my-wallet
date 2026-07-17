<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CardRequest;
use App\Models\Card;
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
