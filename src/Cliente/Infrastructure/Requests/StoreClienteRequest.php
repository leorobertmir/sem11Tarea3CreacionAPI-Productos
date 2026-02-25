<?php

namespace Src\Cliente\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'razon_social' => $this->razon_social,
        ]);
    }


    public function rules(): array
    {
        return [
            'tipo_documento' => 'required|string|in:NI,RUC,CE,PASSAPORTE',
            'numero_documento' => 'required|string|unique:clientes,numero_documento',
            'razon_social' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:255',
            'email' => 'required|email|unique:clientes,email',
        ];
    }   

    public function attributes(): array
    {
        return [
            'tipo_documento' => 'Tipo de Documento',
            'numero_documento' => 'Número de Documento',
            'razon_social' => 'Razón Social',
            'direccion' => 'Dirección',
            'telefono' => 'Teléfono',
            'email' => 'Correo Electrónico',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El :attribute es obligatorio.',
            'tipo_documento.in' => 'El :attribute debe ser uno de los siguientes valores: NI, RUC, CE, PASSAPORTE.',
            'numero_documento.required' => 'El :attribute es obligatorio.',
            'numero_documento.unique' => 'El :attribute ya está registrado.',
            'razon_social.required' => 'La :attribute es obligatoria.',
            'email.required' => 'El :attribute es obligatorio.',
            'email.email' => 'El :attribute debe ser una dirección de correo electrónico válida.',
            'email.unique' => 'El :attribute ya está registrado.',
        ];
    }
}