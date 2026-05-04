<?php

namespace App\Http\Requests\DataAsset;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // ID dari URL parameter (route /update/{id})
        $id = $this->route('id');

        return [
            'sub_category_id' => ['required', 'exists:sub_categories,id'],
            'specific_location_id' => ['required', 'exists:specific_locations,id'],
            'procurement_id' => ['required', 'exists:procurements,id'],
            'user_id' => ['required', 'exists:users,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'kode_bmn' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('fixed_assets', 'kode_bmn')->ignore($id)->whereNull('deleted_at'),
            ],
            'kode_sn' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('fixed_assets', 'kode_sn')->ignore($id)->whereNull('deleted_at'),
            ],
            'kondisi' => ['required', Rule::in(['Baik', 'Rusak', 'Perbaikan'])],
            'tahun_perolehan' => ['required'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'harga' => ['nullable'],
            'keterangan' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'sub_category_id.required' => 'Kategori harus diisi.',
            'sub_category_id.exists' => 'Kategori tidak ditemukan.',
            'specific_location_id.required' => 'Lokasi harus diisi.',
            'specific_location_id.exists' => 'Lokasi tidak ditemukan.',
            'procurement_id.required' => 'Mitra harus diisi.',
            'procurement_id.exists' => 'Mitra tidak ditemukan.',
            'user_id.required' => 'Penanggung jawab harus diisi.',
            'user_id.exists' => 'Penanggung jawab tidak ditemukan.',
            'unit_id.required' => 'Satuan harus diisi.',
            'unit_id.exists' => 'Satuan tidak ditemukan.',
            'kode_bmn.unique' => 'Kode BMN sudah digunakan.',
            'kode_sn.unique' => 'Kode SN sudah digunakan.',
            'kondisi.required' => 'Kondisi harus diisi.',
            'kondisi.in' => 'Kondisi tidak valid.',
            'tahun_perolehan.required' => 'Tahun Perolehan harus diisi.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
        ];
    }
}
