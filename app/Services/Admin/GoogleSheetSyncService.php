<?php

namespace App\Services\Admin;

use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GoogleSheetSyncService
{
    public function syncMasterlist()
    {
        try {
            $url = config('services.google.script_url');
            $response = Http::get($url);

            if ($response->successful()) {
                $data = $response->json(); 
                Log::info("SENCO: Fetched masterlist from Google Sheets. " . count($data) . " total rows received.");

                DB::transaction(function () use ($data) {
                    foreach ($data as $row) {
                        // 1. STOP/SKIP logic: If student_id is empty or null, don't process this row
                        if (empty($row['student_id'])) {
                            continue; 
                        }

                        // 2. Data Cleaning: Force string types to avoid scientific notation 
                        // and handle the "Data truncated" warning for 'college'
                        Student::updateOrCreate(
                            ['student_id' => trim((string)$row['student_id'])], 
                            [
                                'email'       => !empty($row['email']) ? trim($row['email']) : null,
                                'college'     => trim((string)($row['college'] ?? 'N/A')),
                                'first_name'  => trim((string)($row['first_name'] ?? '')),
                                'middle_name' => !empty($row['middle_name']) ? trim($row['middle_name']) : null,
                                'last_name'   => trim((string)($row['last_name'] ?? '')),
                                'suffix'      => !empty($row['suffix']) ? trim($row['suffix']) : null,
                                // Fix for the 7.33E+286 issue: Cast to string explicitly
                                'portal_code' => !empty($row['portal_code']) ? (string)$row['portal_code'] : null,
                            ]
                        );
                    }
                });

                // Update Cache using the cleaned data
                $validStudentIds = collect($data)
                    ->whereNotNull('student_id')
                    ->where('student_id', '!=', '')
                    ->pluck('student_id')
                    ->toArray();

                Cache::put('senco_masterlist_ids', $validStudentIds, now()->addHours(2));

                Log::info("SENCO: Masterlist synced to DB. " . count($validStudentIds) . " valid records processed.");
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("SENCO Sync to DB Failed: " . $e->getMessage());
            return false;
        }
    }
}