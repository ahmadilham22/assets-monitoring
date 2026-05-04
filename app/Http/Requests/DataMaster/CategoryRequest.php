<?php

namespace App\Http\Requests\DataMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'kode_kategori' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'kode_kategori')
                    ->ignore($categoryId)
                    ->whereNull('deleted_at'),
            ],
            'nama_kategori' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_kategori.required' => 'Kode kategori wajib diisi.',
            'kode_kategori.unique'   => 'Kode kategori sudah ada.',
            'kode_kategori.max'      => 'Kode kategori maksimal 50 karakter.',
            'nama_kategori.required' => 'Nama kategori wajib diisi.',
            'nama_kategori.max'      => 'Nama kategori maksimal 255 karakter.',
        ];
    }
}
