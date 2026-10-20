<?php

if (! function_exists('rupiah')) {
    function rupiah(int|float|null $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}

if (! function_exists('rupiah_short')) {
    function rupiah_short(int|float|null $amount): string
    {
        $amount = (float) $amount;
        $format = fn (float $value, string $suffix) => 'Rp'.rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',').' '.$suffix;

        return match (true) {
            $amount >= 1_000_000_000 => $format($amount / 1_000_000_000, 'M'),
            $amount >= 1_000_000 => $format($amount / 1_000_000, 'jt'),
            $amount >= 1_000 => $format($amount / 1_000, 'rb'),
            default => rupiah($amount),
        };
    }
}
