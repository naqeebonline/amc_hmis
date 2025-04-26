<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PatientController\PatientController;
use App\Models\Appointments\Appointment;
use App\Models\Appointments\OpdType;
use App\Models\Configuration\Consultants;
use App\Models\Configuration\District;
use App\Models\Configuration\ProcedureType;
use App\Models\Configuration\Ward;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientAdmission;
use App\Models\Patient\PatientLocation;
use App\Models\Patient\Relation;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AppointmentController extends Controller
{
    public function appointment()
    {
        $data["consultants"] = Consultants::where(["is_active"=>1])->get();
        $data["relations"] = Relation::get();
        $data["district"] = District::get();
        $data["locations"] = PatientLocation::get();
        $data["opd_type"] = OpdType::get();

        return view("appointments.appointments", $data);
    }

    public function print_appointment($id)
    {

        $appointment = Appointment::with(["patient","opd_type","consultant","created_by"])->where(["is_active"=>1])
          ->where("id",$id)
            ->first();
        $data["data"] = $appointment;
        return view("PatientReports.print_doctor_slip", $data);
    }

    public function list_appointments()
    {
        $res = Appointment::with(["patient","opd_type","consultant","created_by"])->where(["is_active"=>1])
        ->when(request()->from_date,function ($q){
           // dd("here");
            $q->whereDate("appointment_date",">=",request()->from_date);
        })
       ->when(request()->to_date,function ($q){
                $q->whereDate("appointment_date","<=",request()->to_date);
            })
            ->when(request()->opd_type_id,function ($q){
                $q->where("opd_type_id",request()->opd_type_id);
            })
            ->when(request()->consultant_id,function ($q){
                $q->where("consultant_id",request()->consultant_id);
            })
        ;

        return DataTables::of($res)
            ->addColumn('actions', function($cert) {
                $details = json_encode($cert);
                //if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                    $html = '<a href="javascript:void(0)" data-details=\''.$details.'\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="'.$cert->name.'" data-id="'.$cert->id.'"><i class="tf-icons bx bx-pencil"></i></a>';
                    $html .= '<a target="_blank" href="'.route('pos.print_appointment',[$cert->id]).'" class="btn btn-success btn-icon btn-sm" data-id="'.$cert->id.'" type="submit"><i class="bx bx-printer tf-icons"></i></a>';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="'.$cert->id.'" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
                /*}else{
                    $html = "";
                }*/
                return $html;
            })
            ->addIndexColumn()
            ->rawColumns(["actions"])
            ->make(true);
    }

    public function save_appointments()
    {
        $data = request()->except(['_token', "id"]);



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


        $appointment = Appointment::where(["patient_id"=> $patient->id, "consultant_id"=>request()->consultant_id,"opd_type_id"=>request()->opd_type_id])
            ->whereDate('appointment_date',request()->regdate)
            ->first();
        if ($appointment) {
            // Appointment exists
            return response()->json([
                'status' => 'exist',
                'message' => 'An appointment already exists for this patient with the same consultant on the given date.'
            ], 400);
        }


        $consultant = Consultants::where(["id"=>request()->consultant_id])->first();
        $opd_type = OpdType::where(["id"=>request()->opd_type_id])->first();
        if(request()->opd_type_id == 1){
            $fees = $consultant->general_opd_fee;
            $hospital_share = $consultant->general_opd_fee;
            $consultant_share = 0;
        }

        if(request()->opd_type_id == 2){

            $fees = $consultant->consultant_opd_fee;
            $hospital_share = $consultant->hospital_share;
            $consultant_share = $consultant->consultant_share;
        }

        if(request()->opd_type_id == 3){
            $fees = 0;
            $hospital_share = 0;
            $consultant_share = 0;
        }

        //$patients = Appointment::orderBy("id","desc")->first();
        $number = $this->generateAppointmentNumber();

        $data = [
            "patient_id"    => $patient->id,
            "appointment_number"=> $number,
            "consultant_id" => request()->consultant_id,
            "opd_type_id"   => request()->opd_type_id,
            "fee"   => $fees,
            "hospital_share"   => $hospital_share,
            "consultant_share"   => $consultant_share,
            "appointment_date"   => request()->regdate." ".date("H:i:s"),
            "created_by"   => auth()->user()->id,

        ];

        $appointment = Appointment::create($data);
        return response()->json([
            "status"=> true,
            "appointment_id"=> $appointment->id,
            "message"=> "Record save successfully."
        ]);
    }

    public function update_appointment()
    {
        $consultant = Consultants::where(["id"=>request()->consultant_id])->first();
        $opd_type = OpdType::where(["id"=>request()->opd_type_id])->first();
        $fees = 0;
        $hospital_share = 0;
        $consultant_share = 0;
        if(request()->opd_type_id == 1){
            $fees = $opd_type->fees;
            $hospital_share = $opd_type->fees;
            $consultant_share = 0;
        }

        if(request()->opd_type_id == 2){
            $fees = $consultant->consultant_opd_fee;
            $hospital_share = $consultant->hospital_share;
            $consultant_share = $consultant->consultant_share;
        }

        if(request()->opd_type_id == 3){
            $fees = 0;
            $hospital_share = 0;
            $consultant_share = 0;
        }

        $data = [

            "consultant_id" => request()->consultant_id,
            "opd_type_id"   => request()->opd_type_id,
            "fee"   => $fees,
            "hospital_share"   => $hospital_share,
            "consultant_share"   => $consultant_share,
            "updated_by"   => auth()->user()->id,

        ];

       // dd($data,request()->id);
        $appointment = Appointment::where(["id"=>request()->id])->update($data);
        return response()->json([
            "status"=> true,
            "appointment_id"=>request()->id,
            "message"=> "Record Updated successfully."
        ]);
    }

    public function print_all_appointments($from_date,$to_date,$opd_type_id,$consultant_id)
    {

        $res = Appointment::with(["patient","opd_type","consultant","created_by"])->where(["is_active"=>1])
            ->when(($from_date && $from_date !='nill'),function ($q) use($from_date){
                // dd("here");
                $q->whereDate("appointment_date",">=",$from_date);
            })
            ->when(($to_date && $to_date !='nill'),function ($q) use($to_date){
                $q->whereDate("appointment_date","<=",$to_date);
            })
            ->when($opd_type_id,function ($q) use ($opd_type_id){
                $q->where("opd_type_id",$opd_type_id);
            })
            ->when($consultant_id,function ($q) use($consultant_id){
                $q->where("consultant_id",$consultant_id);
            })
        ->get();
        $data['from_date'] = ($from_date && $from_date !='nill') ? $from_date : "-";
        $data['to_date'] = ($to_date && $to_date !='nill') ? $to_date : "-";
        $data['data'] = $res;
        return view("appointments.reports.print_all_appointments", $data);
    }

    function generateAppointmentNumber() {
        $prefix = "A-";
        $appointment = Appointment::orderBy("id","desc")->first();
        $number = $appointment ? $appointment->id : 0;
        $number = ($number + 1);
        // Calculate the required length (based on the number of digits)
        $currentLength = strlen((string) $number); // Length of the current number
        $paddingLength = max(4, $currentLength + 1); // Start with 4 digits, increase dynamically

        // Pad the number to the calculated length
        $paddedNumber = str_pad($number, $paddingLength, '0', STR_PAD_LEFT);

        // Combine prefix and padded number
        return $prefix . $paddedNumber;
    }
}
