<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function showForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'whatsapp_numero' => ['nullable', 'string', 'max:20', new \App\Rules\WhatsappPeruRule],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'rol' => 'estudiante',
            'estado' => 'pendiente',
            'whatsapp_numero' => isset($validated['whatsapp_numero']) && $validated['whatsapp_numero'] !== null
                ? \App\Support\Telefono::normalizarWhatsApp($validated['whatsapp_numero'])
                : null,
        ]);

        // Notificar a todos los admins que hay un nuevo alumno pendiente
        try {
            $notificationService = app(\Modules\Notificaciones\Services\NotificationService::class);
            $admins = User::whereIn('rol', ['super_admin', 'admin'])->get();
            foreach ($admins as $admin) {
                $notificationService->alumnoRegistrado(
                    admin: $admin,
                    nombreAlumno: $user->name,
                    emailAlumno: $user->email,
                    alumnoId: $user->id,
                );
            }
        } catch (\Exception $e) {
            report($e);
        }

        Auth::login($user);

        return redirect()->route('pendiente');
    }
}
