<?php

namespace App\Http\Controllers\PatientReports;

use App\Http\Controllers\Controller;
use App\Models\Configuration\Consultants;
use App\Models\Configuration\ProcedureType;
use App\Models\Patient\PatientAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\DataTables;

class PatientReportController extends Controller
{

    public function list_all_patients()
    {
        $patients = PatientAdmission::where(["is_active" => 1])
            //->orWhere(["admission_status" => "Reffered"])
            ->with("patient", "ward", "bed", 'procedure_type', 'consultant')
            ->when(request()->from_date, function ($query) {

                $fromDate = Carbon::parse(request()->from_date)->endOfDay();
                $query->where('admission_date','>=',$fromDate);
            })
            ->when(request()->to_date, function ($query) {

                $toDate = Carbon::parse(request()->to_date)->endOfDay();
                $query->where('admission_date','<=',$toDate);
            })
            ->when(request()->consultant_id, function ($query) {
                $query->where('consultant_id',request()->consultant_id);
            })
            ->when(request()->procedure_type_id, function ($query) {
                $query->where(['procedure_type_id'=> request()->procedure_type_id]);
            })
            ->orderBy("admission_date","desc");

        return DataTables::of($patients)

            ->addColumn("edit_admission_date", function ($patient) {
                //dd(date("Y-m-d", strtotime($patient->admission_date)));
                return  date("Y-m-d", strtotime($patient->admission_date));
            })
            ->addColumn("actions", function ($patient) {
                $buttons =  '<a target="_blank" href="' . route('pos.print_patient_admission', [$patient->id]) . '" class="btn btn-primary btn-sm "><i class="bx bx-printer tf-icons"></i></a>
                      
                        ';
                if ($patient->procedure_type_id == 6) {
                    $buttons = $buttons . '<a  href="' . route('pos.patient_baby', [$patient->patient_id, $patient->id]) . '"  class="btn btn-primary btn-sm mt-2">View Baby Information</a>';

                }

                return $buttons;
            })
            ->rawColumns(["edit_admission_date", "actions"])
            ->make(true);
    }
    public function patient_admission_report()
    {
        
       $data['title'] = "Patient Admission Report";
       $data['procedure_type'] = ProcedureType::where(["is_active"=>1])->get();
       $data['consultant'] = Consultants::where(["is_active"=>1])->get();
       return view("PatientReports.patient_admission_report",$data);
    }

    public function print_patient_admission_report($form_date='',$to_date='',$consultant_id='',$procedure_id='')
    {
        $form_date = ($form_date == "nill") ? "" : $form_date;
        $to_date = ($to_date == "nill") ? "" : $to_date;
        $data['from_date'] = $form_date;
        $data['to_date'] = $to_date;
        $data['procedure_name'] = '-';
        $data['consultant_name'] = '-';
        if($consultant_id){
            $res = Consultants::whereId($consultant_id)->first();
            $data['consultant_name'] = $res->name;
        }
        if($procedure_id){
            $res = ProcedureType::whereId($procedure_id)->first();
            $data['procedure_name'] = $res->name;
        }
        $data['patients'] = PatientAdmission::where(["is_active" => 1])
            //->orWhere(["admission_status" => "Reffered"])
            ->with("patient", "ward", "bed", 'procedure_type', 'consultant')
            ->when($form_date, function ($query) use($form_date) {

                $fromDate = Carbon::parse($form_date)->endOfDay();
                $query->where('admission_date','>=',$fromDate);
            })
            ->when($to_date, function ($query) use($to_date) {

                $toDate = Carbon::parse($to_date)->endOfDay();
                $query->where('admission_date','<=',$toDate);
            })
            ->when($consultant_id, function ($query) use($consultant_id) {
                $query->where('consultant_id',$consultant_id);
            })
            ->when($procedure_id, function ($query) use ($procedure_id) {
                $query->where(['procedure_type_id'=> $procedure_id]);
            })
            ->orderBy("admission_date","desc")

            ->get();

        return view("PatientReports.PrintPatientAdmissionsReport",$data);
    }
}
