<?php

namespace Src\Cliente\Domain\Contrats;

use Src\Cliente\Domain\Entities\Cliente;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ClienteRepositoryInterface
{
    public function save(Cliente $cliente): Cliente;
    public function update(string $id, array $data): ?Cliente;
    //public function delete(string $id): bool;
    public function exists(string $id): bool;
}