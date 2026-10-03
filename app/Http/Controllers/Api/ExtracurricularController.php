<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use Illuminate\Http\Request;

class ExtracurricularController extends Controller
{
    /**
     * Publik — daftar ekstrakurikuler.
     */
    public function index(Request $request)
    {
        $query = Extracurricular::orderBy('id');

        if ($request->filled('limit')) {
            return response()->json(['data' => $query->limit((int) $request->limit)->get()]);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Extracurricular::create($request->validate($this->rules()))], 201);
    }

    public function update(Request $request, Extracurricular $extracurricular)
    {
        $extracurricular->update($request->validate($this->rules()));

        return response()->json(['data' => $extracurricular->fresh()]);
    }

    public function destroy(Extracurricular $extracurricular)
    {
        $extracurricular->delete();

        return response()->json(['message' => 'Ekstrakurikuler dihapus.']);
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|string|max:500',
            'pembina' => 'nullable|string|max:255',
        ];
    }
}
