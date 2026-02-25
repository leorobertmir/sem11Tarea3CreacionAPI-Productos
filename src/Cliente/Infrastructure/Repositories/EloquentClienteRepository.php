<?php

namespace Src\Cliente\Infrastructure\Repositories;

use Src\Cliente\Domain\Entities\Cliente;
use Src\Cliente\Domain\Contrats\ClienteRepositoryInterface;
use Src\Cliente\Infrastructure\Models\ClienteEloquenModel;
use Src\Cliente\Infrastructure\Mappers\ClienteMapper;

class EloquentClienteRepository implements ClienteRepositoryInterface
{
    public function save(Cliente $cliente): Cliente
    {
        $eloquentModel = ClienteEloquenModel::create([
            'tipo_documento' => $cliente->getTipoDocumento(),
            'numero_documento' => $cliente->getNumeroDocumento(),
            'razon_social' => $cliente->getRazonSocial(),
            'direccion' => $cliente->getDireccion(),
            'telefono' => $cliente->getTelefono(),
            'email' => $cliente->getEmail(),
        ]);
        return ClienteMapper::toDomain($eloquentModel->fresh());
    }

    public function update(string $id, array $data): ?Cliente
    {
        $clienteEloquent = ClienteEloquenModel::find($id);
        if (!$clienteEloquent) {
            return null;
        }
        $clienteEloquent->update($data);
        return ClienteMapper::toDomain($clienteEloquent->fresh());
    }

    public function exists(string $id): bool
    {
        return ClienteEloquenModel::query()->where('id', $id)->exists();
    }

    //public function delete (string $id): bool
    //{
    //    $clienteEloquent = ClienteEloquenModel::find($id);
    //    if (!$clienteEloquent) {
    //        return false;
    //    }
    //    return $clienteEloquent->delete();
    //}
}