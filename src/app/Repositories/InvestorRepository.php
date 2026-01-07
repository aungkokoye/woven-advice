<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Investor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InvestorRepository
{
    public function allWithTotalInvestmentAmount(): Builder
    {
        return Investor::select(
            'investors.id',
            'investors.investor_id',
            'investors.name',
            'investors.age',
            \DB::raw('SUM(investments.investment_amount) as total_investment_amount')
        )
            ->leftJoin('investments', 'investors.investor_id', '=', 'investments.owner_id')
            ->groupBy('investors.id', 'investors.investor_id', 'investors.name', 'investors.age');
    }

    public function upsert(array $investors): int
    {
        return DB::table('investors')->upsert(
            array_values($investors),
            ['investor_id'],
            ['name', 'age', 'updated_at']
        );
    }
}
