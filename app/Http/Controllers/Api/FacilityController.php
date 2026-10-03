<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    /**
     * Publik — daftar fasilitas.
     */
    public function index()
    {
        return response()->json(['data' => Facility::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Facility::create($request->validate($this->rules()))], 201);
    }

    public function update(Request $request, Facility $facility)
    {
        $facility->update($request->validate($this->rules()));

        return response()->json(['data' => $facility->fresh()]);
    }

    public function destroy(Facility $facility)
    {
        $facility->delete();

        return response()->json(['message' => 'Fasilitas dihapus.']);
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|string|max:500',
        ];
    }
}
