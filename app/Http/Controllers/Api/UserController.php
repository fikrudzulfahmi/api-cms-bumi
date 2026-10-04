<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Manajemen akun (khusus admin): tambah/edit/hapus admin & penulis.
 */
class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => User::orderBy('role')->orderBy('name')->get(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:admin,penulis',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create($data);

        return response()->json(['data' => $user], 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,penulis',
            'password' => 'nullable|string|min:6',
        ]);

        if (empty($data['password'])) {
            unset($data['password']); // biarkan password lama
        }

        if ($user->role === 'admin' && $data['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['message' => 'Minimal harus ada satu akun admin.'], 422);
        }

        $user->update($data);

        return response()->json(['data' => $user->fresh()]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['message' => 'Minimal harus ada satu akun admin.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Akun dihapus.']);
    }
}
