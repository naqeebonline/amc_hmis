<?php

namespace App\Models\Appointments;

use App\Traits\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpdType extends Model
{
    use HasFactory;
    use Syncable;
    protected $table = "opd_type";
    protected $primaryKey = 'id';
    protected $guarded = ["id"];
    public $timestamps = true;
}
