<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemMake extends Model
{
    use HasFactory;
    protected $table = "item_make";
    protected $guarded = ["id"];
    public $timestamps = false;

}
