<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libnas extends Model
{
    use HasFactory;
    protected $table ='sintari_kalender_libur';
    protected $primaryKey ='id_libur';
}
