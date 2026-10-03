<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Publik — semua pengaturan sebagai map key => value.
     */
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json(['data' => $settings]);
    }

    /**
     * Admin — simpan (upsert) banyak pengaturan sekaligus.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($data['settings'] as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_string($value) ? $value : ($value === null ? null : (string) $value)]
            );
        }

        return response()->json(['data' => Setting::all()->pluck('value', 'key')]);
    }
}
