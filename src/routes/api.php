<?php

use App\Http\Controllers\Api\CsvFileUploadController;
use App\Http\Controllers\Api\InvestorController;
use Illuminate\Support\Facades\Route;

Route::post('/csv-upload', [CsvFileUploadController::class, 'upload']);

Route::get('/investor/avg-age', [InvestorController::class, 'averageAge']);
Route::get('/investor/avg-investment', [InvestorController::class, 'averageInvestment']);
Route::get('/investor/total-investments', [InvestorController::class, 'totalInvestments']);
Route::get('/investors', [InvestorController::class, 'listInvestors']);
