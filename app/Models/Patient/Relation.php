<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Relation extends Model
{
    use HasFactory;
    protected $table = "relations";
    protected $guarded = ["id"];
    protected $primaryKey = 'id';
    public $timestamps = false;
}
