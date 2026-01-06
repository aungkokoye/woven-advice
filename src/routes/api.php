<?php

use App\Http\Controllers\Api\CsvFileUploadController;
use Illuminate\Support\Facades\Route;

Route::post('/csv-upload', [CsvFileUploadController::class, 'upload']);
