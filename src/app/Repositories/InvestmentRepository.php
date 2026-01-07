<?php
declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class InvestmentRepository
{
    public function insert(array $investments): bool
    {
        return DB::table('investments')->insert($investments);
    }
}
