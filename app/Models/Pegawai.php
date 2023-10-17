<?php

namespace App\Models;

use App\Models\User;
use App\Models\Kalender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pegawai extends Model
{
    use HasFactory;
    protected $table = 'sintari_pegawai';
    protected $primaryKey = 'pegawai_id';

    function kalender()
    {
        return $this->belongsTo(Kalender::class, 'kalender_pegawai');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id_user');
    }
}
