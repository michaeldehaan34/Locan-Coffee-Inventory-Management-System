<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Barista;
use App\Models\Manager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Role-based login controller.
 *
 * Preserves the original Flask login flow:
 *  - The login form shows a dropdown of every Barista and Manager username.
 *  - The password is the last 6 characters of the account's phone number
 *    (no_telp), exactly as the legacy Flask system computed it
 *    (modules/auth.py: get_barista_password -> no_telp[-6:]).
 *  - On success the role (barista / manager) is stored in the Laravel session.
 */
class RoleLoginController extends Controller
{
    /**
     * Show the application's login form with the combined username dropdown.
     */
    public function create(): View
    {
        $baristasData = Barista::orderBy('username')->get(['username', 'role']);
        
        $baristasGroup = $baristasData->filter(fn($b) => in_array($b->role, ['barista', 'headbar']))->pluck('username')->all();
        $kitchenGroup = $baristasData->filter(fn($b) => in_array($b->role, ['kitchen', 'headkitchen']))->pluck('username')->all();
        $adminGudangGroup = $baristasData->filter(fn($b) => $b->role === 'admin gudang')->pluck('username')->all();

        $managersGroup = Manager::orderBy('username')->pluck('username')->all();

        return view('auth.login', [
            'baristas' => $baristasGroup,
            'kitchens' => $kitchenGroup,
            'admins' => $adminGudangGroup,
            'managers' => $managersGroup,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        [$type, $username] = $this->resolveAccount($request->input('username'));

        if ($type === '') {
            return back()->withErrors([
                'username' => 'Username tidak ditemukan.',
            ])->onlyInput('username');
        }

        $account = $type === 'manager'
            ? Manager::where('username', $username)->first()
            : Barista::where('username', $username)->first();

        if (! $account) {
            return back()->withErrors([
                'username' => 'Username tidak ditemukan.',
            ])->onlyInput('username');
        }

        // Password = last 6 characters of the phone number (no_telp).
        //
        // IMPORTANT: this must match the previous (Flask) system exactly.
        // In modules/auth.py the password was computed as `no_telp[-6:]`
        // — the last 6 RAW characters of the stored phone number, WITHOUT
        // stripping non-digit characters. We replicate that behaviour (and
        // the original <=6 guard that made authentication fail) so that
        // existing accounts keep working exactly as they did before.
        // ---- Dual Login Logic ----
        // Barista: always use legacy no_telp[-6:] (no password column).
        // Manager: if password column has a hash, use Hash::check().
        //          if password column is NULL (legacy account), fallback to no_telp[-6:].
        $passwordInput = $request->input('password');

        if (($type === 'manager' || $type === 'barista') && $account->password) {
            // Manager atau Karyawan dengan password hash — verifikasi pakai Hash::check()
            if (! Hash::check($passwordInput, $account->password)) {
                return back()->withErrors([
                    'password' => 'Password salah.',
                ])->onlyInput('username');
            }
        } else {
            // Barista atau Manager legacy (password NULL) — fallback ke no_telp[-6:]
            $noTelp = (string) $account->no_telp;

            if (strlen($noTelp) < 6) {
                return back()->withErrors([
                    'password' => 'Password salah. Gunakan 6 digit terakhir no. telepon.',
                ])->onlyInput('username');
            }

            $expectedPassword = substr($noTelp, -6);

            if ($passwordInput !== $expectedPassword) {
                return back()->withErrors([
                    'password' => 'Password salah. Gunakan 6 digit terakhir no. telepon.',
                ])->onlyInput('username');
            }
        }

        $request->session()->regenerate();

        $role = $type === 'manager' ? 'manajemen' : ($account->role ?? 'barista');

        $request->session()->put([
            'user_id'  => $account->id,
            'username' => $account->username,
            'role'     => $role,
            'name'     => $type === 'barista' ? ($account->nama_lengkap ?? $account->username) : $account->username,
        ]);

        if ($role === 'manajemen') {
            return redirect()->route('manager.dashboard');
        }

        if ($role === 'barista') {
            return redirect()->route('barista.dashboard');
        }

        if ($role === 'headbar') {
            return redirect()->route('headbar.dashboard');
        }

        if ($role === 'kitchen') {
            return redirect()->route('kitchen.dashboard');
        }

        if ($role === 'headkitchen') {
            return redirect()->route('headkitchen.dashboard');
        }

        if ($role === 'admin gudang') {
            return redirect()->route('gudang.dashboard');
        }

        // Redirect all other roles to the temporary dashboard
        return redirect('/dashboard/coming-soon');
    }

    /**
     * Destroy the authenticated session (logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Resolve the account type and username from the submitted dropdown value.
     *
     * The dropdown submits "type:username"; if only a username is present we
     * look it up in both tables to keep backwards compatibility.
     *
     * @return array{0: string, 1: string}  [type, username]
     */
    protected function resolveAccount(string $value): array
    {
        if (str_contains($value, ':')) {
            return explode(':', $value, 2);
        }

        if (Manager::where('username', $value)->exists()) {
            return ['manager', $value];
        }

        if (Barista::where('username', $value)->exists()) {
            return ['barista', $value];
        }

        return ['', $value];
    }
}