<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WardBed extends Model
{
    use HasFactory;
    protected $table = "ward_beds";
    protected $guarded = ["id"];
    public $timestamps = false;

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id', "id");
    }
   
}
