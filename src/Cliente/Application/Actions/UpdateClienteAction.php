<?php

namespace Src\Cliente\Application\Actions;

use Src\Cliente\Domain\Entities\Cliente;
use Src\Cliente\Domain\Contracts\ClienteRepositoryInterface;

class UpdateClienteAction{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository
    ){}

    public function execute(string $id, array $data): ?Cliente
    {
        return $this->clienteRepository->update($id, $data);
    }
}