<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantType extends Model
{
    use HasFactory;
    protected $table = "consultant_type";
    protected $guarded = ["id"];
    public $timestamps = false;


   
}
