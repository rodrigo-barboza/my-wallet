<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Invoice;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestSearchSeeder extends Seeder
{
    public function run(): void
    {
        $userId = 1;

        // Cards
        $nubank = Card::create([
            'user_id' => $userId,
            'name' => 'Nubank',
            'color' => '#8B5CF6',
            'closing_day' => 3,
            'due_day' => 10,
        ]);

        $itau = Card::create([
            'user_id' => $userId,
            'name' => 'Itaú',
            'color' => '#F97316',
            'closing_day' => 15,
            'due_day' => 22,
        ]);

        $visa = Card::create([
            'user_id' => $userId,
            'name' => 'Visa Infinity',
            'color' => '#3B82F6',
            'closing_day' => 20,
            'due_day' => 27,
        ]);

        // === Compras no cartão Nubank ===
        $this->createCardPurchase($userId, $nubank->id, 'Netflix', 39.90, '2026-08-01', true);
        $this->createCardPurchase($userId, $nubank->id, 'Spotify', 21.90, '2026-08-01', true);
        $this->createCardPurchase($userId, $nubank->id, 'iFood', 89.50, '2026-08-05', false);
        $this->createCardPurchase($userId, $nubank->id, 'Amazon Prime', 14.90, '2026-08-01', true);
        $this->createCardPurchase($userId, $nubank->id, 'Uber', 156.30, '2026-08-10', false);
        $this->createCardPurchase($userId, $nubank->id, 'Mercado Livre', 299.90, '2026-08-12', false, 3);

        // Compras antigas no Nubank (para testar "todos os meses")
        $this->createCardPurchase($userId, $nubank->id, 'Netflix', 39.90, '2026-07-01', true);
        $this->createCardPurchase($userId, $nubank->id, 'Spotify', 21.90, '2026-07-01', true);
        $this->createCardPurchase($userId, $nubank->id, 'iFood', 67.20, '2026-07-08', false);
        $this->createCardPurchase($userId, $nubank->id, 'Netflix', 39.90, '2026-06-01', true);
        $this->createCardPurchase($userId, $nubank->id, 'Amazon Prime', 14.90, '2026-06-01', true);

        // === Compras no cartão Itaú ===
        $this->createCardPurchase($userId, $itau->id, 'Gaspar Azul', 450.00, '2026-08-01', true);
        $this->createCardPurchase($userId, $itau->id, 'Magazine Luiza', 1200.00, '2026-08-05', false, 10);
        $this->createCardPurchase($userId, $itau->id, 'Casas Bahia', 800.00, '2026-08-10', false, 6);
        $this->createCardPurchase($userId, $itau->id, 'Gaspar Azul', 450.00, '2026-07-01', true);
        $this->createCardPurchase($userId, $itau->id, 'Magazine Luiza', 1200.00, '2026-07-05', false, 10);

        // === Compras no cartão Visa ===
        $this->createCardPurchase($userId, $visa->id, 'Apple Store', 5999.00, '2026-08-01', false, 12);
        $this->createCardPurchase($userId, $visa->id, 'Steam Games', 149.90, '2026-08-15', false);
        $this->createCardPurchase($userId, $visa->id, 'Apple Store', 5999.00, '2026-07-01', false, 12);

        // === Compras individuais (sem cartão) ===
        $this->createIndividualPurchase($userId, 'Aluguel', 2500.00, '2026-08-05', true);
        $this->createIndividualPurchase($userId, 'Condomínio', 650.00, '2026-08-10', true);
        $this->createIndividualPurchase($userId, 'Internet Vivo', 129.90, '2026-08-15', true);
        $this->createIndividualPurchase($userId, 'Energia Elétrica', 180.00, '2026-08-20', true);
        $this->createIndividualPurchase($userId, 'Água', 85.00, '2026-08-25', true);
        $this->createIndividualPurchase($userId, 'Academia', 99.90, '2026-08-01', true);
        $this->createIndividualPurchase($userId, 'Farmácia', 234.50, '2026-08-12', false);
        $this->createIndividualPurchase($userId, 'Supermercado', 876.30, '2026-08-08', false);
        $this->createIndividualPurchase($userId, 'Padaria', 45.00, '2026-08-03', false);

        // Compras individuais antigas
        $this->createIndividualPurchase($userId, 'Aluguel', 2500.00, '2026-07-05', true);
        $this->createIndividualPurchase($userId, 'Condomínio', 650.00, '2026-07-10', true);
        $this->createIndividualPurchase($userId, 'Internet Vivo', 129.90, '2026-07-15', true);
        $this->createIndividualPurchase($userId, 'Supermercado', 920.00, '2026-07-08', false);
        $this->createIndividualPurchase($userId, 'Aluguel', 2500.00, '2026-06-05', true);
        $this->createIndividualPurchase($userId, 'Academia', 99.90, '2026-06-01', true);

        // Faturas (invoices) para os cartões
        $this->createInvoice($nubank->id, 8, 2026, 150.00, 3);
        $this->createInvoice($nubank->id, 7, 2026, 100.00, 2);
        $this->createInvoice($itau->id, 8, 2026, 450.00, 1);
        $this->createInvoice($itau->id, 7, 2026, 450.00, 1);
        $this->createInvoice($visa->id, 8, 2026, 5999.00, 12);

        $this->command->info('Seed de busca criada com sucesso!');
        $this->command->info('Cards: Nubank, Itaú, Visa Infinity');
        $this->command->info('Compras de teste criadas para user_id = 1');
    }

    private function createCardPurchase(int $userId, int $cardId, string $name, float $amount, string $startDate, bool $isRecurring, ?int $installments = null): void
    {
        Purchase::create([
            'user_id' => $userId,
            'card_id' => $cardId,
            'name' => $name,
            'type' => 'credit_card',
            'amount' => $amount,
            'is_recurring' => $isRecurring,
            'installments_total' => $installments,
            'start_date' => $startDate,
            'payment_day' => null,
        ]);
    }

    private function createIndividualPurchase(int $userId, string $name, float $amount, string $startDate, bool $isRecurring, ?int $installments = null): void
    {
        $start = Carbon::parse($startDate);

        Purchase::create([
            'user_id' => $userId,
            'card_id' => null,
            'name' => $name,
            'type' => $name === 'Academia' ? 'others' : 'bill',
            'amount' => $amount,
            'is_recurring' => $isRecurring,
            'installments_total' => $installments,
            'start_date' => $startDate,
            'payment_day' => $start->day,
        ]);
    }

    private function createInvoice(int $cardId, int $month, int $year, float $total, int $dueDay): void
    {
        $closingDate = Carbon::create($year, $month, 3);
        $dueDate = Carbon::create($year, $month, $dueDay);

        Invoice::create([
            'user_id' => 1,
            'card_id' => $cardId,
            'month' => $month,
            'year' => $year,
            'closing_date' => $closingDate,
            'due_date' => $dueDate,
            'status' => 'aberta',
        ]);
    }
}
