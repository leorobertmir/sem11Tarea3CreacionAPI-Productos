<?php

namespace Src\Cliente\Infrastructure\Mappers;

use Src\Cliente\Domain\Entities\Cliente;
use Src\Cliente\Infrastructure\Models\ClienteEloquenModel;
use DateTimeImmutable;

class ClienteMapper
{
    public static function toDomain(ClienteEloquenModel $model): Cliente
    {
        return new Cliente(
            id: $model->id,
            tipo_documento: $model->tipo_documento,
            numero_documento: $model->numero_documento,
            razon_social: $model->razon_social,
            direccion: $model->direccion,
            telefono: $model->telefono,
            email: $model->email,
            created_at: new DateTimeImmutable($model->created_at->toDateTimeString()),
            updated_at: new DateTimeImmutable($model->updated_at->toDateTimeString()),
        );
    }

    public static function toEloquent(Cliente $entity): array
    {
        return [
            'id' => $entity->getId(),
            'tipo_documento' => $entity->getTipoDocumento(),
            'numero_documento' => $entity->getNumeroDocumento(),
            'razon_social' => $entity->getRazonSocial(),
            'direccion' => $entity->getDireccion(),
            'telefono' => $entity->getTelefono(),
            'email' => $entity->getEmail(),
        ];
    }
}