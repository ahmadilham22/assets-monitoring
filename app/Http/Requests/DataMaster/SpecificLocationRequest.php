<?php

namespace App\Http\Requests\DataMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpecificLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $specificLocationId = $this->input('id');

        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'kode_lokasi' => [
                'required',
                'string',
                'max:50',
                Rule::unique('specific_locations', 'kode_lokasi')
                    ->ignore($specificLocationId)
                    ->whereNull('deleted_at'),
            ],
            'lokasi_khusus' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.required' => 'Lokasi umum wajib dipilih.',
            'location_id.exists' => 'Lokasi umum tidak ditemukan.',
            'kode_lokasi.required' => 'Kode sub lokasi wajib diisi.',
            'kode_lokasi.unique' => 'Kode sub lokasi sudah digunakan.',
            'lokasi_khusus.required' => 'Nama sub lokasi wajib diisi.',
        ];
    }
}
