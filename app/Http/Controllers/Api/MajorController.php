<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Support\Slug;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    /**
     * Publik — daftar jurusan.
     */
    public function index()
    {
        return response()->json(['data' => Major::orderBy('id')->get()]);
    }

    /**
     * Publik — detail jurusan berdasarkan slug.
     */
    public function show(string $slug)
    {
        return response()->json(['data' => Major::where('slug', $slug)->firstOrFail()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->resolveSlug($request);

        return response()->json(['data' => Major::create($data)], 201);
    }

    public function update(Request $request, Major $major)
    {
        $data = $request->validate($this->rules());
        $data['slug'] = $this->resolveSlug($request, $major);

        $major->update($data);

        return response()->json(['data' => $major->fresh()]);
    }

    public function destroy(Major $major)
    {
        $major->delete();

        return response()->json(['message' => 'Jurusan dihapus.']);
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'deskripsi' => 'nullable|string',
            'gambar' => 'nullable|string|max:500',
            'akreditasi' => 'nullable|string|max:20',
        ];
    }

    protected function resolveSlug(Request $request, ?Major $major = null): string
    {
        $source = $request->filled('slug') ? $request->slug : $request->nama;

        return Slug::unique(Major::class, $source, 'slug', $major?->id);
    }
}
