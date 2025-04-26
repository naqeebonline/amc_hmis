<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantDepartment extends Model
{
    use HasFactory;
    protected $table = "consultant_department";
    protected $guarded = ["id"];
    public $timestamps = false;


   
}
