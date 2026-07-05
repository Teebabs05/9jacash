<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PaymentMethod extends Model
{
    protected static string $table = 'payment_methods';

    public static function active(): array
    {
        return static::where('is_active', 1, 'id DESC');
    }
}
