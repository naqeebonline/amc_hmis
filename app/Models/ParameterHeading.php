<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParameterHeading extends Model
{
    use HasFactory;
    protected $table = "parameter_heading";
    protected $primaryKey = 'id';
    protected $guarded = ["id"];
    public $timestamps = false;

  
    
}
