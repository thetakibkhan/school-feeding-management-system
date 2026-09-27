<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\FoodItem;
use App\Models\School;
use App\Repositories\DailyDeliveryReportRepository;
use Illuminate\Database\Eloquent\Collection;

class DailyDeliveryReportService
{
    private const QUANTITY_COLUMNS = [
        'bun' => 'bun_quantity',
        'boiled_egg' => 'egg_quantity',
        'banana' => 'banana_quantity',
    ];

    public function __construct(
        private readonly DailyDeliveryReportRepository $reports,
        private readonly StudentCountResolver $studentCounts,
        private readonly DemandCalculator $demandCalculator,
    ) {}

    /**
     * @return array{
     *     date: string,
     *     status: 'working'|'off_day'|'not_set_up',
     *     foodItems: Collection<int, FoodItem>,
     *     rows: list<array{school: School, has_delivery: bool, items: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>}>,
     *     shortfallRows: list<array{school: School, has_delivery: bool, items: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>}>,
     *     totals: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>
     * }
     */
    public function forDate(string $date): array
    {
        $foodItems = $this->reports->foodItems();
        $totals = $this->emptyTotals($foodItems);

        if ($this->reports->isNonWorkingDate($date)) {
            return $this->result($date, 'off_day', $foodItems, [], [], $totals);
        }

        $schedule = $this->reports->scheduleForDate($date);
        if ($schedule === null) {
            return $this->result($date, 'not_set_up', $foodItems, [], [], $totals);
        }

        $schools = $this->reports->schools();
        $studentCounts = $this->studentCounts->forSchoolsOnDate($schools->modelKeys(), $date);
        $deliveries = $this->reports->deliveriesForDate($date);
        $scheduledItemIds = $schedule->items->modelKeys();
        $rows = [];
        $shortfallRows = [];

        foreach ($schools as $school) {
            $studentCount = $studentCounts[$school->id] ?? null;
            if ($studentCount === null) {
                continue;
            }

            $delivery = $deliveries->get($school->id);
            $itemResults = [];
            $hasShortfall = false;

            foreach ($foodItems as $foodItem) {
                $itemResults[$foodItem->key] = $this->itemResult(
                    $foodItem,
                    $studentCount,
                    in_array($foodItem->id, $scheduledItemIds, true),
                    $delivery,
                    $totals,
                    $hasShortfall,
                );
            }

            $row = [
                'school' => $school,
                'has_delivery' => $delivery !== null,
                'items' => $itemResults,
            ];
            $rows[] = $row;
            if ($hasShortfall) {
                $shortfallRows[] = $row;
            }
        }

        return $this->result($date, 'working', $foodItems, $rows, $shortfallRows, $totals);
    }

    /** @return 'working'|'off_day'|'not_set_up' */
    public function statusForDate(string $date): string
    {
        if ($this->reports->isNonWorkingDate($date)) {
            return 'off_day';
        }

        return $this->reports->scheduleForDate($date) === null ? 'not_set_up' : 'working';
    }

    /** @param Collection<int, FoodItem> $foodItems
     * @param  list<array{school: School, has_delivery: bool, items: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>}>  $rows
     * @param  list<array{school: School, has_delivery: bool, items: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>}>  $shortfallRows
     * @param  array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>  $totals
     * @return array{date: string, status: 'working'|'off_day'|'not_set_up', foodItems: Collection<int, FoodItem>, rows: list<array{school: School, has_delivery: bool, items: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>}>, shortfallRows: list<array{school: School, has_delivery: bool, items: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>}>, totals: array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>}
     */
    private function result(string $date, string $status, Collection $foodItems, array $rows, array $shortfallRows, array $totals): array
    {
        return compact('date', 'status', 'foodItems', 'rows', 'shortfallRows', 'totals');
    }

    /** @param Collection<int, FoodItem> $foodItems
     * @return array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>
     */
    private function emptyTotals(Collection $foodItems): array
    {
        $totals = [];
        foreach ($foodItems as $foodItem) {
            $totals[$foodItem->key] = $this->emptyItemTotals();
        }

        return $totals;
    }

    /** @return array{demand: int, delivered: int, shortfall: int, excess: int} */
    private function emptyItemTotals(): array
    {
        return ['demand' => 0, 'delivered' => 0, 'shortfall' => 0, 'excess' => 0];
    }

    /**
     * @param  array<string, array{demand: int, delivered: int, shortfall: int, excess: int}>  $totals
     * @param  bool  $hasShortfall  Set to true when this food has a positive shortfall.
     * @return array{demand: int, delivered: int, shortfall: int, excess: int}
     */
    private function itemResult(
        FoodItem $foodItem,
        int $studentCount,
        bool $scheduled,
        ?Delivery $delivery,
        array &$totals,
        bool &$hasShortfall,
    ): array {
        $demand = $this->demandCalculator->forResolvedInputs($studentCount, false, $scheduled, $foodItem)->quantity;
        $quantityColumn = self::QUANTITY_COLUMNS[$foodItem->key] ?? null;
        $delivered = $delivery !== null && $quantityColumn !== null ? (int) $delivery->{$quantityColumn} : 0;
        $shortfall = max($demand - $delivered, 0);
        $excess = max($delivered - $demand, 0);

        $totals[$foodItem->key]['demand'] += $demand;
        $totals[$foodItem->key]['delivered'] += $delivered;
        $totals[$foodItem->key]['shortfall'] += $shortfall;
        $totals[$foodItem->key]['excess'] += $excess;
        $hasShortfall = $hasShortfall || $shortfall > 0;

        return compact('demand', 'delivered', 'shortfall', 'excess');
    }
}
