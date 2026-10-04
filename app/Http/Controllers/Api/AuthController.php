<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /** Ambang peringatan brute force (percobaan gagal per IP dalam 24 jam). */
    private const AMBANG_BRUTE_FORCE = 5;

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $ip = $request->ip();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->catatLoginGagal($credentials['email'], $ip, (bool) $user);

            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        $this->catatLoginBerhasil($request, $user);

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()]);
    }

    /**
     * Pengaturan akun — ubah nama/email, dan (opsional) ganti password.
     * Ganti password wajib menyertakan password lama.
     */
    public function updateAccount(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if (! empty($data['password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $user->password)) {
                return response()->json(['message' => 'Password lama tidak sesuai.'], 422);
            }
            $user->password = $data['password'];
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->save(); // perubahannya tercatat otomatis oleh trait LogsActivity (severity: critical)

        return response()->json(['data' => $user->fresh()]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        Activity::log('logout', 'Keluar dari panel admin', [
            'user' => $user,
            'status' => 200,
        ]);

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /**
     * Login gagal: catat email yang dicoba + hitung percobaan dari IP yang sama.
     * Lima kali gagal dalam 24 jam dinaikkan jadi `critical` (indikasi brute force).
     */
    private function catatLoginGagal(string $email, ?string $ip, bool $akunTerdaftar): void
    {
        $gagalSebelumnya = ActivityLog::where('event', 'login_gagal')
            ->when($ip, fn ($q) => $q->where('ip', $ip))
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $total = $gagalSebelumnya + 1;
        $bruteForce = $total >= self::AMBANG_BRUTE_FORCE;

        Activity::log('login_gagal', "Login gagal untuk {$email}", [
            'severity' => $bruteForce ? 'critical' : 'warning',
            'status' => 401,
            'actor_email' => $email,
            'data' => [
                'email_dicoba' => $email,
                'akun_terdaftar' => $akunTerdaftar,
                'gagal_dari_ip_ini_24jam' => $total,
                'peringatan' => $bruteForce
                    ? "Kemungkinan brute force: {$total} percobaan gagal dari IP ini dalam 24 jam."
                    : null,
            ],
        ]);
    }

    /**
     * Login berhasil: bandingkan IP & perangkat dengan riwayat login sebelumnya.
     * IP/perangkat baru dinaikkan jadi `warning` — sinyal awal akun dipakai orang lain.
     */
    private function catatLoginBerhasil(Request $request, User $user): void
    {
        $ip = $request->ip();
        $ua = (string) $request->userAgent();

        $ipBaru = ! $this->pernahLoginDengan($user, $ip, null);
        $perangkatBaru = ! $this->pernahLoginDengan($user, null, $ua);

        Activity::log('login', 'Login berhasil'.($ipBaru ? ' — dari IP baru' : ''), [
            'user' => $user,
            'severity' => $ipBaru ? 'warning' : 'info',
            'status' => 200,
            'data' => [
                'peran' => $user->role,
                'ip_baru' => $ipBaru,
                'perangkat_baru' => $perangkatBaru,
                'peringatan' => $ipBaru
                    ? 'Login dari IP yang belum pernah dipakai akun ini. Pastikan ini memang Anda.'
                    : null,
            ],
        ]);
    }

    private function pernahLoginDengan(User $user, ?string $ip, ?string $userAgent): bool
    {
        return ActivityLog::where('user_id', $user->id)
            ->where('event', 'login')
            ->when($ip, fn ($q) => $q->where('ip', $ip))
            ->when($userAgent, fn ($q) => $q->where('user_agent', $userAgent))
            ->exists();
    }
}
