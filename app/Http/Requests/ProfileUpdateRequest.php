<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+(\s[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+)*$/'
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Validación para cargo (solo letras y espacios, mayúsculas, 2-25 caracteres)
            'cargo' => [
                'nullable',
                'string',
                'min:2',
                'max:25',
                'regex:/^[A-Z\s]+$/',
            ],
            // Validación para teléfono (internacional, máximo 30 caracteres)
            'telefono' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^\+[0-9\s\-()]+$/',
            ],
        ];
    }
}
