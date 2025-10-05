<?php

namespace App\Models\Machine;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineShift extends Model
{
    use HasFactory;
    protected $table = "machine_shifts";
    protected $guarded = ["id"];
    protected $primaryKey = 'id';
    public $timestamps = false;
}
