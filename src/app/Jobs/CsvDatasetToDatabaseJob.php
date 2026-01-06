<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CsvProcessingComplete;
use App\Services\CsvDatasetHandlerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class  CsvDatasetToDatabaseJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $path,
        private readonly string $to
    )
    {}

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(CsvDatasetHandlerService $service): void
    {
        $service->process($this->path);

        // Send completion email
        Mail::to($this->to)->send(new CsvProcessingComplete($this->path));
    }
}
