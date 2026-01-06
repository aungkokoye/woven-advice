<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CsvFileUploadRequest;
use App\Jobs\CsvDatasetToDatabaseJob;
use Exception;
use Illuminate\Http\JsonResponse;

class CsvFileUploadController extends Controller
{
    /**
     * Upload investment dataset CSV file.
     * @throws Exception
     * @throws \Throwable
     */
    public function upload(CsvFileUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $file = $validated['file'];
        $fileName = time() . '.csv';
        $path = $file->storeAs('csv-investor', $fileName, 'local');
        // Dispatch job to process CSV file
        CsvDatasetToDatabaseJob::dispatch($path, 'user@wovenadvice.com')
            ->onConnection(config('queue.queue_connection'))
            ->onQueue(config('queue.notification_queue'));

        return response()->json([
            'message' => 'CSV file uploaded successfully, will notify once data processing is complete.',
        ]);
    }
}
