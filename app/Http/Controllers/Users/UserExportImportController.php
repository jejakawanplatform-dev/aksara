<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserImportRequest;
use App\Models\User;
use App\Services\UserExportImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UserExportImportController extends Controller
{
    public function __construct(protected UserExportImportService $service) {}

    /**
     * Ekspor data pengguna ke berkas Excel (.xlsx).
     */
    public function export(Request $request): SymfonyResponse
    {
        $currentUser = Auth::user();
        abort_unless($currentUser instanceof User && $currentUser->isAdmin(), 403);

        $ids = $request->query('ids');
        $roleFilter = (string) $request->query('role', '');
        $search = (string) $request->query('search', '');

        $query = User::query();

        if (is_array($ids) && ! empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query
                ->when($roleFilter !== '', fn ($q) => $q->where('role', $roleFilter))
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                });
        }

        $content = $this->service->exportUsers($query);
        $filename = 'data-pengguna-aksara-'.now()->format('Ymd_His').'.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tempFile === false) {
            abort(500, 'Gagal membuat file sementara.');
        }
        file_put_contents($tempFile, $content);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Unduh template resmi (.xlsx) untuk pengisian data pengguna.
     */
    public function template(): SymfonyResponse
    {
        $currentUser = Auth::user();
        abort_unless($currentUser instanceof User && $currentUser->isAdmin(), 403);

        $content = $this->service->generateTemplate();
        $filename = 'template-impor-pengguna-aksara.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tempFile === false) {
            abort(500, 'Gagal membuat file sementara.');
        }
        file_put_contents($tempFile, $content);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Impor data pengguna dari berkas Excel yang diunggah.
     */
    public function import(UserImportRequest $request): RedirectResponse
    {
        $uploaded = $request->file('file');
        if (! ($uploaded instanceof UploadedFile)) {
            return back()->with('error', 'Berkas spreadsheet tidak valid.');
        }

        $dup = $request->input('duplicate_mode');
        $pwd = $request->input('password_mode');
        $def = $request->input('default_password');

        /** @var array{duplicate_mode?: string, password_mode?: string, default_password?: string} $options */
        $options = [
            'duplicate_mode' => is_scalar($dup) ? (string) $dup : 'skip',
            'password_mode' => is_scalar($pwd) ? (string) $pwd : 'default',
            'default_password' => is_scalar($def) && (string) $def !== '' ? (string) $def : 'Aksara2026!',
        ];

        $res = $this->service->importUsers($uploaded, $options);

        $messages = [];
        if ($res['imported'] > 0) {
            $messages[] = "{$res['imported']} pengguna baru berhasil ditambahkan.";
        }
        if ($res['updated'] > 0) {
            $messages[] = "{$res['updated']} data pengguna berhasil diperbarui.";
        }
        if ($res['skipped'] > 0) {
            $messages[] = "{$res['skipped']} baris duplikat dilewati.";
        }

        if (empty($messages) && empty($res['errors'])) {
            $messages[] = 'Tidak ada perubahan data.';
        }

        $redirect = redirect()->route('users.index');

        if (! empty($messages)) {
            $redirect->with('message', implode(' ', $messages));
        }

        if (! empty($res['errors'])) {
            $redirect->with('import_errors', $res['errors']);
        }

        if (! empty($res['credentials'])) {
            session(['imported_credentials' => $res['credentials']]);
            $redirect->with('has_credentials', true);
        }

        return $redirect;
    }

    /**
     * Unduh berkas rekap kredensial akun baru hasil generate acak pasca-impor.
     */
    public function downloadCredentials(): SymfonyResponse
    {
        $currentUser = Auth::user();
        abort_unless($currentUser instanceof User && $currentUser->isAdmin(), 403);

        $raw = session('imported_credentials');
        /** @var list<array{name: string, email: string, role: string, password: string}> $credentials */
        $credentials = is_array($raw) ? $raw : [];

        if (empty($credentials)) {
            return redirect()->route('users.index')->with('error', 'Tidak ada data kredensial baru yang dapat diunduh.');
        }

        $content = $this->service->exportCredentialsExcel($credentials);
        $filename = 'kredensial-pengguna-baru-'.now()->format('Ymd_His').'.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tempFile === false) {
            abort(500, 'Gagal membuat file sementara.');
        }
        file_put_contents($tempFile, $content);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
