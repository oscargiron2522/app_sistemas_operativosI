<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simulacion extends Model
{
    protected $table = 'simulaciones';

    protected $guarded = [];

    protected $casts = ['procesos' => 'array', 'resultado' => 'array'];
}
