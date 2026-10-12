<?php

namespace App\Reports;

use Illuminate\Support\Collection;

// Abre cada produto pendente nas entregas programadas.
class PendingDeliveryRows
{
    // Tira o que já foi entregue a partir da primeira entrega planejada.
    public static function openPlans(iterable $plans, float $ordered, float $remaining): Collection
    {
        $delivered = max(0, $ordered - min(max(0, $remaining), $ordered));
        $open = collect();

        foreach ($plans as $plan) {
            $quantity = (float) $plan->quantity;
            if ($quantity <= 0) {
                continue;
            }
            if ($delivered >= $quantity) {
                $delivered -= $quantity;
                continue;
            }

            $left = (int) round($quantity - $delivered);
            $delivered = 0;
            if ($left <= 0) {
                continue;
            }

            $date = $plan->delivery_date;
            $open->push((object) [
                'plan' => $plan,
                'sequence' => (int) $plan->sequence,
                'quantity' => $left,
                'date' => $date instanceof \DateTimeInterface
                    ? $date->format('d/m/Y')
                    : date('d/m/Y', strtotime((string) $date)),
            ]);
        }

        return $open;
    }

    // Diz quais entregas a quantidade encerra e o que fica pela metade.
    public static function describe(Collection $open, int $quantity): string
    {
        if ($open->isEmpty() || $quantity <= 0) {
            return '';
        }

        $left = $quantity;
        $closed = [];
        $partial = null;

        foreach ($open as $slice) {
            if ($left <= 0) {
                break;
            }
            if ($left >= $slice->quantity) {
                $closed[] = $slice;
                $left -= $slice->quantity;
                continue;
            }

            $partial = (object) [
                'sequence' => $slice->sequence,
                'date' => $slice->date,
                'left' => $slice->quantity - $left,
            ];
            $left = 0;
        }

        $parts = [];
        if ($closed !== []) {
            $names = array_map(
                fn ($slice) => 'entrega ' . $slice->sequence . ' (' . $slice->date . ')',
                $closed
            );
            $parts[] = 'Encerra a ' . self::joinNames($names) . '.';
        }
        if ($partial && $partial->left > 0) {
            $parts[] = 'Ficam ' . number_format($partial->left, 0, '', '.')
                . ' na entrega ' . $partial->sequence . ' (' . $partial->date . ').';
        }

        return implode(' ', $parts);
    }

    public static function expand(Collection $items, ?string $from, ?string $to, bool $filterByDeliveryDate): Collection
    {
        $rows = $items->flatMap(function ($item) {
            $available = max(0, min((float) ($item->saldo ?? 0), (float) ($item->quant ?? 0)));
            if ($available <= 0) {
                return [];
            }

            $plans = method_exists($item, 'relationLoaded') ? $item->deliveryPlans : collect();
            if ($plans->isEmpty()) {
                $row = clone $item;
                $row->saldo = (int) $available;

                return [$row];
            }

            return self::openPlans($plans, (float) $item->quant, (float) $item->saldo)->map(function ($slice) use ($item) {
                $row = clone $item;
                $row->saldo = $slice->quantity;
                $row->quant = $slice->quantity;
                $row->delivery_date = implode('-', array_reverse(explode('/', $slice->date)));

                return $row;
            })->all();
        });

        if ($filterByDeliveryDate && ($from || $to)) {
            $rows = $rows->filter(function ($row) use ($from, $to) {
                $date = substr((string) ($row->delivery_date ?? ''), 0, 10);
                if ($date === '') {
                    return false;
                }
                if ($from && $date < $from) {
                    return false;
                }
                if ($to && $date > $to) {
                    return false;
                }

                return true;
            });
        }

        return $rows->sortBy(function ($row) {
            return [
                substr((string) ($row->delivery_date ?? ''), 0, 10),
                (int) ($row->id ?? 0),
            ];
        })->values();
    }

    private static function joinNames(array $names): string
    {
        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);

        return implode(', a ', $names) . ' e a ' . $last;
    }
}
