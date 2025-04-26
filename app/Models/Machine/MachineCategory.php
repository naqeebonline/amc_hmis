<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineCategory extends Model
{
    use HasFactory;
    protected $table = "machine_categories";
    protected $guarded = ["id"];
    protected $primaryKey = 'id';
    public $timestamps = false;
}
