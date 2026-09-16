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

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isAdmin();
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'duplicate_mode' => ['nullable', 'string', 'in:skip,update'],
            'password_mode' => ['nullable', 'string', 'in:default,generate,random'],
            'default_password' => ['nullable', 'string', 'min:6', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file spreadsheet (.xlsx / .xls) untuk diimpor.',
            'file.file' => 'Berkas yang diunggah harus berupa file.',
            'file.mimes' => 'Format berkas harus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran berkas maksimal adalah 5MB.',
            'duplicate_mode.in' => 'Opsi duplikasi harus antara "skip" atau "update".',
            'password_mode.in' => 'Opsi password harus antara "default" atau "generate".',
            'default_password.min' => 'Password default minimal 6 karakter.',
        ];
    }
}
