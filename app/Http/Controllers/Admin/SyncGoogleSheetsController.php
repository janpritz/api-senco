<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GoogleSheetSyncService;

class SyncGoogleSheetsController extends Controller
{
    public function syncGoogleSheets()
    {
        $syncService = new GoogleSheetSyncService();
        $result = $syncService->syncMasterlist();

        if ($result) {
            return response()->json(['message' => 'Google Sheets masterlist synced successfully.'], 200);
        } else {
            return response()->json(['message' => 'Failed to sync Google Sheets masterlist.'], 500);
        }
    }
}
