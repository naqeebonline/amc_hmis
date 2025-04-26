<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestigationParameter extends Model
{
    use HasFactory;
    protected $table = "investigation_sub_category_parameters";
    protected $guarded = ["id"];
    public $timestamps = false;

    public function investigation_sub_category(){
        return $this->belongsTo(InvestigationSubCategory::class,'investigation_sub_category_id');
    }
}
