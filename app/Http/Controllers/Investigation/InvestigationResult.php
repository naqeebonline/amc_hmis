<?php

namespace App\Http\Controllers\Investigation;

use App\Http\Controllers\Controller;
use App\Models\Configuration\InvestigationParameter;
use App\Models\Configuration\InvestigationSubCategory;
use App\Models\Patient\InvestigationResult as PatientInvestigationResult;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientInvestigation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use LDAP\Result;
use Yajra\DataTables\Facades\DataTables;

class InvestigationResult extends Controller
{
    //
    public function investigation_result()
    {

        $investigations = PatientInvestigation::with("patient", "admission", "investigation")->get();
        // return $investigations;

        return view("laboratory.investigation_result");
    }

    public function investigation_result_list()
    {
        $investigations = PatientInvestigation::whereStatus(0)->with("patient", "admission", "investigation");

        return DataTables::of($investigations)
            ->editColumn('status', function ($row) {
                return $row->status == 1
                    ? '<span class="badge bg-success badge-success">Success</span>'
                    : '<span class="badge bg-dark badge-warning">Pending</span>';
            })
            ->editColumn('patient_type', function ($row) {
                return $row->patient_type == 'general_patient'
                    ? '<span class="badge bg-success badge-success">General Patient</span>'
                    : '<span class="badge bg-danger badge-danger">Sehat Card Patient</span>';
            })
            ->addColumn("actions", function ($investigation) {
                return '<a href="' . route('pos.investigation_add_result', [$investigation->id, $investigation->investigation_sub_category_id]) . '"  data-details=\'' . $investigation . '\'  class="btn btn-sm btn-warning ">Result</a>'
                    . (($investigation->status == 1)
                        ? '<a href="' . route('pos.print_inv_result', [$investigation->id]) . '" class="btn btn-sm btn-success btn-inline-block ">Print</a>'
                        : '');
            })
            ->rawColumns(["status", "actions","patient_type"])
            ->make(true);
    }
    public function investigation_completed_list()
    {
        $investigations = PatientInvestigation::whereStatus(1)->with("patient", "admission", "investigation")->orderBy("inv_out_date","desc");

        return DataTables::of($investigations)
            ->editColumn('status', function ($row) {
                return $row->status == 1
                    ? '<span class="badge bg-success badge-success">Success</span>'
                    : '<span class="badge bg-dark badge-warning">Pending</span>';
            })
            ->addColumn("actions", function ($investigation) {
                return '<a href="' . route('pos.investigation_add_result', [$investigation->id, $investigation->investigation_sub_category_id]) . '"  data-details=\'' . $investigation . '\'  class="btn btn-sm btn-warning ">Result</a>'
                . (($investigation->status == 1)
                    ? '<a href="' . route('pos.print_inv_result', [$investigation->id]) . '" class="btn btn-sm btn-success btn-inline-block ">Print</a>'
                    : '');
            })
            ->rawColumns(["status", "actions"])
            ->make(true);
    }

    public function investigation_add_result($inv_id, $cat_id)
    {
        $data["investigation"] = PatientInvestigation::find($inv_id);
        $data['patient'] = Patient::where("id",$data["investigation"]->patient_id)->first();
        $sub_category = InvestigationSubCategory::find($cat_id);

        $data['sub_category'] = $sub_category;
        $data['is_textual'] = ($sub_category->is_parameter == 0) ? 1: 0;

        $data["is_ict"] = ($sub_category->is_ict == 1) ? true : false;
        $data["paramenters"] = InvestigationParameter::where("investigation_sub_category_id", $cat_id)->get();

        // return $paramenters;
        $data["result"] = PatientInvestigationResult::where("patient_investigation_id", $inv_id)->get();

        return view("laboratory.add_investigation_result", $data);
    }

    public function store_inv_result()
    {
       // dd(request()->all());
        $investigation  = $data["investigation"] = PatientInvestigation::find(request()->inv_id);
        $inv_result = PatientInvestigationResult::where("patient_investigation_id", request()->inv_id)->get();
        foreach ($inv_result as $result) {
            $result->delete();
        }

        if(request()->is_textual){
            PatientInvestigationResult::create([
                "patient_investigation_id" => request()->inv_id,
                "result_text_value" => request()->result_text_value,
                "result_entry_date" => Carbon::now(),
                "created_by" => auth()->user()->id,
                "updated_by" => auth()->user()->id,
            ]);
        }else{
            foreach (request()->parameter_id as $key => $paramenter) {
                PatientInvestigationResult::create([
                    "patient_investigation_id" => request()->inv_id,
                    "parameter_id" => $paramenter,
                    "result_value" => request()->result[$key],
                    "result_text_value" => request()->result_text_value[$key],
                    "result_entry_date" => Carbon::now(),
                    "created_by" => auth()->user()->id,
                    "updated_by" => auth()->user()->id,
                ]);
            }
        }


        $investigation->status = 1;
        $investigation->inv_out_date = Carbon::now();
        $investigation->inv_comment = request()->inv_comment;
        $investigation->update();
        
        return response()->json([
            "status"=> true,
            "message" => "Result Added"
        ]);
        
    }


    public function print_inv_result($inv_id)
    {
        $data["result"] = PatientInvestigation::where("id", $inv_id)->where("status", 1)->with("patient", "subCategory.main_category", "investigationResult.parameter", "admission.consultant")->first();
        $data['inv_sub_category'] = InvestigationSubCategory::where(['id'=>$data["result"]->investigation_sub_category_id])->first();

        return view("reports.investigation_result", $data);
    }
}
