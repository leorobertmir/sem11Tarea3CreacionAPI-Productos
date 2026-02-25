<?php

namespace Src\Cliente\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUuid;

class ClienteEloquenModel extends Model
{
    use HasUuid;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'direccion',
        'telefono',
        'email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
