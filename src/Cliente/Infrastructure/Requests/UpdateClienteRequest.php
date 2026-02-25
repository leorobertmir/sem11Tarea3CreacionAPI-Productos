<?php

namespace Src\Cliente\Infrastructure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'tipo_documento' => $this->tipoDocumento,
            'numero_documento' => $this->numeroDocumento,
            'razon_social' => $this->razonSocial,
        ]);
    }

    public function rules(): array
    {
        $clienteId = $this->route('id') ?? $this->route('cliente');

        return [
            'tipo_documento' => 'sometimes|string|in:DNI,RUC,CE,PASSPORTE',
            'numero_documento' => 'sometimes|string|unique:clientes,numero_documento,' . $clienteId . ',id',
            'razon_social' => 'sometimes|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:clientes,email,' . $clienteId . ',id'
        ];
    }

    public function atributes(): array
    {
        return [
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'razon_social' => 'razón social',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'email' => 'correo electrónico',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.in' => 'El :attribute debe ser uno de los siguientes valores: DNI, RUC, CE, PASSPORTE.',
            'numero_documento.unique' => 'El :attribute ya está en uso.',
            'razon_social.required' => 'La :attribute es obligatoria.',
            'direccion.required' => 'La :attribute es obligatoria.',
            'telefono.required' => 'El :attribute es obligatorio.',
            'email.email' => 'El :attribute debe ser una dirección de correo electrónico válida.',
            'email.unique' => 'El :attribute ya está en uso.',
        ];
    }
}