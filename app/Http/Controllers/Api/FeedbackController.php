<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Publik — umpan balik lulusan.
     */
    public function index(Request $request)
    {
        $query = Feedback::latest('id');

        if ($request->filled('limit')) {
            return response()->json(['data' => $query->limit((int) $request->limit)->get()]);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        return response()->json(['data' => Feedback::create($request->validate($this->rules()))], 201);
    }

    public function update(Request $request, Feedback $feedback)
    {
        $feedback->update($request->validate($this->rules()));

        return response()->json(['data' => $feedback->fresh()]);
    }

    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return response()->json(['message' => 'Umpan balik dihapus.']);
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'tahun_lulus' => 'nullable|string|max:10',
            'jurusan' => 'nullable|string|max:255',
            'pesan' => 'nullable|string',
            'foto' => 'nullable|string|max:500',
        ];
    }
}
