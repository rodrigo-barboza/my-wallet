<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MonthlySummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __invoke(Request $request, MonthlySummaryService $service): Response
    {
        $now = Carbon::now();
        $currentMonth = (int) $now->month;
        $currentYear = (int) $now->year;

        $startMonth = (int) $request->input('month', $currentMonth);
        $startYear = (int) $request->input('year', $currentYear);

        // Clamp: never go before current month
        if ($startYear < $currentYear || ($startYear === $currentYear && $startMonth < $currentMonth)) {
            $startMonth = $currentMonth;
            $startYear = $currentYear;
        }

        $user = auth()->user();
        $monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        $window = collect();
        $matrix = collect();
        $monthlySummary = collect();

        $m = $startMonth;
        $y = $startYear;

        // Collect all items across the window to build consistent rows
        $allItems = collect();

        for ($i = 0; $i < 6; $i++) {
            $items = $service->buildForMonth($user, $y, $m);

            foreach ($items as $item) {
                $existing = $allItems->firstWhere('id', $item['id']);
                if (! $existing) {
                    $allItems->push([
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'type' => $item['type'],
                        'totals' => array_fill(0, 6, 0),
                    ]);
                }
            }

            $m++;
            if ($m > 12) {
                $m = 1;
                $y++;
            }
        }

        // Build month-by-month data
        $m = $startMonth;
        $y = $startYear;

        for ($i = 0; $i < 6; $i++) {
            $income = $service->incomeForMonth($user, $y, $m);
            $items = $service->buildForMonth($user, $y, $m);
            $expenses = (float) $items->sum('total');
            $paid = $service->paidForMonth($user, $y, $m);

            $monthlySummary->push([
                'income' => $income,
                'expenses' => $expenses,
                'paid' => $paid,
                'balance' => $income - $expenses,
                'month' => $m,
                'year' => $y,
            ]);

            $window->push([
                'label' => $monthNames[$m - 1],
                'month' => $m,
                'year' => $y,
                'isCurrent' => $m === $currentMonth && $y === $currentYear,
                'isHighlighted' => $i === 0,
            ]);

            foreach ($items as $item) {
                $allItems = $allItems->map(function (array $row) use ($item, $i): array {
                    if ($row['id'] === $item['id']) {
                        $row['totals'][$i] = $item['total'];
                    }

                    return $row;
                });
            }

            $m++;
            if ($m > 12) {
                $m = 1;
                $y++;
            }
        }

        $matrix = $allItems->values();

        $categoryDistribution = $service->expensesByType($user, $startYear, $startMonth);
        $upcomingPayments = $service->upcomingPayments($user, $startYear, $startMonth, 5);

        return Inertia::render('Dashboard', [
            'window' => $window,
            'matrix' => $matrix,
            'monthlySummary' => $monthlySummary,
            'categoryDistribution' => $categoryDistribution,
            'upcomingPayments' => $upcomingPayments,
        ]);
    }
}
