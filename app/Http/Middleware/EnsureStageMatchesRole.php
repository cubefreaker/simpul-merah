<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\FormSubmission;

/**
 * Memastikan user yang mengakses halaman aksi (disposisi/verifikasi/approve)
 * hanya bisa bertindak jika stage dokumen memang giliran mereka.
 * Mencegah "jumping stage".
 */
class EnsureStageMatchesRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Dev selalu bisa bypass
        if ($user->hasDevAccess()) {
            return $next($request);
        }

        $submissionId = $request->route('id') ?? $request->route('submission');

        if ($submissionId) {
            $submission = FormSubmission::find($submissionId);

            if ($submission && !$submission->canBeActedBy($user)) {
                abort(403, 'Anda tidak berwenang melakukan aksi pada tahap ini.');
            }
        }

        return $next($request);
    }
}
