<?php
declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvDatasetHandlerService
{
    private const BATCH_SIZE = 1000;

    /**
     * @throws \Throwable
     */
    public function process(string $filePath): bool
    {
        $fullPath = storage_path('app/private/' . $filePath);

        try {
            $startTime = microtime(true);
            $totalRows = 0;
            $processedRows = 0;

            Log::info("Starting CSV processing: " . $fullPath);
            $handle = fopen($fullPath, 'r');

            if ($handle === false) {
                Log::error("Investor Dataset Processing: Failed to open file at " . $fullPath);
                return false;
            }

            // Skip header row
            fgetcsv($handle, 10000, ',');

            $batch = [];
            $rowNumber = 1;

            while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                $rowNumber++;

                // Validate row data
                if (count($data) < 5) {
                    Log::warning("Investor Dataset Processing: skipping row {$rowNumber} for $fullPath (insufficient columns)");
                    continue;
                }

                $batch[] = $this->parseRow($data, $rowNumber);
                $totalRows++;

                // Process batch when it reaches the batch size
                if (count($batch) >= self::BATCH_SIZE) {
                    $this->processBatch($batch);
                    $processedRows += count($batch);
                    Log::info("Investor Dataset Processing: processed {$processedRows}/{$totalRows} rows");
                    $batch = [];
                }
            }

            // Process remaining rows
            if (!empty($batch)) {
                $this->processBatch($batch);
                $processedRows += count($batch);
            }

            fclose($handle);

            $duration = round(microtime(true) - $startTime, 2);
            Log::info("Investor Dataset Processing: completed {$processedRows} rows processed in {$duration}s");

            return true;
        } catch (Exception $e) {
            Log::error("Investor Dataset Processing: " . $e->getMessage());

            return false;
        }
    }

    private function parseRow(array $data, int $rowNumber): array
    {
        return [
            'investor_id'       => (int) trim($data[0]),
            'name'              => trim($data[1]),
            'age'               => (int) trim($data[2]),
            'investment_amount' => number_format((float) trim($data[3]), 2, '.', ''),
            'investment_date'   => Carbon::createFromFormat('d-m-Y', trim($data[4]))->format('Y-m-d'),
            'row_number'        => $rowNumber,
        ];
    }

    /**
     * @throws \Throwable
     */
    private function processBatch(array $batch): void
    {
        $now = now();
        DB::transaction(function () use ($batch, $now) {

            $investorsData = [];
            $investmentsData = [];

            foreach ($batch as $row) {
                $investorId = $row['investor_id'];

                // Collect unique investors (use latest data if duplicate in batch)
                $investorsData[$investorId] = [
                    'investor_id'   => $investorId,
                    'name'          => $row['name'],
                    'age'           => $row['age'],
                    'updated_at'    => $now,
                    'created_at'    => $now,
                ];

                // We'll add investments after we have investor database IDs
                $investmentsData[] = [
                    'owner_id'          => $investorId,
                    'investment_amount' => $row['investment_amount'],
                    'investment_date'   => $row['investment_date'],
                    'updated_at'        => $now,
                    'created_at'        => $now,
                ];
            }

            $this->flush($investorsData, $investmentsData);
        });
    }

    private function flush(array $investors, array $investments): void
    {
        // Upsert investors
        DB::table('investors')->upsert(
            array_values($investors),
            ['investor_id'],
            ['name', 'age', 'updated_at']
        );

        // Insert investments
        DB::table('investments')->insert($investments);
    }
}
