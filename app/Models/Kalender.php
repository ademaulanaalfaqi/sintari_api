<?php

namespace App\Models;

use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kalender extends Model
{
    use HasFactory;
    protected $table = 'sintari_kalender_561';
    protected $primaryKey = 'kalender_id';

    function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'kalender_pegawai');
    }
}
