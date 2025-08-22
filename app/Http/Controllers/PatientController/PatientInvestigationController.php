<?php

namespace App\Http\Controllers\PatientController;

use App\Http\Controllers\Controller;
use App\Models\Configuration\Consultants;
use App\Models\Configuration\District;
use App\Models\Configuration\InvestigationMainCategory;
use App\Models\Configuration\InvestigationParameter;
use App\Models\Configuration\InvestigationSubCategory;
use App\Models\Configuration\ProcedureType;
use App\Models\Configuration\ServiceType;
use App\Models\Configuration\Ward;
use App\Models\Configuration\WardBed;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientInvestigation;
use App\Models\Patient\PatientInvestigationPayment;
use App\Models\Patient\PatientLocation;
use App\Models\Patient\Relation;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class PatientInvestigationController extends Controller
{
    public function general_patient_investigation()
    {
        $data["title"] = "Patient Investigation";
        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();
        $data["relations"] = Relation::get();
        $data["district"] = District::get();
        $data["locations"] = PatientLocation::get();
        $data["consultants"] = Consultants::where(["is_active"=>1])->get();
        return view("investigation.patient_investigation",$data);
    }

    public function save_general_patient_investigation()
    {
        $data = request()->except(['_token', "id","list_investigations","invoice_no","discount_percentage","consultant_id"]);


        if(request()->id == 0){
            $number = (new PatientController())->generateMrNumber();
            $data['mr_no'] = $number;
            $data['regdate'] = request()->regdate." ".date("H:i:s");
            $data['patient_type'] = "hospital_patient";
        }

        $patient =  Patient::updateOrCreate(
            ["id" => request()->id],
            $data
        );
        $patient_id = $patient->id;
        $list_investigations = json_decode(request()->list_investigations);

        if(count($list_investigations) == 0){
            return response()->json([
                "status" => "empty",
                "message" => "Please Add Some investigation."
            ]);
        }



        $consultant_share_percentage = 0;
        $consultant_id = 0;
        if(request()->has('consultant_id')){
            $consultant_id = request()->consultant_id;
            $con = Consultants::where(["id"=>$consultant_id])->first();
            $consultant_share_percentage = $con->lab_percentage ?? 0;

        }
        $total_inv_amount = 0;
        foreach ($list_investigations as $key => $value){
            $investigation = InvestigationSubCategory::where("id",$value->investigation_id)->first();
            $investigation_rate_after_discount = ($investigation->sale_price * $value->frequency) - ($value->discount_amount);
            $total_inv_amount = ($total_inv_amount) + ($investigation_rate_after_discount);
            $data = [
                "invoice_no"        => request()->invoice_no,
                "patient_id"        => $patient_id,
                "investigation_sub_category_id"    => $value->investigation_id,
                "consultant_id"    => $consultant_id,
                "consultant_share_percentage"    => $consultant_share_percentage,
                "consultant_share_amount"    => ($investigation_rate_after_discount * $consultant_share_percentage)/100,
                "inv_amount"        => $investigation->price,
                "sale_price"        => ($investigation->sale_price * $value->frequency),
                "frequency"         => $value->frequency,
                "discount_percentage"    => $value->discount_percentage,
                "discount_amount"    => $value->discount_amount,
                "inv_date"    => date("Y-m-d H:i:s"),
                "created_by"    => auth()->user()->id,
                "created_at"    => date("Y-m-d H:i:s"),
                "patient_type"    => 'hospital_patient',

            ];
            PatientInvestigation::create($data);
        }

        PatientInvestigationPayment::create(["invoice_no"=>request()->invoice_no,"patient_id"=>$patient_id,"amount"=>$total_inv_amount,"created_by"=>auth()->user()->id,"created_at"=>date("Y-m-d H:i:s")]);

        return response()->json([
            "status" => true,
            "message" => "Record save successfully."
        ]);

    }

    public function get_list_investigations()
    {
        $patients = PatientInvestigation::where("is_active", 1)->with("investigation")->with("patient")

            ->when(request()->from_date, function ($query) {
                $query->whereDate('inv_date','>=',date("Y-m-d",strtotime(request()->from_date)));
            })
            ->when(request()->to_date, function ($query) {
                $query->whereDate('inv_date','<=',date("Y-m-d",strtotime(request()->to_date)));
            })
            ->where(["patient_type"=>"hospital_patient"])
            ->orderBy("id", "desc");
        return DataTables::of($patients)
            ->addColumn("actions", function ($patient) {
                $button = '';
                if ($patient->status == 1) {
                    $button = $button . '<a target="_blank" href="' . route('pos.print_inv_result', [$patient->id]) . '"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-success print_inv_record"><i class="tf-icons bx bx-printer"></i></a>';
                }else{
                    $button = $button . '<button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_record"><i class="bx bx-trash tf-icons"></i></button>';
                }


                return $button;
            })
            ->addColumn("received_amount", function ($patient) {
                return (($patient->sale_price) - ($patient->discount_amount)) * $patient->frequency;

            })
            ->addColumn("print_invoice_number", function ($patient) {
                return '<a target="_blank" href="' . route('pos.print_hospital_lab_invoice', [$patient->invoice_no]) . '">' . $patient->invoice_no . '</a>';
            })
            ->rawColumns(["print_invoice_number","received_amount", "actions"])
            ->make(true);
    }



    public function save_patient_investigation()
    {
        $data = request()->except(['_token', "id"]);

        $data['inv_date'] = request()->inv_date . " " . date("h:i:s");
        if (request()->id == 0) {
            $data['created_by'] = auth()->user()->id;
            $data['patient_type'] = "sehat_card";
        } else {
            $data['updated_by'] = auth()->user()->id;
        }
        PatientInvestigation::updateOrCreate(
            ["id" => request()->id],
            $data
        );

        return response()->json([
            "status" => true,
            "message" => "Record save successfully."
        ]);
    }


    public function delete_patient_investigation()
    {


        PatientInvestigation::whereId(request()->id)->update(["is_active"=>0]);
        $list_investigations = PatientInvestigation::whereId(request()->id)->first();
        $invoice_no = $list_investigations->invoice_no ?? "";
        $list_investigations = PatientInvestigation::with("subCategory")->where("invoice_no",$invoice_no)->where(["is_active"=>1])->get();
        $total_inv_amount = 0;


        foreach ($list_investigations as $key => $value){

            //$investigation = InvestigationSubCategory::where("id",$value->investigation_id)->first();

            $investigation_rate_after_discount = ($value->sale_price) - ($value->discount_amount);
            $total_inv_amount = ($total_inv_amount) + ($investigation_rate_after_discount);
        }

        PatientInvestigationPayment::where("invoice_no",$invoice_no)->update(["amount"=>$total_inv_amount]);
        return ["status"=>true,"message"=>"Record saved successfully"];
    }





}
