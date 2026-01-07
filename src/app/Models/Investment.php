<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    protected $fillable = ['owner_id', 'investment_amount', 'investment_date', 'created_at', 'updated_at'];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class, 'owner_id', 'investor_id');
    }

    public static function averageAmount(): float
    {
        return round((float) self::avg('investment_amount'), 2);
    }

    public static function totalInvestments(): int
    {
        return self::count('id');
    }
}
