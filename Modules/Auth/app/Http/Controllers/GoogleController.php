<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    public function redirect(): \Symfony\Component\HttpFoundation\Response
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect('/')->with('error', 'Google rechazó la autenticación.');
        }
        if (!$request->has('code')) {
            return redirect('/')->with('error', 'No se recibió código de autenticación.');
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (InvalidStateException $e) {
            return redirect('/')->with('error', 'Error de seguridad. Intenta de nuevo.');
        } catch (\Exception $e) {
            Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect('/')->with('error', 'No se pudo autenticar con Google.');
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        $esNuevo = false;

        if ($user) {
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $esNuevo = true;
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => bcrypt(Str::password(32)),
                'rol' => RolUsuario::Estudiante,
                'estado' => 'pendiente',  // Nuevos usuarios requieren aprobación
            ]);
        }

        $authKey = 'login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d';
        session()->put($authKey, $user->id);
        Auth::setUser($user);

        // Si el usuario es nuevo y está pendiente, redirigir a página de espera
        if ($esNuevo) {
            return redirect()->route('pendiente');
        }

        // Si el usuario existente está pendiente, redirigir a espera
        if ($user->estaPendiente()) {
            return redirect()->route('pendiente');
        }

        // Si el usuario fue rechazado
        if ($user->estaRechazado()) {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect('/')->with('error', 'Tu cuenta ha sido rechazada. Contacta al administrador.');
        }

        return redirect('/dashboard');
    }
}
