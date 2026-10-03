<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Publik — daftar guru & karyawan.
     */
    public function index(Request $request)
    {
        $query = Teacher::orderBy('urutan')->orderBy('id');

        if ($request->filled('limit')) {
            return response()->json(['data' => $query->limit((int) $request->limit)->get()]);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        return response()->json(['data' => Teacher::create($data)], 201);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $teacher->update($request->validate($this->rules()));

        return response()->json(['data' => $teacher->fresh()]);
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return response()->json(['message' => 'Data dihapus.']);
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'foto' => 'nullable|string|max:500',
            'urutan' => 'nullable|integer',
        ];
    }
}
