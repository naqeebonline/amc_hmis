<?php

namespace App\Http\Controllers\PatientController;

use App\Http\Controllers\Controller;
use App\Models\Configuration\Relation;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientLocation;
use Illuminate\Support\Carbon;
use Modules\Settings\Entities\District;
use Yajra\DataTables\Facades\DataTables;

class PatientController extends Controller
{
   public function patient_regisration()
   {

      $data["relations"] = Relation::get();
      $data["district"] = District::get();
      $data["locations"] = PatientLocation::get();

      return view("patients.patient_registration", $data);
   }

   public function store_patient_regisration() {
       $data = request()->except(['_token', "id"]);
       $patients = Patient::orderBy("id","desc")->first();
       $number = "AMC-".($patients->id) + 1;
       if(request()->id == 0){
           $data['mr_no'] = $number;
           $data['regdate'] = request()->regdate." ".date("H:i:s");
           $data['patient_type'] = "sehat_card";
       }
      Patient::updateOrCreate(
         ["id" => request()->id],
         $data
      );
       
      return response()->json([
         "status"=> true,
         "message"=> "Record save successfully."
      ]);
      
   }

   public function list_patient(){
      $patients = Patient::where("is_active", 1)->with("location", "district");
      return DataTables::of($patients)
         ->addColumn("actions", function ($patient) {
            return '<a href="javascript:void(0)"  data-details=\'' . $patient . '\'  class="btn btn-sm btn-warning edit_record"><i class="tf-icons bx bx-pencil"></i></a> 
                        <button data-id="' . $patient->id . '" class="btn btn-danger btn-sm delete_record"><i class="bx bx-trash tf-icons"></i></button>';
         })
         ->rawColumns(["actions"])
         ->make(true);
   }




   public function patient_admission(){

      $patients = Patient::get();
      return view("patients.patient_admission",compact("patients"));
   }

   public function get_patient_by_cnic(){

       $patient = [];
      if(request()->mr_number){
          $patient = Patient::where("mr_no", request()->mr_number)->get();
      }


      if(count($patient) > 0){
         return response()->json([
            "status"=> true,
            "data"=> $patient
         ]);

      }else if(request()->contact_no){
          $patient = Patient::where("contact_no", request()->contact_no)->get();

          return response()->json([
              "status"=> true,
              "data"=> $patient
          ]);
      }else{
          return response()->json([
              "status"=> false,
              "data"=> []
          ]);
      }
   }

    function generateMrNumber() {
        $prefix = "AMC-";
        $appointment = Patient::orderBy("id","desc")->first();
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
