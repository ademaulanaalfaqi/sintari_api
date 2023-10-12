<?php

namespace App\Models;

use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Realisasi extends Model
{
    use HasFactory;
    protected $table = 'sintari_realisasi_7';
    protected $primaryKey = 'realisasi_id';

    public function pegawai(){
        return $this->hasOne(Pegawai::class,'realisasi_user');
    }
}
