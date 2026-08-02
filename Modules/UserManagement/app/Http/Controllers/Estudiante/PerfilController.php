<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    /**
     * Muestra el perfil del estudiante.
     */
    public function show(): Response
    {
        $user = auth()->user();

        return Inertia::render('MiPerfil/Index', [
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp_numero' => $user->whatsapp_numero,
                'foto' => $user->foto ? asset('storage/' . $user->foto) : null,
                'rol' => $user->rol,
                'rol_label' => $user->rol?->label() ?? '—',
                'estado' => $user->estado,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Actualiza los datos del perfil del estudiante.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'whatsapp_numero' => ['nullable', 'string', 'max:20', new \App\Rules\WhatsappPeruRule],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_numero' => isset($validated['whatsapp_numero']) && $validated['whatsapp_numero'] !== null
                ? \App\Support\Telefono::normalizarWhatsApp($validated['whatsapp_numero'])
                : null,
        ];

        // ─── Cambiar contraseña ──────────────────────────
        if (!empty($validated['new_password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
            }
            $data['password'] = bcrypt($validated['new_password']);
        }

        $user->update($data);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Actualiza la foto de perfil del estudiante.
     */
    public function updateFoto(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        // Eliminar foto anterior si existe
        if ($user->foto && Storage::disk('public')->exists($user->foto)) {
            Storage::disk('public')->delete($user->foto);
        }

        $path = $this->storeReencodedImage($request->file('foto'), 'perfiles', 'public');
        $user->update(['foto' => $path]);

        return back()->with('success', 'Foto de perfil actualizada.');
    }

    /**
     * Elimina la foto de perfil del estudiante.
     */
    public function destroyFoto(): RedirectResponse
    {
        $user = auth()->user();

        if ($user->foto && Storage::disk('public')->exists($user->foto)) {
            Storage::disk('public')->delete($user->foto);
        }

        $user->update(['foto' => null]);

        return back()->with('success', 'Foto de perfil eliminada.');
    }

    private function storeReencodedImage(UploadedFile $file, string $directory, string $disk): string
    {
        $image = match ($file->getMimeType()) {
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/gif' => imagecreatefromgif($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => imagecreatefromjpeg($file->getRealPath()),
        };

        $filename = 'perfiles/' . uniqid() . '.jpg';

        ob_start();
        imagejpeg($image, null, 85);
        $contents = ob_get_clean();
        imagedestroy($image);

        Storage::disk($disk)->put($filename, $contents);

        return $filename;
    }
}
