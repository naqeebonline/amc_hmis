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
use App\Models\Users;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AppointmentController extends Controller
{
    public function appointment()
    {
        $data['users'] = Users::whereHas('roles', function ($q) {
            $q->where('name', 'Super Admin')->orWhere('name', 'like', '%Receiption User%');
        })->get(["id", "name"]);
        $data["consultants"] = Consultants::where(["is_active" => 1])->get();
        $data["relations"] = Relation::get();
        $data["district"] = District::get();
        $data["locations"] = PatientLocation::get();
        $data["opd_type"] = OpdType::get();

        return view("appointments.appointments", $data);
    }

    public function print_appointment($id)
    {

        $appointment = Appointment::with(["patient", "opd_type", "consultant", "created_by", "location"])->where(["is_active" => 1])
            ->where("id", $id)
            ->first();
        $data["data"] = $appointment;

        return view("PatientReports.print_doctor_slip", $data);
    }

    public function print_e_prescription($id)
    {
        $appointment = Appointment::with(["patient", "opd_type", "consultant", "created_by", "location"])->where(["is_active" => 1])
            ->where("id", $id)
            ->first();
        $data["data"] = $appointment;

        // Fetch sale details with medications for this appointment
        $sale = \App\Models\Sale::where('appointment_id', $id)
            ->orderBy('SaleID', 'DESC')
            ->first();

        if ($sale) {
            $saleDetails = \App\Models\SaleDetails::with('product')
                ->where('SaleID', $sale->SaleID)
                ->get();
            $data["medications"] = $saleDetails;
            $data["sale"] = $sale;
        } else {
            $data["medications"] = collect();
            $data["sale"] = null;
        }

        // Fetch HX complaints for this appointment
        $hxComplaint = \App\Models\HxComplaint::where('appointment_id', $id)
            ->where('is_active', 1)
            ->first();
        $data["hx_complaint"] = $hxComplaint;

        // Fetch patient investigations for this appointment
        $investigations = \App\Models\Patient\PatientInvestigation::with('investigation')
            ->where('appointment_id', $id)
            ->get();
        $data["investigations"] = $investigations;

        return view("PatientReports.print_e_prescription", $data);
    }

    public function list_appointments()
    {

        $res = Appointment::with(["patient", "opd_type", "consultant", "created_by_user"])->where(["is_active" => 1])
            ->select('appointments.*')
            ->leftJoin('users', 'appointments.created_by', '=', 'users.id')
            ->when(request()->from_date, function ($q) {
                // dd("here");
                $q->whereDate("appointment_date", ">=", request()->from_date);
            })
            ->when(request()->to_date, function ($q) {
                $q->whereDate("appointment_date", "<=", request()->to_date);
            })
            ->when(request()->opd_type_id, function ($q) {
                $q->where("opd_type_id", request()->opd_type_id);
            })
            ->when(request()->consultant_id, function ($q) {
                $q->where("consultant_id", request()->consultant_id);
            })
            ->when(request()->created_by, function ($q) {
                $q->where("created_by", request()->created_by);
            })
            ->orderBy("id", "desc");

        return DataTables::of($res)
            ->addColumn('actions', function ($cert) {
                $details = json_encode($cert);
                //if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","District Super Admin"])){
                $html = "";
                $html .= '<a target="_blank" href="' . route('pos.print_appointment', [$cert->id]) . '" class="btn btn-success btn-icon btn-sm" data-id="' . $cert->id . '" type="submit" title="Print Appointment"><i class="bx bx-printer tf-icons"></i></a>&nbsp;&nbsp;';
                $html .= '<a target="_blank" href="' . route('pos.print_e_prescription', [$cert->id]) . '" class="btn btn-primary btn-icon btn-sm" data-id="' . $cert->id . '" type="submit" title="Print E-Prescription"><i class="bx bx-file tf-icons"></i></a>&nbsp;&nbsp;';
                if ((getUserRole() == 'Super Admin' || getUserRole() == 'Finance')) {
                    $html .= '<a href="javascript:void(0)" data-details=\'' . $details . '\' class="btn btn-warning btn-icon btn-sm edit_record" data-name="' . $cert->name . '" data-id="' . $cert->id . '"><i class="tf-icons bx bx-pencil"></i></a>&nbsp;&nbsp;';
                    $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="' . $cert->id . '" type="submit"><i class="bx bx-trash tf-icons"></i></button>&nbsp;&nbsp;';
                }



                /*}else{
                    $html = "";
                }*/
                return $html;
            })
            ->addColumn('created_by_user', function ($row) {
                return $row->created_by_user->name ?? '';
            })
            ->addIndexColumn()
            ->rawColumns(["actions", "created_by_user"])
            ->make(true);
    }

    public function save_appointments()
    {
        $data = request()->except(['_token', "id"]);

        if (request()->id == 0) {
            $number = (new PatientController())->generateMrNumber();
            $data['mr_no'] = $number;
            $data['regdate'] = request()->regdate . " " . date("H:i:s");
            $data['patient_type'] = "hospital_patient";
        }
        $patient =  Patient::updateOrCreate(
            ["id" => request()->id],
            $data
        );


        $appointment = Appointment::where(["patient_id" => $patient->id, "consultant_id" => request()->consultant_id, "opd_type_id" => request()->opd_type_id])
            ->whereDate('appointment_date', request()->regdate)
            ->where("is_active", 1)
            ->first();
        if ($appointment) {
            // Appointment exists
            return response()->json([
                'status' => 'exist',
                'message' => 'An appointment already exists for this patient with the same consultant on the given date.'
            ], 400);
        }


        $consultant = Consultants::where(["id" => request()->consultant_id])->first();
        $opd_type = OpdType::where(["id" => request()->opd_type_id])->first();
        /*if(request()->opd_type_id == 1){
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
        if(request()->opd_type_id == 4){
            $fees = $opd_type->fees;
            $hospital_share = 0;
            $consultant_share = 0;
        }*/
        switch (request()->opd_type_id) {
            case 1:
                $fees = $consultant->general_opd_fee;
                $hospital_share = $consultant->general_opd_hospital_share;
                $consultant_share = $consultant->general_opd_consultant_share;
                break;

            case 2:
                $fees = $consultant->consultant_opd_fee;
                $hospital_share = $consultant->hospital_share;
                $consultant_share = $consultant->consultant_share;
                break;
            case 4:
                $fees = $consultant->er_fee;
                $hospital_share = $consultant->er_hospital_share;
                $consultant_share = $consultant->er_consultant_share;
                break;

            default:
                $fees = $opd_type->fees;
                $hospital_share = $opd_type->fees;
                $consultant_share = 0;
                break;
        }

        //$patients = Appointment::orderBy("id","desc")->first();
        $number = $this->generateAppointmentNumber();

        $data = [
            "patient_id"    => $patient->id,
            "appointment_number" => $number,
            "consultant_id" => request()->consultant_id,
            "opd_type_id"   => request()->opd_type_id,
            "fee"   => $fees,
            "hospital_share"   => $hospital_share,
            "consultant_share"   => $consultant_share,
            "appointment_date"   => request()->regdate . " " . date("H:i:s"),
            "created_by"   => auth()->user()->id,

        ];

        $appointment = Appointment::create($data);
        return response()->json([
            "status" => true,
            "appointment_id" => $appointment->id,
            "message" => "Record save successfully."
        ]);
    }

    public function update_appointment()
    {
        $consultant = Consultants::where(["id" => request()->consultant_id])->first();
        $opd_type = OpdType::where(["id" => request()->opd_type_id])->first();
        $fees = 0;
        $hospital_share = 0;
        $consultant_share = 0;
        /*if(request()->opd_type_id == 1){
            $fees = $opd_type->fees;
            $hospital_share = $opd_type->fees;
            $consultant_share = 0;
        } else if(request()->opd_type_id == 2){
            $fees = $consultant->consultant_opd_fee;
            $hospital_share = $consultant->hospital_share;
            $consultant_share = $consultant->consultant_share;
        } else if(request()->opd_type_id == 3){
            $fees = 0;
            $hospital_share = 0;
            $consultant_share = 0;
        } else(request()->opd_type_id == 4){
            $fees = $opd_type->fees;
            $hospital_share = 0;
            $consultant_share = 0;
        }*/
        switch (request()->opd_type_id) {
            case 1:
                $fees = $consultant->general_opd_fee;
                $hospital_share = $consultant->general_opd_hospital_share;
                $consultant_share = $consultant->general_opd_consultant_share;
                break;

            case 2:
                $fees = $consultant->consultant_opd_fee;
                $hospital_share = $consultant->hospital_share;
                $consultant_share = $consultant->consultant_share;
                break;
            case 4:
                $fees = $consultant->er_fee;
                $hospital_share = $consultant->er_hospital_share;
                $consultant_share = $consultant->er_consultant_share;
                break;
            default:
                $fees = $opd_type->fees;
                $hospital_share = $opd_type->fees;
                $consultant_share = 0;
                break;
        }

        $data = [

            "consultant_id" => request()->consultant_id,
            "opd_type_id"   => request()->opd_type_id,
            "fee"   => $fees,
            "hospital_share"   => $hospital_share,
            "consultant_share"   => $consultant_share,
            "updated_by"   => auth()->user()->id,
            "is_sync"   => 0,

        ];

        // dd($data,request()->id);
        $appointment = Appointment::where(["id" => request()->id])->update($data);
        return response()->json([
            "status" => true,
            "appointment_id" => request()->id,
            "message" => "Record Updated successfully."
        ]);
    }

    public function print_all_appointments($from_date, $to_date, $opd_type_id, $consultant_id, $user_id)
    {

        $res = Appointment::with(["patient", "opd_type", "consultant", "created_by_user"])->where(["is_active" => 1])
            ->when(($from_date && $from_date != 'nill'), function ($q) use ($from_date) {
                // dd("here");
                $q->whereDate("appointment_date", ">=", $from_date);
            })
            ->when(($to_date && $to_date != 'nill'), function ($q) use ($to_date) {
                $q->whereDate("appointment_date", "<=", $to_date);
            })
            ->when($opd_type_id, function ($q) use ($opd_type_id) {
                $q->where("opd_type_id", $opd_type_id);
            })
            ->when($consultant_id, function ($q) use ($consultant_id) {
                $q->where("consultant_id", $consultant_id);
            })
            ->when($user_id, function ($q) use ($user_id) {
                $q->where("created_by", $user_id);
            })
            ->get();

        $data['from_date'] = ($from_date && $from_date != 'nill') ? $from_date : "-";
        $data['to_date'] = ($to_date && $to_date != 'nill') ? $to_date : "-";
        $data['data'] = $res;
        return view("appointments.reports.print_all_appointments", $data);
    }

    function generateAppointmentNumber()
    {
        $year = date('y');  // Last 2 digits of the year, e.g., "25"
        $month = date('m'); // 2-digit month, e.g., "07"

        // Get count of appointments for the current year and month
        $count = Appointment::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();

        $sequence = $count + 1;

        // Sequence should start at 2 digits and grow as needed
        $minLength = 2;
        $dynamicLength = max($minLength, strlen((string)$sequence));
        $paddedSequence = str_pad($sequence, $dynamicLength, '0', STR_PAD_LEFT);

        // Combine year, month, and padded sequence
        return $year . $month . $paddedSequence;
    }
}
