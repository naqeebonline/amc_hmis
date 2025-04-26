<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantSpeciality extends Model
{
    use HasFactory;
    protected $table = "consultant_speciality";
    protected $guarded = ["id"];
    public $timestamps = false;


   
}
