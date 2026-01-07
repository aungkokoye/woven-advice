<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression as DbQueryExpression;

/**
 * @method static select(string $string, string $string1, string $string2, string $string3, Expression|DbQueryExpression $raw)
 */
class Investor extends Model
{
    protected $fillable = ['investor_id', 'name', 'age', 'created_at', 'updated_at'];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class, 'owner_id', 'investor_id');
    }

    public static function averageAge(): float
    {
        return round((float) self::avg('age'));
    }
}
