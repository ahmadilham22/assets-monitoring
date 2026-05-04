<?php

namespace App\Http\Requests\DataMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subCategoryId = $this->route('id');

        return [
            'categories_id' => ['required', 'integer', 'exists:categories,id'],
            'kode_sub_kategori' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sub_categories', 'kode_sub_kategori')
                    ->ignore($subCategoryId)
                    ->whereNull('deleted_at'),
            ],
            'nama_sub_kategori' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'categories_id.required' => 'Kategori induk wajib dipilih.',
            'categories_id.exists' => 'Kategori induk tidak ditemukan.',
            'kode_sub_kategori.required' => 'Kode sub kategori wajib diisi.',
            'kode_sub_kategori.unique' => 'Kode sub kategori sudah digunakan.',
            'nama_sub_kategori.required' => 'Nama sub kategori wajib diisi.',
        ];
    }
}
