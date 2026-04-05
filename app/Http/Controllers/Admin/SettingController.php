<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Display all settings
     */
    public function index()
    {
        $settings = Setting::all();

        return response()->json($settings);
    }

    /**
     * Store or update a setting
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|unique:settings,key',
            'value' => 'required|numeric',
        ]);

        $setting = Setting::create([
            'key' => $request->key,
            'value' => $request->value,
        ]);

        return response()->json([
            'message' => 'Setting created successfully',
            'data' => $setting
        ]);
    }

    /**
     * Show a specific setting
     */
    public function show($key)
    {
        $value = Setting::get($key);

        if ($value === null) {
            return response()->json([
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $value
        ]);
    }

    /**
     * Update a setting
     */
    public function update(Request $request, $key)
    {
        $request->validate([
            'value' => 'required|numeric',
        ]);

        Setting::set($key, $request->value);

        return response()->json([
            'message' => 'Setting updated successfully'
        ]);
    }

    /**
     * Delete a setting
     */
    public function destroy($key)
    {
        $deleted = Setting::where('key', $key)->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Setting deleted successfully'
        ]);
    }
}
