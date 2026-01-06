<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investment extends Model
{
    protected $fillable = ['owner_id', 'investment_amount', 'investment_date'];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class, 'owner_id', 'investor_id');
    }
}
