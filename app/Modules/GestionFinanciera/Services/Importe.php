<?php

namespace App\Modules\GestionFinanciera\Services;

use App\Modules\Shared\Clinic;

final class Importe
{
    public static function centavos(string $amount): int
    {
        if (! preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/D', $amount, $parts)) {
            Clinic::fail('Introduce un importe no negativo con hasta dos decimales.', 'precio_consulta');
        }

        return (int) $parts[1] * 100 + (int) str_pad($parts[2] ?? '', 2, '0');
    }

    public static function decimal(string $amount): string
    {
        $cents = self::centavos($amount);

        return self::fromCents($cents);
    }

    public static function fromCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function total(array $lines): string
    {
        $total = 0;
        foreach ($lines as $line) {
            $unit = self::centavos((string) $line['precio_unitario']);
            $quantity = (int) $line['cantidad'];
            if ($quantity < 1 || ($unit > 0 && $quantity > intdiv(999999999999 - $total, $unit))) {
                Clinic::fail('El total supera el máximo permitido o la cantidad no es válida.');
            }
            $total += $unit * $quantity;
        }

        return self::fromCents($total);
    }
}
