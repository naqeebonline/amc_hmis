<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function syncData(Request $request)
    {
        $table = $request->input('table');
        $records = $request->input('data'); // array of 20 records

        if (!$table || !$records || !is_array($records)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid request: table or data missing'
            ], 400);
        }

        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Table {$table} not found in database"
            ], 400);
        }

        $syncedIds = [];

        foreach ($records as $data) {
            if (!isset($data['id'])) {
                continue; // skip invalid
            }

            DB::table($table)->updateOrInsert(
                ['id' => $data['id']],
                $data
            );

            $syncedIds[] = $data['id'];
        }

        return response()->json([
            'status'     => 'success',
            'table'      => $table,
            'synced_ids' => $syncedIds,
            'count'      => count($syncedIds),
        ]);
    }

    public function load_sync_view()
    {
        return view("reports.sync_view");
    }


    public function syncLoclDataWithLive()
    {
        $logPath = storage_path('logs');
        // Clear logs
        foreach (glob($logPath . '/*.log') as $file) {
            file_put_contents($file, ''); // Empty file
        }

        $tables = [
            "in_patient_admissions",
            "patient_investigations",
            "patients",
            "consultants",
            "patient_investigation_result",
            "appointments",
            "finance_heads",
            "finance_transactions",
            "finance_vouchers"
        ];

        $summary = [
            'success' => [],
            'errors'  => [],
        ];

        foreach ($tables as $table) {
            try {
                \Illuminate\Support\Facades\DB::table($table)
                    ->where('is_sync', 0)
                    ->orderBy('id')
                    ->chunk(70, function ($records) use ($table, &$summary) {
                        if ($records->isEmpty()) {
                            return; // nothing to sync
                        }

                        $apiUrl = env('LIVE_URL') . 'api/sync';

                        $payload = [
                            'table' => $table,
                            'data'  => $records->map(fn($r) => (array) $r)->toArray(),
                    ];

                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept' => 'application/json',
                    ])->post($apiUrl, $payload);

                    if ($response->successful()) {
                        $syncedIds = $response->json('synced_ids') ?? [];

                        if (!empty($syncedIds)) {
                            \Illuminate\Support\Facades\DB::table($table)
                                ->whereIn('id', $syncedIds)
                                ->update(['is_sync' => 1]);
                        }

                        $summary['success'][] = [
                            'table'  => $table,
                            'count'  => count($syncedIds),
                        ];
                    } else {
                        $summary['errors'][] = [
                            'table'    => $table,
                            'response' => $response->body(),
                        ];
                    }
                });
            } catch (\Exception $e) {
                $summary['errors'][] = [
                    'table'   => $table,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        if (!empty($summary['errors'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Some tables failed to sync',
                'details' => $summary,
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'All tables synced successfully',
            'details' => $summary,
        ]);
    }
    public function syncLoclDataWithLiveBackup()
    {
        $logPath = storage_path('logs');
        // Delete all log files inside storage/logs
        foreach (glob($logPath.'/*.log') as $file) {
            file_put_contents($file, ''); // Empty file
        }
        $tables = [
            "in_patient_admissions",
            "patient_investigations",
            "patients",
            "consultants",
            "patient_investigation_result",
            "appointments",
            "finance_heads",
            "finance_transactions",
            "finance_vouchers"

        ];

        foreach ($tables as $table) {
            \Illuminate\Support\Facades\DB::table($table)
                ->where('is_sync', 0) // only unsynced
                ->orderBy('id')
                ->chunk(30, function ($records) use ($table) {
                    $apiUrl = env('LIVE_URL').'api/sync';

                    $payload = [
                        'table' => $table,
                        'data'  => $records->map(fn($r) => (array) $r)->toArray(),
                    ];

                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Accept' => 'application/json',
                    ])->post($apiUrl, $payload);

                    if ($response->successful()) {
                        // response se synced ids nikalo
                        $syncedIds = $response->json('synced_ids') ?? [];

                        if (!empty($syncedIds)) {
                            \Illuminate\Support\Facades\DB::table($table)->whereIn('id', $syncedIds)->update(['is_sync' => 1]);
                        }
                        echo ("{$table}: synced " . count($syncedIds) . " records.<br>");
                    } else {

                        echo ("{$table}: failed syncing chunk <br>");
                        echo ("Response: " . $response->body());

                    }
                });
        }
        return ["status"=>true,"message"=>"There is no data to sync. all records are synced"];
        //echo "<br>There is no data to sync. all records are synced";
    }
}
