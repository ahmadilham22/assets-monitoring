<?php

namespace App\Http\Requests\DataMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Route Division pakai pola updateOrCreate: ID dikirim di body
        // (bukan URL param), jadi ambil dari request input untuk ignore.
        $divisionId = $this->input('id');

        return [
            'kode_divisi' => [
                'required',
                'string',
                'max:50',
                Rule::unique('divisions', 'kode_divisi')
                    ->ignore($divisionId)
                    ->whereNull('deleted_at'),
            ],
            'nama_divisi' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_divisi.required' => 'Kode divisi wajib diisi.',
            'kode_divisi.unique' => 'Kode divisi sudah digunakan.',
            'nama_divisi.required' => 'Nama divisi wajib diisi.',
        ];
    }
}
