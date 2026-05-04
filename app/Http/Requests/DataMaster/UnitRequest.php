<?php

namespace App\Http\Requests\DataMaster;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->input('id');

        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('units', 'nama')
                    ->ignore($unitId)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama unit wajib diisi.',
            'nama.unique' => 'Nama unit sudah digunakan.',
        ];
    }
}
