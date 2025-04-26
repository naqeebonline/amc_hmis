<?php

namespace App\Http\Controllers\PatientController;

use App\Http\Controllers\Controller;
use App\Models\Configuration\Consultants;
use App\Models\Configuration\Investigation;
use App\Models\Configuration\InvestigationSubCategory;
use App\Models\Configuration\ProcedureType;
use App\Models\Configuration\ServiceType;
use App\Models\Configuration\Ward;
use App\Models\Configuration\WardBed;
use App\Models\Machine\MachinePatient;
use App\Models\Machine\MachineShift;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientAdmission;
use App\Models\Patient\PatientBaby;
use App\Models\Patient\PatientDischargeChecklist;
use App\Models\Patient\PatientInvestigation;
use App\Models\Patient\PatientNurseNote;
use App\Models\Patient\PatientOtNote;
use App\Models\Patient\PatientRefund;
use App\Models\Patient\PatientServiceCharges;
use App\Models\Patient\PatientVital;
use App\Models\Patient\Relation;
use App\Models\SaleDetails;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Contracts\DataTable;
use Yajra\DataTables\Facades\DataTables;

class PatientAdmissionController extends Controller
{

    public function patient_admission()
    {
        $data['title'] = "Patient Admission";
        $admitted_patient = PatientAdmission::where(["admission_status" => "Admit","is_active"=>1])->pluck("patient_id");
        //dd($admitted_patient);
        $data["patients"] = Patient::whereNotIn("id", $admitted_patient)->get();
        $data["relations"] = Relation::get();
        $data["wards"] = Ward::get();
        $data["procedure_type"] = ProcedureType::whereIsActive(1)->get();
        $data["consultant"] = Consultants::whereIsActive(1)->get();
        return view("patients.patient_admission", $data);
    }

    public function discharge_patient()
    {
        $data['title'] = "Discharge Patient";
        $data['procedure_type'] = ProcedureType::get();
        return view("patients.discharge_patient", $data);
    }


    public function list_admission()
    {
        $patients = PatientAdmission::where(["is_active"=> 1,"admission_status"=>"Admit"])->with("patient", "ward", "bed", 'procedure_type', 'consultant','sub_consultant')
        ->orderBy("id","desc");
        $res = [];
        return DataTables::of($patients)

            ->addColumn("edit_admission_date", function ($patient) {
                //dd(date("Y-m-d", strtotime($patient->admission_date)));
                return  date("Y-m-d", strtotime($patient->admission_date));
            })
            ->addColumn("alert", function ($patient) {
                $res = (new PatientExpenseController())->getAdmissionDetails($patient->id);
                return  $res['alert'];
            })
            ->addColumn("totalCost", function ($patient) use($res) {
                $res = (new PatientExpenseController())->getAdmissionDetails($patient->id);
                return  $res['totalCost'];
            })
            ->addColumn("balance", function ($patient) use($res) {
                $res = (new PatientExpenseController())->getAdmissionDetails($patient->id);
                return  $res['balance'];
            })
            ->addColumn("actions", function ($patient) {
                $buttons =  '<a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-warning edit_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_record"><i class="bx bx-trash tf-icons"></i></button>
                        <a href="' . route('pos.view_patient_summary', [$patient->patient_id, $patient->id]) . '" class="btn btn-primary btn-sm "><i class="bx bxs-show tf-icons"></i></a>
                        <a href="' . route('pos.print_patient_admission', [$patient->id]) . '" class="btn btn-primary btn-sm "><i class="bx bx-printer tf-icons"></i></a>
                      
                        ';
                if ($patient->admission_status == "Admit") {
                    $buttons = $buttons . '<button data-id="' . $patient->id . '" class="btn btn-danger btn-sm cancel_admission mt-2">Admission Cancelation</button>';
                }
                if ($patient->procedure_type_id == 6 || $patient->procedure_type_id == 208 || $patient->procedure_type_id == 211) {
                    $buttons = $buttons . '<a  href="' . route('pos.patient_baby', [$patient->patient_id, $patient->id]) . '"  class="btn btn-success btn-sm mt-2">Add Baby Information</a>';
                }




                return $buttons;
            })

            ->rawColumns(["edit_admission_date",'totalCost','balance','alert', "actions"])
            ->make(true);
    }


    public function discharged_patient()
    {
        $data['title'] = "Discharged Patient";
        $data["procedure_type"] = ProcedureType::whereIsActive(1)->get();
        $data["consultant"] = Consultants::whereIsActive(1)->get();
        return view("patients.discharged_patient_list", $data);
    }

    public function discharged_patient_list()
    {

        $patients = PatientAdmission::where(["is_active" => 1, "admission_status" => "Discharged"])
            ->with("patient", "ward", "bed", 'procedure_type', 'consultant','sub_consultant')

            ->when(request()->from_date, function ($query) {
                $fromDate = Carbon::parse(request()->from_date)->endOfDay();
                $query->where('discharge_date','>=',date("Y-m-d",strtotime($fromDate)));
            })
            ->when(request()->to_date, function ($query) {
                $toDate = Carbon::parse(request()->to_date)->endOfDay();
                $query->where('discharge_date','<=',date("Y-m-d",strtotime($toDate)));
            })
            ->when(request()->procedure_type_id, function ($query) {
              //  dd("here");
                 $query->where(['patient_admissions.procedure_type_id'=> request()->procedure_type_id]);
            })
            /*->when(request()->consultant_id, function ($query)  {
                $query->where(function ($query) {
                    $query->where('consultant_id', request()->consultant_id);
                       // ->orWhere('sub_consultant_id', request()->consultant_id);
                });

            })*/
            ->when(request()->consultant_id, function ($query) {
                 $query->where(['consultant_id'=> request()->consultant_id]);//->orWhere(['sub_consultant_id'=> request()->consultant_id]);
            })
            ->orderBy("discharge_date","desc");
        return DataTables::of($patients)
            ->addColumn("edit_admission_date", function ($patient) {
                return  date("Y-m-d", strtotime($patient->admission_date));
            })
            ->addColumn("actions", function ($patient) {
                $buttons =  '<a target="_blank" href="' . route('pos.print_patient_admission', [$patient->id]) . '" class="btn btn-primary btn-sm "><i class="bx bx-printer tf-icons"></i></a>
                      <a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-warning edit_record"><i class="tf-icons bx bx-pencil"></i></a>
                      <a href="' . route('pos.view_patient_summary', [$patient->patient_id,$patient->id]) . '"  class="btn btn-sm btn-success edit_record"><i class="bx bxs-show tf-icons"></i></a>
                        ';
                if ($patient->procedure_type_id == 6 || $patient->procedure_type_id == 208 || $patient->procedure_type_id == 211) {
                    $buttons = $buttons . '<a  href="' . route('pos.patient_baby', [$patient->patient_id, $patient->id]) . '"  class="btn btn-primary btn-sm mt-2">View Baby Information</a>';
                }
                return $buttons;
            })
            ->addColumn("alert", function ($patient) {
                $res = (new PatientExpenseController())->getAdmissionDetails($patient->id);
                return  $res['alert'];
            })
            ->addColumn("totalCost", function ($patient) {
                $res = (new PatientExpenseController())->getAdmissionDetails($patient->id);
                return  $res['totalCost'];
            })
            ->addColumn("balance", function ($patient)  {
                $res = (new PatientExpenseController())->getAdmissionDetails($patient->id);
                return  $res['balance'];
            })
            ->rawColumns(["alert","totalCost","balance","edit_admission_date", "actions"])
            ->make(true);
    }


    public function ward_bed()
    {
        $occupied_beds = PatientAdmission::whereIsActive(1)->where(["admission_status" => "Admit"])->pluck('bed_id');
        $ward_bed = WardBed::where("ward_id", request()->id)
            ->when($occupied_beds, function ($query) use ($occupied_beds) {
                $query->whereNotIn('id', $occupied_beds)->whereIsActive(1)->get();
            })
            ->whereIsActive(1)->get();
        return response()->json([
            "status" => true,
            "message" => "Record Found.",
            "data" => $ward_bed
        ]);
    }

    public function store_patient_admission()
    {

        $data = request()->except(['_token', "id"]);
        if (request()->id == 0) {
            $data['admission_date'] = request()->admission_date . " " . date("H:i:s");

            $data['created_by'] = auth()->user()->id;
        } else {
            $data = request()->except(['_token', "id","patient_id"]);
            $data['updated_by'] = auth()->user()->id;
        }
        $procedure_type_id = ProcedureType::where(["id"=>request()->procedure_type_id])->first();

        $consultant = Consultants::where(["id"=>request()->consultant_id])->first();
        $data['consultant_share'] = $consultant->share_percentage;
        $data['procedure_rate'] = $procedure_type_id->net_rate;
        $data['sec_procedure_rate'] = 0;
        if(request()->sec_procedure_type_id){
            $sec_procedure_type_id = ProcedureType::where(["id"=>request()->sec_procedure_type_id])->first();
            $data['sec_procedure_rate'] = $sec_procedure_type_id->net_rate ?? 0;
        }

        PatientAdmission::updateOrCreate(
            ["id" => request()->id],
            $data
        );

        return response()->json([
            "status" => true,
            "message" => "Record save successfully."
        ]);
    }

    public function patient_account($patient_id, $admission_id)
    {
        $data['patient'] = Patient::whereId($patient_id)->whereIsActive(1)->first();

        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();
        $data['admission'] = PatientAdmission::with(["ward", "bed"])->whereId($admission_id)->whereIsActive(1)->first();
        return view("patients.patient_account", $data);
    }

    public function save_patient_investigation()
    {
        $data = request()->except(['_token', "id"]);
        $data['inv_date'] = request()->inv_date . " " . date("h:i:s");
        $investigation = InvestigationSubCategory::where(["id"=>request()->investigation_sub_category_id])->first();
        $data['inv_amount'] = $investigation->price ?? 0;
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


    public function get_admission_investigations($patient_id = '', $admission_id = '')
    {
        $patients = PatientInvestigation::where("is_active", 1)->with("investigation")
            ->when($patient_id, function ($query) use ($patient_id) {
                $query->where('patient_id', $patient_id);
            })
            ->when($admission_id, function ($query) use ($admission_id) {
                $query->where('admission_id', $admission_id);
            })
            ->orderBy("id", "desc");
        return DataTables::of($patients)
            ->addColumn("actions", function ($patient) {
                $button = '';
                if ($patient->status == 1) {
                    $button = $button . '<a target="_blank" href="' . route('pos.print_inv_result', [$patient->id]) . '"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-success print_inv_record"><i class="tf-icons bx bx-printer"></i></a>';
                }
                $button = $button . '<button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_record"><i class="bx bx-trash tf-icons"></i></button>';

                return $button;
            })

            ->addColumn("print_invoice_number", function ($patient) {
                return '<a target="_blank" href="' . route('pos.print_lab_invoice', [$patient->invoice_no]) . '">' . $patient->invoice_no . '</a>';
            })
            ->rawColumns(["print_invoice_number", "actions"])
            ->make(true);
    }

    public function save_patient_service_charges()
    {
        $data = request()->except(['_token', "id"]);
        $data['service_date'] = request()->service_date . " " . date("h:i:s");

        PatientServiceCharges::updateOrCreate(
            ["id" => request()->id],
            $data
        );

        return response()->json([
            "status" => true,
            "message" => "Record save successfully."
        ]);
    }

    public function get_admission_service_charges($patient_id = '', $admission_id = '')
    {
        $patients = PatientServiceCharges::where("is_active", 1)->with("service_type")
            ->when($patient_id, function ($query) use ($patient_id) {
                $query->where('patient_id', $patient_id);
            })
            ->when($admission_id, function ($query) use ($admission_id) {
                $query->where('admission_id', $admission_id);
            })
            ->orderBy("id", "desc");
        return DataTables::of($patients)
            ->addColumn("actions", function ($patient) {
                return '<a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-warning edit_service_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_service_record"><i class="bx bx-trash tf-icons"></i></button>
                 
                        ';
            })
            ->rawColumns(["actions"])
            ->make(true);
    }

    public function get_admitted_patient_treatments($patient_id, $admission_id)
    {
        $patients = SaleDetails::with("product", "sale")
            ->when($patient_id, function ($query) use ($patient_id) {
                $query->where('patient_id', $patient_id);
            })
            ->when(request()->medicine_type, function ($query) {
                $query->whereHas('sale', function ($q) {

                    $q->where('medicine_type', request()->medicine_type);
                });
            })

            ->when($admission_id, function ($query) use ($admission_id) {
                $query->where('admission_id', $admission_id);
            });
        return DataTables::of($patients)
            ->addColumn("actions", function ($patient) {
                if($patient->ReturnQuantity == $patient->Quantity){
                    return "";
                }else{
                    return '<a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-primary return_product">Return</a>';
                }

            })
            ->addColumn("total_amount", function ($value) {
                $total = ($value->UnitePrice) * ($value->Quantity);
                return number_format($total, 2);
            })
            ->addColumn("total_consumed", function ($value) {
                $total = ($value->Quantity) - ($value->ReturnQuantity);
                return $total;
            })

            ->rawColumns(["actions", "total_amount","total_consumed"])
            ->make(true);
    }


    public function list_admitted_patients()
    {
        $patients = PatientAdmission::where(["is_active"=> 1, "admission_status"=> "Admit"])
            //->where(["admission_status"=>"Admit"])
            ->with("patient", "ward", "bed", 'procedure_type', 'consultant');
        return DataTables::of($patients)

            ->addColumn("edit_admission_date", function ($patient) {
                //dd(date("Y-m-d", strtotime($patient->admission_date)));
                return  date("Y-m-d", strtotime($patient->admission_date));
            })
            ->addColumn("actions", function ($patient) {
                return ' 
                        <a href="' . route('pos.discharge_patient_from_ward', [$patient->id]) . '" class="btn btn-danger btn-sm">Discharge</button>
                       
                        ';
            })
            ->rawColumns(["edit_admission_date", "actions"])
            ->make(true);
    }

    public function discharge_patient_from_ward($id)
    {
        $data['data'] = PatientAdmission::whereId($id)->with(['patient', 'consultant', 'ward', 'bed'])->first();
        $data['procedure_type_id'] = $data['data']->procedure_type_id;
        $data['baby_count'] = PatientBaby::where(["admission_id"=>$id])->count();

        $data['admission_id'] = $id;

        $data['checklist'] = PatientDischargeChecklist::where(["admission_id" => $id])->first();

        return view("patients.discharge_patient_checklist", $data);
    }

    public function save_discharge_checklist()
    {
        PatientDischargeChecklist::updateOrCreate(
            ["id" => request()->id],
            request()->except(["id", "_token","admission_status","discharge_summary","sc_ref_no"])
        );
        PatientAdmission::whereId(request()->admission_id)->update(["discharge_date" => date("Y-m-d H:i:s"), "admission_status" => request()->admission_status,'discharge_summary'=>request()->discharge_summary,"sc_ref_no"=>request()->sc_ref_no]);

        return redirect()->back();
    }






    public function patient_summary()
    {
        $data["patients"] = PatientAdmission::with("patient", "ward", "bed","consultant","procedure_type")
            ->where("admission_status", "Admit")
            ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->get();
        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();


        return view("patients.patient_summary", $data);
    }



    public function patient_investigation()
    {
        $data["patients"] = PatientAdmission::where("admission_status", "Admit")
            ->with("patient", "ward", "bed")->get();
        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();
        return view("patients.patient_investigation", $data);
    }

    function patient_service_charges()
    {
        $data["patients"] = PatientAdmission::where("admission_status", "Admit")
            ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->with("patient", "ward", "bed")->get();
        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();
        return view("patients.patient_service_charge", $data);
    }

    public function ot_notes()
    {
        //dd(Carbon::now()->subDay(2)->format('Y-m-d H:i:s'));
        $data["patients"] = PatientAdmission::where("admission_status", "Admit")
            ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->with("patient", "ward", "bed")->get();
        return view("patients.patient_ot_notes", $data);
    }


    public function save_patient_ot_notes()
    {
        $data = request()->except(['_token', "id"]);
        if (request()->id == 0) {
            $data["created_by"] = auth()->user()->id;
        } else {
            $data["updated_by"] = auth()->user()->id;
        }
        PatientOtNote::updateOrCreate(
            ["id" => request()->id],
            $data
        );
        return response()->json([
            "status" => true,
            "message" => "Record save successfully."
        ]);
    }

    public function get_patient_ot_notes($patient_id, $admission_id)
    {
        $patient_ot_notes = PatientOtNote::where(["patient_id" => $patient_id, "admission_id" => $admission_id])->whereIsActive(1)->with("patient", "admission");

        return DataTables::of($patient_ot_notes)
            ->addColumn("actions", function ($patient) {
                return '<a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-warning edit_service_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_service_record"><i class="bx bx-trash tf-icons"></i></button>
                 
                        ';
            })
            ->rawColumns(["actions"])
            ->make(true);
    }


    public function save_patient_nurse_notes()
    {
        $data = request()->except(['_token', "id"]);
        if (request()->id == 0) {
            $data["created_by"] = auth()->user()->id;
        } else {
            $data["updated_by"] = auth()->user()->id;
        }


        PatientNurseNote::updateOrCreate(
            ["id" => request()->id],
            $data
        );
        return response()->json([
            "status" => true,
            "message" => "Record save successfully."
        ]);
    }

    public function patient_nurse_notes()
    {
        $data["patients"] = PatientAdmission::where("admission_status", "Admit")
            ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->with("patient", "ward", "bed")->get();
        return view("patients.patient_nurse_notes", $data);
    }

    public function get_patient_nurse_notes($patient_id, $admission_id)
    {
        $patient_ot_notes = PatientNurseNote::where(["patient_id" => $patient_id, "admission_id" => $admission_id])->whereIsActive(1)->with("patient", "admission");

        return DataTables::of($patient_ot_notes)
            ->addColumn("actions", function ($patient) {
                return '<a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-warning edit_service_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_service_record"><i class="bx bx-trash tf-icons"></i></button>
                 
                        ';
            })
            ->rawColumns(["actions"])
            ->make(true);
    }

    public function print_patient_admission($id)
    {
        $data["patient"] = PatientAdmission::whereId($id)->with("user", "patient", "ward", "bed", "consultant", "procedure_type",'relation')->first();

        $data['cnic_array'] =str_split($data["patient"]->patient->cnic);
        $data['cnic_array'] = array_reverse($data['cnic_array'], true);

        return view("reports.patient_admission_report", $data);
    }


    public function patient_refunds()
    {
        $data["patients"] = PatientAdmission::where("admission_status", "Admit")->with("patient", "consultant", "ward", "bed","sec_procedure")->get();
        $data["consultants"] = Consultants::get();
        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();
        return view("patients.patient_refunds", $data);
    }


    public function save_patient_refunds()
    {
        $data = request()->except(['_token', "id"]);
        $data["investigation_ids"] = json_encode(request()->investigation_ids);
        if (request()->id != 0) {
            $data["updated_at"] = Carbon::now();
            $data["updated_by"] = auth()->user()->id;
        } else {
            $data["created_by"] = auth()->user()->id;
        }
        PatientRefund::updateOrCreate(
            ["id" => request()->id],
            $data
        );
    }

    public function get_patient_refunds($patient_id, $admission_id)
    {
        $patient_refunds = PatientRefund::where(["patient_id" => $patient_id, "admission_id" => $admission_id])->first();

        return response()->json([
            "status" => true,
            "data" => $patient_refunds
        ]);
    }

    public function print_lab_invoice($invoice)
    {
        $data['data'] = PatientInvestigation::with(["investigation", "patient"])->where(["invoice_no" => $invoice])->whereIsActive(1)->get();
        return view("reports.print_lab_invoice", $data);
    }

    public function get_ward_investigations($admission_id)
    {
        $admission = PatientAdmission::whereId($admission_id)->first();
        $ward = Ward::whereId($admission->ward_id)->first();
        if ($ward->short_name == "Dialysis") {
            $patientId = $admission->patient_id;
            $investigationIds = [1, 16, 60, 17, 18, 11]; // List of investigation IDs to check

            $filteredInvestigations = collect($investigationIds)->filter(function ($investigationId) use ($patientId) {
                // Check if the investigation was performed in the last 30 days
                $recentInvestigation = DB::table('patient_investigations')
                    ->where('patient_id', $patientId)
                    ->where('investigation_sub_category_id', $investigationId)
                    ->whereDate('created_at', '>=', Carbon::now()->subDays(30)) // Performed within the last 30 days
                    ->exists();

                // Allow only investigations not performed in the last 30 days
                return !$recentInvestigation;
            });

            $investigations = InvestigationSubCategory::whereIn('id', $filteredInvestigations->toArray())
                ->whereIsActive(1)
                ->get();


           // $investigations = InvestigationSubCategory::whereIn("id", [1, 16, 60, 17, 18, 11])->whereIsActive(1)->get();


        } else {
            $investigations = InvestigationSubCategory::whereIsActive(1)->get();
        }
        foreach ($investigations as $key => $value){
            $value->price = ($value->price) ? $value->price : 0;
        }
        return response()->json(["status" => true, "data" => $investigations]);
    }



    public function patient_vitals(){
        $data["patients"] = PatientAdmission::where("admission_status", "Admit")
            ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->with("patient", "ward", "bed")->get();
        return view("patients.patient_vitals", $data);
    }

    public function patient_vitals_list($patient_id, $admission_id){
        $vitals = PatientVital::where(["patient_id"=>$patient_id, "admission_id"=>$admission_id])->whereIsActive(1);

        return DataTables::of($vitals)
            ->addColumn("actions", function ($vital) {
                return '<a href="javascript:void(0)"  data-details=\'' . $vital . '\'  class="btn btn-sm btn-warning edit_service_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $vital->id . '" class="btn btn-danger btn-sm delete_service_record"><i class="bx bx-trash tf-icons"></i></button>
                 
                        ';
            })
            ->rawColumns(["actions"])
            ->make(true);
        
    }

    public function save_patient_vitals(){
        PatientVital::updateOrCreate(
            ["id"=>request()->id],
            request()->except(["_token","id"])
        );
        return ["status"=>true,"message"=>"Record saved successfully"];
    }

    public function print_admitted_patient_treatment_report($patient_id, $admission_id)
    {
        $data['admission'] = PatientAdmission::with('patient')->where(["id"=>$admission_id])->first();
        $data['data'] = SaleDetails::with("product", "sale")
            ->when($patient_id, function ($query) use ($patient_id) {
                $query->where('patient_id', $patient_id);
            })
            ->when($admission_id, function ($query) use ($admission_id) {
                $query->where('admission_id', $admission_id);
            })->get();
        return view("reports.customer_purchase_report_new",$data);
    }

    public function patient_treatment_chart_report($patient_id, $admission_id,$medicine_type_id='')
    {
        $data['admission'] = PatientAdmission::with('patient')->where(["id"=>$admission_id])->first();
        $data['data'] = SaleDetails::with("product", "sale")
            ->when($patient_id, function ($query) use ($patient_id) {
                $query->where('patient_id', $patient_id);
            })
            ->when($admission_id, function ($query) use ($admission_id) {
                $query->where('admission_id', $admission_id);
            })
            ->when($medicine_type_id, function ($query) use($medicine_type_id) {
                $query->whereHas('sale', function ($q) use($medicine_type_id){

                    $q->where('medicine_type', $medicine_type_id);
                });
            })
            ->get();
        return view("reports.patient_treatment_chart_report",$data);
    }

    public function cencel_patient_admission()
    {
        $data = request()->except(["_token","id",'']);
         $data['admission_status'] = "Canceled";
         $data['discharge_summary'] = request()->discharge_summary;
         $data['discharge_date'] = date("Y-m-d H:i:s");

        PatientAdmission::updateOrCreate(
            ["id" => request()->id],
            $data
        );
        return response()->json(["status" => true, "data" => "done"]);
    }

    public function canceled_patients()
    {
        $data['title'] = "Canceled Admissions";
        return view("patients.canceled_patient_list", $data);
    }
    public function canceled_patient_list()
    {
        $patients = PatientAdmission::where(["is_active" => 1, "admission_status" => "Canceled"])->with("patient", "ward", "bed", 'procedure_type', 'consultant');
        return DataTables::of($patients)

            ->addColumn("edit_admission_date", function ($patient) {
                //dd(date("Y-m-d", strtotime($patient->admission_date)));
                return  date("Y-m-d", strtotime($patient->admission_date));
            })
            ->addColumn("canelation_reason_text", function ($patient) {
                return "<p style='color: red'>".$patient->canelation_reason."</p>";
            })
            ->addColumn("actions", function ($patient) {
                $buttons =  '<a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-warning edit_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_record"><i class="bx bx-trash tf-icons"></i></button>
                        <a href="' . route('pos.patient_account', [$patient->patient_id, $patient->id]) . '" class="btn btn-primary btn-sm "><i class="bx bxs-show tf-icons"></i></a>
                        <a href="' . route('pos.print_patient_admission', [$patient->id]) . '" class="btn btn-primary btn-sm "><i class="bx bx-printer tf-icons"></i></a>
                      
                        ';
                if ($patient->admission_status == "Admit") {
                    $buttons = $buttons . '<button data-id="' . $patient->id . '" class="btn btn-danger btn-sm cancel_admission mt-2">Admission Cancelation</button>';
                }
                return $buttons;
            })
            ->rawColumns(["edit_admission_date", "actions","canelation_reason_text"])
            ->make(true);
    }

    public function update_patient_admission()
    {
        PatientAdmission::where(["id"=>request()->admission_id])->update(
            [
                "consultant_id"=>request()->consultant_id,
                "sub_consultant_id"=>request()->sub_consultant_id,
                "procedure_type_id"=>request()->procedure_type_id,
                "consultant_share"=>request()->consultant_share,
                "procedure_rate"=>request()->procedure_rate,
                "sec_procedure_type_id"=>request()->sec_procedure_type_id,
                "sec_procedure_rate"=>request()->sec_procedure_rate ?? 0,
                //"sc_ref_no"=>request()->edit_sc_ref_no ?? 0,
                "updated_by"=>auth()->user()->id,
            ]
        );
        return ["status"=>true,"message"=>"Record updated successfully"];
    }

    public function birth_certificate($baby_id)
    {
        $data['baby'] = PatientBaby::with(['admission','patient'])->where(['id'=>$baby_id])->get();

        return view("reports.birth_certificate",$data);
    }

    public function patient_baby($patient_id='',$admission_id='')
    {
        $data['admission'] = PatientAdmission::where(["id"=>$admission_id])->first();
        $data['patient'] = Patient::where(["id"=>$patient_id])->first();
       //  dd($data['patient']);
        $data['title'] = "Patient Baby";
        $data['patient_id'] = $patient_id;
        $data['admission_id'] = $admission_id;
        return view('patients.patient_baby',$data);
    }

    public function list_patient_baby($patient_id, $admission_id){
        $vitals = PatientBaby::where(["patient_id"=>$patient_id, "admission_id"=>$admission_id])->where(["is_active"=>1]);

        return DataTables::of($vitals)
            ->addColumn("actions", function ($vital) {
                $buttons = '<a href="javascript:void(0)"  data-details=\'' . $vital . '\'  class="btn btn-sm btn-warning edit_service_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $vital->id . '" class="btn btn-danger btn-sm delete_record"><i class="bx bx-trash tf-icons"></i></button>
                 
                        ';
                if($vital->baby_status == 'Alive'){
                    $buttons = $buttons . '<a target="_blank"  href="' . route('pos.birth_certificate', [$vital->id]) . '"  class="btn btn-primary btn-sm mt-2">Print</a>';
                }

                return $buttons;

            })
            ->rawColumns(["actions"])
            ->make(true);

    }

    public function save_baby()
    {
        $data = request()->except(['_token', "id"]);

        if (request()->id == 0) {
            $data['created_by'] = auth()->user()->id;
        } else {
            $data['updated_by'] = auth()->user()->id;
        }
        PatientBaby::updateOrCreate(
            ["id" => request()->id],
            $data
        );

        return response()->json([
            "status" => true,
            "message" => "Record save successfully."
        ]);
    }

    public function getMachinePatientReport()
    {

        $data = [
            ["day" =>"monday"],
            ["day" =>"tuesday"],
            ["day" =>"wednesday"],
            ["day" =>"thursday"],
            ["day" =>"friday"],
            ["day" =>"saturday"],
            ["day" =>"sunday"],
        ];
        foreach ($data as $key => $value){
            $data[$key]['shift'] = MachineShift::get();
        }

        foreach ($data as $key => $value){
            foreach ($value['shift'] as $key2 => $shift){
                $value['shift'][$key2]['patients'] = MachinePatient::with('patient','machine_shift','machine_category')->where(["day"=>$value['day'],"machine_shift_id"=> $shift->id,"is_active"=>1])->get();
            }
        }
        $data['data'] = $data;
        return view('reports.machine_patient_report',$data);
    }

    public function view_patient_summary($patient_id,$admission_id)
    {
        $data["patient"] = PatientAdmission::with("patient", "ward", "bed","consultant","procedure_type")
            ->where("id", $admission_id)

            ->first();
        $data['summary'] =  (new PatientExpenseController())->getAdmissionDetails($admission_id);

        $data['investigation'] = InvestigationSubCategory::whereIsActive(1)->get();
        $data['service_type'] = ServiceType::whereIsActive(1)->get();



        $data['patient_id'] = $patient_id;
        $data['admission_id'] = $admission_id;
        return view("patients.view_patient_summary", $data);
    }


    
    
}
