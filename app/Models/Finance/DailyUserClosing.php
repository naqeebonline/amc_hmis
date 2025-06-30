<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyUserClosing extends Model
{
    use HasFactory;
    protected $table = "daily_user_closings";
    protected $primaryKey = 'id';
    protected $guarded = ["id"];
    public $timestamps = false;
}
