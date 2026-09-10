<?php

namespace App\Support;

class CashCombination
{
    /**
     * @param  array<int, int>  $inventory  Denomination in cents => available notes
     * @return array<int, int>|null Denomination in cents => notes to dispense
     */
    public function find(int $amount, array $inventory): ?array
    {
        if ($amount <= 0) {
            return null;
        }

        krsort($inventory, SORT_NUMERIC);
        $solutions = [0 => []];

        foreach ($inventory as $denomination => $available) {
            if ($denomination <= 0 || $available <= 0) {
                continue;
            }

            $next = $solutions;
            foreach ($solutions as $sum => $breakdown) {
                $maximum = min($available, intdiv($amount - $sum, $denomination));
                for ($count = 1; $count <= $maximum; $count++) {
                    $candidateSum = $sum + ($denomination * $count);
                    $candidate = $breakdown + [$denomination => $count];

                    if (! isset($next[$candidateSum]) || array_sum($candidate) < array_sum($next[$candidateSum])) {
                        $next[$candidateSum] = $candidate;
                    }
                }
            }
            $solutions = $next;
        }

        return $solutions[$amount] ?? null;
    }
}
