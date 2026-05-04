<?php

namespace App\Http\Requests\DataMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locationId = $this->input('id');

        return [
            'kode_lokasi' => [
                'required',
                'string',
                'max:50',
                Rule::unique('locations', 'kode_lokasi')
                    ->ignore($locationId)
                    ->whereNull('deleted_at'),
            ],
            'lokasi_umum' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_lokasi.required' => 'Kode lokasi wajib diisi.',
            'kode_lokasi.unique' => 'Kode lokasi sudah digunakan.',
            'lokasi_umum.required' => 'Nama lokasi wajib diisi.',
        ];
    }
}
