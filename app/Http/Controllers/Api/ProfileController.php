<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Publik — profil sekolah (sejarah, visi, misi).
     */
    public function show()
    {
        $profile = Profile::first();

        return response()->json(['data' => $profile ?? new Profile]);
    }

    /**
     * Admin — simpan profil sekolah (satu baris).
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'sejarah' => 'nullable|string',
            'visi' => 'nullable|string',
            'misi' => 'nullable|string',
        ]);

        $profile = Profile::first();

        if (! $profile) {
            $profile = Profile::create($data);
        } else {
            $profile->update($data);
        }

        return response()->json(['data' => $profile->fresh()]);
    }
}
