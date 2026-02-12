<?php
// app/Models/PeriodoAvaliacao.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoAvaliacao extends Model
{
    protected $table = 'periodos_avaliacao';

    protected $fillable = [
        'periodo',
        'dt_primeiro_dia',
        'dt_ultimo_dia',
        'ano',
    ];

    protected $primaryKey = 'id_periodo_avaliacao'; // Use o nome correto da sua chave primária
}