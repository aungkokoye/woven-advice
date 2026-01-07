<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\InvestorRepository;
use Illuminate\Support\Collection;

class InvestorService
{
    public function getAllInvestorsWithTotalInvestmentAmount(): Collection
    {
        return app(InvestorRepository::class)->allWithTotalInvestmentAmount()
            ->get()
            ->map(function ($investor) {
                $investor->total_investment_amount = round((float) $investor->total_investment_amount, 2);
                return $investor;
            });
    }
}
