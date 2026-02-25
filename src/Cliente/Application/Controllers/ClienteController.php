<?php

use Src\Cliente\Application\Controllers;

use App\Http\Controllers\Controller;
use Src\Cliente\Application\Actions\CreateClienteAction;
use Src\Cliente\Application\Actions\UpdateClienteAction;
use Src\Cliente\Infrastructure\Models\ClienteEloquentModel;
use Src\Cliente\Infrastructure\Requests\StoreClienteRequest;
use Src\Cliente\Infrastructure\Requests\UpdateClienteRequest;
use Src\Cliente\Infrastructure\Resources\ClienteResource;

class ClienteController extends Controller
{
    public function __construct(
        private CreateClienteAction $createClienteAction,
        private UpdateClienteAction $updateClienteAction,
    ) {}

    public function store (StoreClienteRequest $request)
    {
        $cliente = $this->createClienteAction->execute($request->validated());
        $clienteEloquent = ClienteEloquentModel::find($cliente->getId());
        return new ClienteResource($clienteEloquent);
    }

    public function index()
    {
        $clientes = ClienteEloquentModel::all();
        return ClienteResource::collection($clientes);
    }

    public function show($id)
    {
        $clienteEloquent = ClienteEloquentModel::find($id);
        if (!$clienteEloquent) {
            return response()->json([
                'message' => 'Cliente no encontrado',
                'success' => false
            ], 404);
        }
        return new ClienteResource($clienteEloquent);
    }

    public function update(UpdateClienteRequest $request, string $id)
    {
        $cliente = $this->updateClienteAction->execute($id, $request->validated());

        if (!$cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado',
                'success' => false
            ], 404);
        }

        $clienteModel = ClienteEloquentModel::find($id);
        return new ClienteResource($clienteModel);
    }
}