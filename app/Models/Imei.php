<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imei extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_pdf',
        'imei',
        'uuid',
        'created_at',
        'updated_at',
    ];

    protected $table = 'imeis'; // Asegúrate de especificar el nombre de la tabla
}
