<?php

namespace App\Http\Requests\DataMaster;

use Illuminate\Foundation\Http\FormRequest;

class ProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mitra' => ['required', 'string', 'max:255'],
            'jenis_pengadaan' => ['required', 'string', 'max:100'],
            'tahun_pengadaan' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'mitra.required' => 'Mitra wajib diisi.',
            'jenis_pengadaan.required' => 'Jenis pengadaan wajib diisi.',
            'tahun_pengadaan.required' => 'Tahun pengadaan wajib diisi.',
            'tahun_pengadaan.date' => 'Tahun pengadaan tidak valid.',
        ];
    }
}
