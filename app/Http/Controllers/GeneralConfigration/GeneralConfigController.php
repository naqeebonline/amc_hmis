<?php

namespace App\Http\Controllers\GeneralConfigration;

use App\Http\Controllers\Controller;
use App\Models\Configuration\ProcedureType;
use App\Models\Configuration\ServiceType;
use App\Models\Configuration\Ward;
use App\Models\Configuration\WardBed;
use App\Models\Patient\PatientLocation;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class GeneralConfigController extends Controller
{
    public function add_ward()
    {
        $data["title"] = "Add New Ward";
        return view("general_configuration.ward",$data);
    }

    public function list_ward()
    {
        $res = Ward::where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_ward()
    {
        Ward::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function add_bed()
    {
        $data["title"] = "Add New Bed";
        $data['wards'] = Ward::whereIsActive(1)->get();
        return view("general_configuration.bed",$data);
    }

    public function list_bed()
    {
        $res = WardBed::with(["ward"])->where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_bed()
    {

        WardBed::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function add_service_type()
    {
        $data["title"] = "Add Service Type";
        return view("general_configuration.service_type",$data);
    }

    public function list_service_type()
    {
        $res = ServiceType::where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_service_type()
    {
        ServiceType::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function add_procedure_type()
    {
        $data["title"] = "Add Procedure Type";
        return view("general_configuration.procedure_type",$data);
    }

    public function list_procedure_type()
    {
        $res = ProcedureType::where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_procedure_type()
    {
        //dd(request()->all());
        ProcedureType::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }


    public function add_locations()
    {
        $data["title"] = "Add New Locations";
        return view("general_configuration.locations",$data);
    }

    public function list_locations()
    {
        $res = PatientLocation::where(["is_active"=>1]);

        return DataTables::of($res)
            ->addColumn('action', function($cert) {
                $details = json_encode($cert);
                if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                }else{
                    $html = "";
                }
                return $html;
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function save_locations()
    {
        PatientLocation::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["id","_token"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }
}
