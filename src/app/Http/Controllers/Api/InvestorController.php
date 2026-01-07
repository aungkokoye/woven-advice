<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Investor;
use App\Services\InvestorService;
use Illuminate\Http\JsonResponse;

class InvestorController extends Controller
{
    public function averageAge(): JsonResponse
    {
        return response()->json(['average_age' => Investor::averageAge()]);
    }

    public function averageInvestment(): JsonResponse
    {
        return response()->json(['average_investment' => Investment::averageAmount()]);
    }

    public function totalInvestments(): JsonResponse
    {
        return response()->json(['total_investments' => Investment::totalInvestments()]);
    }

    public function listInvestors(InvestorService $service): JsonResponse
    {
        $investors = $service->getAllInvestorsWithTotalInvestmentAmount();

        return response()->json([
            'total'     => $investors->count(),
            'investors' => $investors,
        ]);
    }
}
