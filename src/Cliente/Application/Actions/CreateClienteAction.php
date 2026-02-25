<?php

use Src\Cliente\Application\Actions;

use Src\Cliente\Domain\Entities\Cliente;
use Src\Cliente\Domain\Contracts\ClienteRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Support\Str;

class CreateClienteAction{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository
    ){}

    public function execute(array $data): Cliente
    {
        $cliente = new Cliente(
            id: Str::uuid()->toString(),
            tipoDocumento: $data['tipo_documento'],
            numeroDocumento: $data['numero_documento'],
            razonSocial: $data['razon_social'],
            direccion: $data['direccion'],
            telefono: $data['telefono'],
            email: $data['email'],
            createAt: new DateTimeImmutable(),
            updateAt: new DateTimeImmutable()
        );

        return $this->clienteRepository->save($cliente);
    }
}

