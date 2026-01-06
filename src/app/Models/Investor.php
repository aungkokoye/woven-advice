<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    protected $fillable = ['investor_id', 'name', 'age', 'created_at', 'updated_at'];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class, 'owner_id', 'investor_id');
    }
}
