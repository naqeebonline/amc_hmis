<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PatientController\PatientAdmissionController;
use App\Models\Appointments\Appointment;
use App\Models\Customer;
use App\Models\GrnDetails;
use App\Models\Market;
use App\Models\Patient\InPatientAdmission;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientAdmission;
use App\Models\PharmacyRetrun;
use App\Models\PharmacyTransfer;
use App\Models\PharmacyTransferDetails;
use App\Models\Product;
use App\Models\ReceiveablesDetail;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\SalePayment;
use App\Models\Store;
use App\Models\TempSale;
use App\Models\TempSaleDetails;
use App\Models\WardRequest;
use App\Models\WardRequestDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SaleController extends Controller
{
    public function sehat_card_pharmacy_sale()
    {
        $store = Store::whereId(env('SEHAT_CARD_PHARMACY_STORE_ID'))->first();

        session(['store_id' => 1]);
        session(['store_name' => "Sehat Card Pharmacy Sale"]);
        session(['is_free' => 1]);
        /*if($store){
            session(['store_id' => $store->id]);
            session(['store_name' => $store->store_name]);
            session(['is_free' => $store->use_purchase_price_as_sale_price]);
        }*/


        $type = $_GET['type'] ?? "";
        $data['type'] = $type;
        $data["ward_request"] = $_GET["ward_request"] ?? "";
        $data['patient_id'] = "";
        $data['list_products'] = [];

        if ($data['ward_request']) {
            $ward_request = WardRequest::whereId($data['ward_request'])->first();
            $data['patient_id'] = $ward_request->patient_id;
            $ward_request_details = WardRequestDetails::with(['products'])->where(["wr_id" => $ward_request->id])->get();
            $list_products = [];
            foreach ($ward_request_details as $key => $value) {
                $avliable_qty = (new StockController())->avaliableQuantity($value->product_id);
                $res = [
                    "ProductID" => $value->product_id,
                    "Product" => $value->products->ProductName,
                    "Name" => $value->products->ProductName,
                    "UnitePrice" => $value->products->PurchasePrice,
                    "Quantity" => $value->quantity,
                    "Total" => ($value->quantity) * ($value->products->PurchasePrice),
                    "AvailableQuantity" => $avliable_qty,
                    "taxAmount" => 0,
                    "taxPercentage" => 0,
                    "currentAvailableQuantity" => $avliable_qty,
                    "dose_type" => '-',
                ];
                array_push($list_products, $res);
            }
            $data['list_products'] = $list_products;
        }
        $data["title"] = "Add New Sale";
        $data['products'] = Product::orderBy("ProductName", "ASC")
            ->when($type == 'Home', function ($query) {
                return $query->where("item_form_id", "!=", 16);
            })
            /* ->when(session('store_id'),function ($q){
                $q->where('store_id',env('SEHAT_CARD_PHARMACY_STORE_ID'));
            })*/
            ->where('store_id', env('SEHAT_CARD_PHARMACY_STORE_ID'))
            ->get();
        foreach ($data['products'] as $key => $value) {
            $value->avaliable_qty = GrnDetails::where(["ProductID" => $value->ProductID])->sum('RemainingQuantity');
        }
        //$data['customers'] = Customer::where(["Type" => 2])->orderBy("Name", "ASC")->get();
        $data['admitted_patients'] = PatientAdmission::where(["admission_status" => "Admit", "is_active" => 1, "patient_type" => "sehat_card"])
            //->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->with(["patient"])->get();

        $data['invoiceNo'] = $this->returnInvoiceNumber();

        return view("sale.new_sale", $data);
    }


    public function retail_pharmacy_sale()
    {
        $store = Store::where("id", "!=", env('SEHAT_CARD_PHARMACY_STORE_ID'))->first();
        session(['store_id' => 2]);
        session(['store_name' => "Retail Pharmacy Sale"]);
        session(['is_free' => 0]);
        /*if($store){
            session(['store_id' => $store->id]);
            session(['store_name' => $store->store_name]);
            session(['is_free' => $store->use_purchase_price_as_sale_price]);
        }*/

        $type = $_GET['type'] ?? "Home";
        $data['type'] = $type;
        $data["ward_request"] = $_GET["ward_request"] ?? "";
        $data['patient_id'] = "";
        $data['list_products'] = [];

        $data['appointments'] = Appointment::where('is_active', 1)
            ->where('created_at', '>=', Carbon::now()->subDays(2)) // last 5 days
            ->with(['patient'])
            ->orderBy('appointment_date', 'desc')
            ->get();


        $data["title"] = "Retail Sale";
        /*$data['products'] = Product::orderBy("ProductName", "ASC")
             ->when(session('store_id'),function ($q){
                 $q->where('store_id',session('store_id'));
             })
            ->get();*/
        //Cache::forget('products_store_2');
        $data["products"] =  Product::with('generic_name')
            ->when(session('store_id'), function ($q) {
                $q->where('store_id', session('store_id'));
            })
            ->orderBy("ProductName", "ASC")
            ->where("IsActive", 1)
            ->where("ProductName", "!=", '')
            ->where("pack_size", "!=", 0)
            ->where("pack_price", "!=", 0)

            ->get();
        foreach ($data['products'] as $key => $value) {
            $value->avaliable_qty = GrnDetails::where(["ProductID" => $value->ProductID])->sum('RemainingQuantity');
        }
        //$data['customers'] = Customer::where(["Type" => 2])->orderBy("Name", "ASC")->get();
        $data['admitted_patients'] = Patient::where("patient_type", "walking_customer")->get();

        $data['invoiceNo'] = $this->returnInvoiceNumber();

        return view("sale.retial_sale", $data);
    }

    public function pharmacy_transfer()
    {
        $store = Store::where("id", "!=", env('SEHAT_CARD_PHARMACY_STORE_ID'))->first();
        session(['store_id' => 2]);
        session(['store_name' => "Retail Pharmacy Sale"]);
        session(['is_free' => 0]);
        /*if($store){
            session(['store_id' => $store->id]);
            session(['store_name' => $store->store_name]);
            session(['is_free' => $store->use_purchase_price_as_sale_price]);
        }*/

        $type = $_GET['type'] ?? "Home";
        $data['type'] = $type;
        $data["ward_request"] = $_GET["ward_request"] ?? "";
        $data['patient_id'] = "";
        $data['list_products'] = [];

        $data['appointments'] = [];


        $data["title"] = "Retail Sale";
        /*$data['products'] = Product::orderBy("ProductName", "ASC")
             ->when(session('store_id'),function ($q){
                 $q->where('store_id',session('store_id'));
             })
            ->get();*/
        //Cache::forget('products_store_2');
        $data["products"] =  Product::with('generic_name')
            ->when(session('store_id'), function ($q) {
                $q->where('store_id', session('store_id'));
            })
            ->orderBy("ProductName", "ASC")
            ->where("IsActive", 1)
            ->where("ProductName", "!=", '')
            ->where("pack_size", "!=", 0)
            ->where("pack_price", "!=", 0)

            ->get();
        foreach ($data['products'] as $key => $value) {
            $value->avaliable_qty = GrnDetails::where(["ProductID" => $value->ProductID])->sum('RemainingQuantity');
        }
        //$data['customers'] = Customer::where(["Type" => 2])->orderBy("Name", "ASC")->get();
        $data['admitted_patients'] = Patient::where("patient_type", "walking_customer")->get();

        $data['invoiceNo'] = $this->returnTransferInvoiceNumber();

        return view("sale.pharmacy_transfer", $data);
    }

    public function search_appointment(Request $request)
    {
        $term = $request->get('q');

        $appointments = Appointment::where('is_active', 1)
            ->when($term, function ($q) use ($term) {
                $q->whereHas('patient', function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('mr_no', 'like', "%{$term}%");
                })
                    ->orWhere('appointment_number', 'like', "%{$term}%");
            })
            ->with('patient')
            ->orderBy('appointment_date', 'desc')
            ->limit(20)
            ->get();

        return response()->json($appointments->map(function ($a) {
            return [
                'id'   => $a->id,
                'text' => $a->patient->name .
                    " | Appointment# " . $a->appointment_number .
                    " | MR#: " . $a->patient->mr_no,
            ];
        }));
    }

    public function in_patient_pharmacy_sale()
    {
        $store = Store::where("id", "!=", env('SEHAT_CARD_PHARMACY_STORE_ID'))->first();
        /*if($store){
            session(['store_id' => $store->id]);
            session(['store_name' => $store->store_name]);
            session(['is_free' => $store->use_purchase_price_as_sale_price]);
        }*/
        session(['store_id' => 2]);
        session(['store_name' => "In Patient Retail Pharmacy Sale"]);
        session(['is_free' => 0]);

        $type = $_GET['type'] ?? "Ward";
        $data['type'] = $type;
        $data["ward_request"] = $_GET["ward_request"] ?? "";
        $data['patient_id'] = "";
        $data['list_products'] = [];

        $data["title"] = "Add New Sale";
        $data['products'] = Product::orderBy("ProductName", "ASC")
            ->when(session('store_id'), function ($q) {
                $q->where('store_id', session('store_id'));
            })
            ->orderBy("ProductName", "ASC")
            ->where("IsActive", 1)
            ->where("ProductName", "!=", '')
            ->where("pack_size", "!=", 0)
            ->where("pack_price", "!=", 0)
            ->get();
        foreach ($data['products'] as $key => $value) {
            $value->avaliable_qty = GrnDetails::where(["ProductID" => $value->ProductID])->sum('RemainingQuantity');
        }
        //$data['customers'] = Customer::where(["Type" => 2])->orderBy("Name", "ASC")->get();
        $data['admitted_patients'] = InPatientAdmission::where(["admission_status" => "Admit", "is_active" => 1])
            ->where("patient_type", "!=", "sehat_card")
            ->where("patient_type", "!=", "configuration")
            // ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->with(["patient"])->get();

        $data['invoiceNo'] = $this->returnInvoiceNumber();

        return view("sale.in_patient_pharmacy_sale", $data);
    }

    // public function ware_house_stock()
    // {

    //     $data["title"] = "Add New Supplier or Customer";
    //     $data['market'] = Market::where(["IsActive" => 1])->get()->sortBy('Name');

    //     return view("configuration.sup_cus_registration", $data);
    // }

    // public function list_customer()
    // {
    //     $res = Customer::with("market")->where(["IsActive" => 1]);

    //     return DataTables::of($res)
    //         ->addColumn('action', function ($cert) {
    //             $details = json_encode($cert);
    //             if (in_array(auth()->user()->roles->pluck('name')[0], ["Super Admin", "District Super Admin"])) {
    //                 $html = '<a href="javascript:void(0)" class="btn btn-warning btn-icon btn-sm edit_record" data-details=\'' . $details . '\'  data-id="' . $cert->SCID . '"><i class="tf-icons bx bx-pencil"></i></a>';
    //                 $html .= '<button class="btn btn-danger btn-icon btn-sm delete_record" data-id="' . $cert->SCID . '" type="submit"><i class="bx bx-trash tf-icons"></i></button>';
    //             } else {
    //                 $html = "";
    //             }

    //             return $html;
    //         })
    //         ->addColumn('customType', function ($cert) {
    //             $cert->customType;
    //             if ($cert->Type == 2) {
    //                 return $cert->customType = "Customer";
    //             } else {
    //                 return $cert->customType = "Supplier";
    //             }
    //         })
    //         ->rawColumns(["customType", "action"])
    //         ->make(true);
    // }

    // public function save_customer()
    // {


    //     Customer::updateOrCreate(
    //         ["SCID" => request()->id],
    //         request()->except(["id", "_token"])
    //     );
    //     return ["status" => true, "message" => "Record saved successfully"];
    // }


    public function save_sale()
    {

        /*return response()->json([
            "data" => $request->all()
        ]);*/
        $patient_id = request()->patient_id;
        $admission_id = request()->patient_admission_id ?? 0;
        $customer = Patient::where(["id" => $patient_id])->first();
        //-------------------------------------------//
        $Invoice = $this->returnInvoiceNumber();
        $SupplierID = $patient_id;
        $Freight = 0;
        $PDate = date("Y-m-d", strtotime(request()->bill_date));
        $Description = request()->BillDiscription;
        $medicine_type = request()->medicine_type;
        $bill_description = request()->BillDiscription;
        $discount_percentage = request()->discount_percentage ?? 0;
        $Discount = request()->discount_amount ?? 0;
        $demage = 0;
        $ReceivedAmount = request()->ReceivedAmount;
        $userID = auth()->user()->id;
        $totalTax = 0;
        $TotalSale = request()->BillAmount;
        $SalemanID = 0;
        $Commesion = 0;
        if ($customer) {
            $CustomerName = $customer->name . " - " . $customer->mr_no;
        } else {
            $CustomerName = "Walking Customer";
            $patient_id = 0;
        }

        /*foreach(request()->ProductList as $row){
            $totalTax = ($totalTax) + ($row['taxAmount']);
        }*/
        $total = ($TotalSale) + $totalTax;

        $SaleArray = array(
            'SCID'     => (session('store_id') == env('SEHAT_CARD_PHARMACY_STORE_ID')) ? 1 : 2, // 1 sehat card user,2 walking customer of retail store , table use sup_cus_details
            'store_id'     => session('store_id'), // sehat card user
            'wr_id'     => request()->ward_request_id ?? 0, // sehat card user
            'patient_id'   => $patient_id,
            'admission_id'   => $admission_id,
            'InvoiceNo' => $Invoice,
            'medicine_type' => $medicine_type,
            'Date'  => $PDate,
            'Description'   =>  $CustomerName,
            'TotalSale'     => $total,
            'received_amount'     => $ReceivedAmount,
            'Discount'     =>  $Discount,
            'discount_percentage'     =>  $discount_percentage,
            'sale_descriptions' => $bill_description,
            'CreatedBy'     => $userID,
            'CreatedAt'     => date('Y-m-d')
        );
        if ($SalemanID != '') {
            $SaleArray['SalemanCommesion'] = $Commesion;
            $SaleArray['SalemanID'] = $SalemanID;
        }

        $SaleArray['bill_details'] = json_encode(request()->ProductList);
        $sale = Sale::create($SaleArray);
        $last_id = $sale->SaleID;





        $is_free = session('is_free');
        foreach (request()->ProductList as $row) {
            $soldQuantity = $row['Quantity'];
            $result = GrnDetails::where(["ProductID" => $row['ProductID'], "ProductStatus" => 1])->get();
            $Detail_array = array(
                'store_id'   => session('store_id'),
                'SaleID'   => $last_id,
                'patient_id'   => $patient_id,
                'admission_id'   => $admission_id,
                'ProductID' => $row['ProductID'],
                'UnitePrice'  => $row['UnitePrice'],
                'taxPercentage'  => $row['taxPercentage'],
                'dose_type'  => $row['dose_type'],
            );
            $applyTax = $row['taxPercentage'] / 100;
            foreach ($result as $key => $value) {
                if ($soldQuantity <= $value->RemainingQuantity && $soldQuantity != 0) {
                    //echo "yes";
                    $total = ($soldQuantity) * ($row['UnitePrice']);
                    $taxAmount = ($total) * $applyTax;

                    if ($is_free) {  // if free then sale price will be same as purchase price
                        $Detail_array['UnitePrice'] = $value->UnitPrice;
                    }

                    $Detail_array['PurchasePrice'] = $value->UnitPrice;
                    $Detail_array['Quantity'] = $soldQuantity;
                    $Detail_array['GDID'] = $value->GDID;
                    $Detail_array['taxAmount'] = $taxAmount;
                    $remainingQuantity = $value->RemainingQuantity - $soldQuantity;
                    SaleDetails::create($Detail_array);
                    GrnDetails::where(["GDID" => $value->GDID])->update(['RemainingQuantity' => $remainingQuantity, 'SoldQuantity' => ($value->SoldQuantity + $soldQuantity)]);
                    if ($remainingQuantity == 0) {
                        GrnDetails::where(['GDID' => $value->GDID])->update(['ProductStatus' => 0]);
                    }
                    $soldQuantity = 0;
                } else {
                    if ($soldQuantity > $value->RemainingQuantity && $soldQuantity != 0) {
                        $total = ($value->RemainingQuantity) * ($row['UnitePrice']);
                        $taxAmount = ($total) * $applyTax;

                        if ($is_free) { // if free then sale price will be same as purchase price
                            $Detail_array['UnitePrice'] = $value->UnitPrice;
                        }

                        $Detail_array['PurchasePrice'] = $value->UnitPrice;
                        $Detail_array['Quantity'] = $value->RemainingQuantity;
                        $Detail_array['GDID'] = $value->GDID;
                        $Detail_array['taxAmount'] = $taxAmount;
                        $soldQuantity = ($soldQuantity) - ($value->RemainingQuantity);
                        //echo $soldQuantity;
                        SaleDetails::create($Detail_array);
                        GrnDetails::where(['GDID' => $value->GDID])->update(['RemainingQuantity' => 0, 'SoldQuantity' => ($value->SoldQuantity + $value->RemainingQuantity), 'ProductStatus' => 0]);
                    }
                }
            } //.... end of foreach
            //---- if stock is zero then also enter products in sale   -----//
            /*if($soldQuantity > 0){
                            $product = GrnDetails::where("UnitPrice",">",0)->where(["ProductID"=>$row['ProductID']])->orderBy("GDID","DESC")->first();

                            // dd($product,$row['ProductID']);
                            $total = ($soldQuantity) * ($row['UnitePrice']);
                            $taxAmount = ($total) * $applyTax;

                            $Detail_array['PurchasePrice']= $product->UnitPrice;
                            $Detail_array['Quantity']=$soldQuantity;
                            $Detail_array['GDID']=$product->GDID;
                            $Detail_array['taxAmount']=$taxAmount;
                            $remainingQuantity = ($product->RemainingQuantity) - ($soldQuantity);

                            SaleDetails::create($Detail_array);
                            GrnDetails::where(['GDID'=>$value->GDID])->update(['RemainingQuantity'=>$remainingQuantity]);
                        }*/
            //--------- end if stock is zero   ----------//
        } //------------ end of main foreach   -----------//

        if (request()->ward_request_id) {
            WardRequest::whereId(request()->ward_request_id)->update(["issued_by" => auth()->user()->id, "issued_at" => date("Y-m-d H:i:s"), "status" => 1]);
        }

        if (session('store_id') == 1) {
            (new PatientAdmissionController())->updateAdmissionDetails($admission_id);
        }


        return ["status" => true, "message" => "Sale Completed Successfully", "id" => $last_id];
    }

    public function save_retail_sale()
    {
        $TotalSale = 0;

        foreach (request()->ProductList as $row) {
            $TotalSale = ($TotalSale) + (($row['Quantity'] * ($row['UnitePrice'])));
        }

        $patient_id = request()->patient_id;
        $invoice_discount = request()->invoice_discount;
        $admission_id = request()->patient_admission_id ?? 0;
        $customer = Patient::where(["id" => $patient_id])->first();
        $ReceivedAmountFromCustomer = 0;
        if (request()->ReceivedAmountFromCustomer) {
            $ReceivedAmountFromCustomer = request()->ReceivedAmountFromCustomer;
        }
        //-------------------------------------------//
        $Invoice = $this->returnInvoiceNumber();
        $SupplierID = $patient_id;
        $Freight = 0;
        $PDate = date("Y-m-d", strtotime(request()->bill_date));
        $Description = request()->BillDiscription;
        $appointment_id = request()->appointment_id;
        $medicine_type = request()->medicine_type;
        $bill_description = request()->BillDiscription;
        $discount_percentage = request()->discount_percentage ?? 0;
        $Discount = request()->discount_amount ?? 0;
        $demage = 0;
        $ReceivedAmount = request()->ReceivedAmount;

        $userID = auth()->user()->id;
        $totalTax = 0;

        $SalemanID = 0;
        $Commesion = 0;
        if ($customer) {
            $CustomerName = $customer->name . " - " . $customer->mr_no;
        } else {
            $CustomerName = "Walking Customer";
            $patient_id = 0;
        }

        if ($appointment_id) {
            $app = Appointment::where('is_active', 1)
                ->with(['patient'])
                ->where("id", $appointment_id)
                ->first();
            $CustomerName = $app->patient->name ?? "";
            $patient_id = $app->patient_id ?? 0;
            $admission_id = 0;
        }

        /*foreach(request()->ProductList as $row){
            $totalTax = ($totalTax) + ($row['taxAmount']);
        }*/
        $total = ($TotalSale) + $totalTax;

        $SaleArray = array(
            'SCID'     => (session('store_id') == env('SEHAT_CARD_PHARMACY_STORE_ID')) ? 1 : 2, // 1 sehat card user,2 walking customer of retail store , table use sup_cus_details
            'store_id'     => session('store_id'), // sehat card user
            'wr_id'     => request()->ward_request_id ?? 0, // sehat card user
            'ReceivedAmountFromCustomer'   => $ReceivedAmountFromCustomer,
            'patient_id'   => $patient_id,
            'admission_id'   => $admission_id,
            'InvoiceNo' => $Invoice,
            'appointment_id' => $appointment_id,
            'medicine_type' => $medicine_type,
            'Date'  => $PDate,
            'Description'   =>  $CustomerName,
            'TotalSale'     => $total,
            'received_amount'     => $ReceivedAmount,
            'Discount'     =>  $Discount,
            'invoice_discount'     =>  $invoice_discount,
            'discount_percentage'     =>  $discount_percentage,
            'sale_descriptions' => $bill_description,
            'CreatedBy'     => $userID,
            'CreatedAt'     => date('Y-m-d')
        );
        if ($SalemanID != '') {
            $SaleArray['SalemanCommesion'] = $Commesion;
            $SaleArray['SalemanID'] = $SalemanID;
        }

        $SaleArray['bill_details'] = json_encode(request()->ProductList);

        try {
            DB::beginTransaction();

            $sale = Sale::create($SaleArray);
            $last_id = $sale->SaleID;
            $SaleArray['SaleID'] = $last_id;
            $temp_sale = TempSale::create($SaleArray);
            $item_details = [];
            foreach (request()->ProductList as $row) {
                $TotalSale = ($TotalSale) + (($row['Quantity'] * ($row['UnitePrice'])));

                // Get product to validate discount
                $product = Product::find($row['ProductID']);
                $allowPercentage = $product->allow_percentage ?? null;
                $requestedDiscount = $row['discount_percentage'] ?? 0;
                $finalDiscountPercentage = 0;

                if ($allowPercentage === 0) {
                    // If allow_percentage is zero, don't apply any discount
                    $finalDiscountPercentage = 0;
                } else if ($allowPercentage > 0) {
                    // If product has allow_percentage limit
                    $finalDiscountPercentage = ($requestedDiscount > $allowPercentage) ? $allowPercentage : $requestedDiscount;
                } else {
                    // No allow_percentage field set (null) - apply user's discount
                    $finalDiscountPercentage = $requestedDiscount;
                }

                $discountAmount = ($row['Quantity'] * $row['UnitePrice'] * $finalDiscountPercentage) / 100;

                $item_details[] = array(
                    'store_id'   => session('store_id'),
                    'SaleID'   => $last_id, //$sale->SaleID,
                    'temp_sale_id'   => $temp_sale->id,
                    'patient_id'   => $patient_id,
                    'admission_id'   => $admission_id,
                    'ProductID' => $row['ProductID'],
                    'UnitePrice'  => $row['UnitePrice'],
                    'taxPercentage'  => $row['taxPercentage'],
                    'dose_type'  => $row['dose_type'],
                    'Quantity'  => $row['Quantity'],
                    'discount_percentage' => $finalDiscountPercentage,
                    'discount_percentage_amount' => $discountAmount,
                    'discount_percentage_amount' => $discountAmount,
                );
            }

            TempSaleDetails::insert($item_details);

            DB::commit(); // ✅ commit if all good
        } catch (\Exception $e) {
            DB::rollBack(); // ❌ rollback on error
            throw $e;       // optional: rethrow for logging
        }


        if ($ReceivedAmount >= 1) {
            SalePayment::create(["sale_id" => $last_id, "patient_id" => $patient_id, "admission_id" => $admission_id, "amount" => $ReceivedAmount, "created_by" => auth()->user()->id, "created_at" => date("Y-m-d H:i:s")]);
        }



        $is_free = session('is_free');
        foreach (request()->ProductList as $row) {
            $soldQuantity = $row['Quantity'];
            $result = GrnDetails::where(["ProductID" => $row['ProductID'], "ProductStatus" => 1])->get();

            // Get product to check allow_percentage
            $product = Product::find($row['ProductID']);
            $allowPercentage = $product->allow_percentage ?? 0;

            // Apply discount based on allow_percentage logic
            $requestedDiscount = $row['discount_percentage'] ?? 0;
            $finalDiscountPercentage = 0;

            if ($allowPercentage === 0) {
                // If allow_percentage is zero, don't apply any discount
                $finalDiscountPercentage = 0;
            } else if ($allowPercentage > 0) {
                // If product has allow_percentage limit
                if ($requestedDiscount <= $allowPercentage) {
                    // User's discount is within limit - apply user's discount
                    $finalDiscountPercentage = $requestedDiscount;
                } else {
                    // User's discount exceeds limit - apply product's allow_percentage
                    $finalDiscountPercentage = $allowPercentage;
                }
            } else {
                // No allow_percentage field set (null) - apply user's discount
                $finalDiscountPercentage = $requestedDiscount;
            }

            $discountAmount = ($row['Quantity'] * $row['UnitePrice'] * $finalDiscountPercentage) / 100;

            $Detail_array = array(
                'store_id'   => session('store_id'),
                'SaleID'   => $last_id,
                'patient_id'   => $patient_id,
                'admission_id'   => $admission_id,
                'ProductID' => $row['ProductID'],
                'UnitePrice'  => $row['UnitePrice'],
                'taxPercentage'  => $row['taxPercentage'],
                'dose_type'  => $row['dose_type'],
                'discount_percentage' => $finalDiscountPercentage,
                'discount_percentage_amount' => $discountAmount,
            );
            $applyTax = $row['taxPercentage'] / 100;
            foreach ($result as $key => $value) {
                if ($soldQuantity <= $value->RemainingQuantity && $soldQuantity != 0) {
                    //echo "yes";
                    $total = ($soldQuantity) * ($row['UnitePrice']);
                    $taxAmount = ($total) * $applyTax;
                    $Detail_array['PurchasePrice'] = $value->UnitPrice;
                    $Detail_array['Quantity'] = $soldQuantity;
                    $Detail_array['GDID'] = $value->GDID;
                    $Detail_array['taxAmount'] = $taxAmount;
                    $remainingQuantity = $value->RemainingQuantity - $soldQuantity;
                    SaleDetails::create($Detail_array);
                    GrnDetails::where(["GDID" => $value->GDID])->update(['RemainingQuantity' => $remainingQuantity, 'SoldQuantity' => ($value->SoldQuantity + $soldQuantity)]);
                    if ($remainingQuantity == 0) {
                        GrnDetails::where(['GDID' => $value->GDID])->update(['ProductStatus' => 0]);
                    }
                    $soldQuantity = 0;
                } else {
                    if ($soldQuantity > $value->RemainingQuantity && $soldQuantity != 0) {
                        $total = ($value->RemainingQuantity) * ($row['UnitePrice']);
                        $taxAmount = ($total) * $applyTax;

                        if ($is_free) { // if free then sale price will be same as purchase price
                            $Detail_array['UnitePrice'] = $value->UnitPrice;
                        }

                        $Detail_array['PurchasePrice'] = $value->UnitPrice;
                        $Detail_array['Quantity'] = $value->RemainingQuantity;
                        $Detail_array['GDID'] = $value->GDID;
                        $Detail_array['taxAmount'] = $taxAmount;
                        $soldQuantity = ($soldQuantity) - ($value->RemainingQuantity);
                        //echo $soldQuantity;
                        SaleDetails::create($Detail_array);
                        GrnDetails::where(['GDID' => $value->GDID])->update(['RemainingQuantity' => 0, 'SoldQuantity' => ($value->SoldQuantity + $value->RemainingQuantity), 'ProductStatus' => 0]);
                    }
                }
            } //.... end of foreach
            //---- if stock is zero then also enter products in sale   -----//
            /*if($soldQuantity > 0){
                $product = GrnDetails::where("UnitPrice",">",0)->where(["ProductID"=>$row['ProductID']])->orderBy("GDID","DESC")->first();

                // dd($product,$row['ProductID']);
                $total = ($soldQuantity) * ($row['UnitePrice']);
                $taxAmount = ($total) * $applyTax;

                $Detail_array['PurchasePrice']= $product->UnitPrice;
                $Detail_array['Quantity']=$soldQuantity;
                $Detail_array['GDID']=$product->GDID;
                $Detail_array['taxAmount']=$taxAmount;
                $remainingQuantity = ($product->RemainingQuantity) - ($soldQuantity);

                SaleDetails::create($Detail_array);
                GrnDetails::where(['GDID'=>$value->GDID])->update(['RemainingQuantity'=>$remainingQuantity]);
            }*/
            //--------- end if stock is zero   ----------//
        } //------------ end of main foreach   -----------//

        if (request()->ward_request_id) {
            WardRequest::whereId(request()->ward_request_id)->update(["issued_by" => auth()->user()->id, "issued_at" => date("Y-m-d H:i:s"), "status" => 1]);
        }

        if (session('store_id') == 1) {
            (new PatientAdmissionController())->updateAdmissionDetails($admission_id);
        }
        sleep(1);

        return ["status" => true, "message" => "Sale Completed Successfully", "id" => $last_id];
    }

    public function save_pharmacy_transfer()
    {
        $TotalSale = 0;

        foreach (request()->ProductList as $row) {
            $TotalSale = ($TotalSale) + (($row['Quantity'] * ($row['UnitePrice'])));
        }

        $patient_id = request()->patient_id;
        $invoice_discount = request()->invoice_discount;
        $admission_id = request()->patient_admission_id ?? 0;
        $customer = Patient::where(["id" => $patient_id])->first();
        $ReceivedAmountFromCustomer = 0;
        if (request()->ReceivedAmountFromCustomer) {
            $ReceivedAmountFromCustomer = request()->ReceivedAmountFromCustomer;
        }
        //-------------------------------------------//
        $Invoice = $this->returnTransferInvoiceNumber();


        $SupplierID = $patient_id;
        $Freight = 0;
        $PDate = date("Y-m-d", strtotime(request()->bill_date));
        $Description = request()->BillDiscription;
        $appointment_id = request()->appointment_id;
        $medicine_type = request()->medicine_type;
        $bill_description = request()->previous_balance ?? '';
        $discount_percentage = request()->discount_percentage ?? 0;
        $Discount = request()->discount_amount ?? 0;
        $demage = 0;
        $ReceivedAmount = request()->ReceivedAmount;

        $userID = auth()->user()->id;
        $totalTax = 0;

        $SalemanID = 0;
        $Commesion = 0;
        if ($customer) {
            $CustomerName = $customer->name . " - " . $customer->mr_no;
        } else {
            $CustomerName = "Walking Customer";
            $patient_id = 0;
        }

        if ($appointment_id) {
            $app = Appointment::where('is_active', 1)
                ->with(['patient'])
                ->where("id", $appointment_id)
                ->first();
            $CustomerName = $app->patient->name ?? "";
            $patient_id = $app->patient_id ?? 0;
            $admission_id = 0;
        }

        /*foreach(request()->ProductList as $row){
            $totalTax = ($totalTax) + ($row['taxAmount']);
        }*/
        $total = ($TotalSale) + $totalTax;

        $SaleArray = array(
            'SCID'     => 0, // 1 sehat card user,2 walking customer of retail store , table use sup_cus_details
            'store_id'     => 1, // sehat card user
            'wr_id'     =>  0, // sehat card user
            'transfer_type'     =>  request()->SID, // sehat card user
            'ReceivedAmountFromCustomer'   => $ReceivedAmountFromCustomer,
            'patient_id'   => 0,
            'admission_id'   => 0,
            'SaleID'   => 0,
            'InvoiceNo' => $Invoice,
            'appointment_id' => 0,
            'medicine_type' => $medicine_type,
            'Date'  => $PDate,
            'Description'   =>  $CustomerName,
            'TotalSale'     => $total,
            'received_amount'     => $ReceivedAmount,
            'Discount'     =>  $Discount,
            'invoice_discount'     =>  $invoice_discount,
            'discount_percentage'     =>  $discount_percentage,
            'sale_descriptions' => $bill_description,
            'CreatedBy'     => $userID,
            'CreatedAt'     => date('Y-m-d')
        );




        $SaleArray['bill_details'] = json_encode(request()->ProductList);

        try {
            DB::beginTransaction();
            $temp_sale = PharmacyTransfer::create($SaleArray);
            $item_details = [];
            foreach (request()->ProductList as $row) {
                $TotalSale = ($TotalSale) + (($row['Quantity'] * ($row['UnitePrice'])));
                $item_details[] = array(
                    'store_id'   => session('store_id'),
                    'SaleID'   => 0, //$sale->SaleID,
                    'temp_sale_id'   => $temp_sale->id,
                    'patient_id'   => $patient_id,
                    'admission_id'   => $admission_id,
                    'ProductID' => $row['ProductID'],
                    'UnitePrice'  => $row['UnitePrice'],
                    'taxPercentage'  => $row['taxPercentage'],
                    'dose_type'  => $row['dose_type'],
                    'Quantity'  => $row['Quantity'],
                    'PurchasePrice'  => $row['UnitePrice'],
                );
            }

            PharmacyTransferDetails::insert($item_details);

            DB::commit(); // ✅ commit if all good
        } catch (\Exception $e) {
            DB::rollBack(); // ❌ rollback on error
            throw $e;       // optional: rethrow for logging
        }



        $is_free = session('is_free');
        foreach (request()->ProductList as $row) {
            $soldQuantity = $row['Quantity'];
            $result = GrnDetails::where(["ProductID" => $row['ProductID'], "ProductStatus" => 1])->get();

            $applyTax = $row['taxPercentage'] / 100;
            foreach ($result as $key => $value) {
                if ($soldQuantity <= $value->RemainingQuantity && $soldQuantity != 0) {
                    $remainingQuantity = $value->RemainingQuantity - $soldQuantity;
                    GrnDetails::where(["GDID" => $value->GDID])->update(['RemainingQuantity' => $remainingQuantity, 'SoldQuantity' => ($value->SoldQuantity + $soldQuantity)]);
                    if ($remainingQuantity == 0) {
                        GrnDetails::where(['GDID' => $value->GDID])->update(['ProductStatus' => 0]);
                    }
                    $soldQuantity = 0;
                } else {
                    if ($soldQuantity > $value->RemainingQuantity && $soldQuantity != 0) {
                        GrnDetails::where(['GDID' => $value->GDID])->update(['RemainingQuantity' => 0, 'SoldQuantity' => ($value->SoldQuantity + $value->RemainingQuantity), 'ProductStatus' => 0]);
                    }
                }
            } //.... end of foreach
            //--------- end if stock is zero   ----------//
        } //------------ end of main foreach   -----------//


        sleep(1);

        return ["status" => true, "message" => "Sale Completed Successfully", "id" => $temp_sale->id];
    }


    public function temp_save_sale()
    {
        TempSale::truncate();
        TempSaleDetails::truncate();
        /*return response()->json([
            "data" => $request->all()
        ]);*/
        $customer = Customer::where(["SCID" => request()->SID])->first();
        //-------------------------------------------//
        $Invoice = $this->returnInvoiceNumber();
        $SupplierID = request()->SID;
        $Freight = 0;
        $PDate = date("Y-m-d", strtotime(request()->bill_date));
        $Description = request()->BillDiscription;
        $bill_description = request()->BillDiscription;
        $Discount = 0;
        $demage = 0;
        $ReceivedAmount = request()->ReceivedAmount;
        $userID = auth()->user()->id;
        $totalTax = 0;
        $TotalSale = request()->BillAmount;
        $SalemanID = 0;
        $Commesion = 0;
        $CustomerName = $customer->Name;
        /*foreach(request()->ProductList as $row){
            $totalTax = ($totalTax) + ($row['taxAmount']);
        }*/
        $total = ($TotalSale) + $totalTax;

        $invoice_discount = request()->invoice_discount ?? 0;

        $SaleArray = array(
            'SCID'     => $SupplierID,
            'InvoiceNo' => $Invoice,
            'Date'  => $PDate,
            'Description'   =>  $CustomerName,
            'TotalSale'     => $total,
            'received_amount'     => $ReceivedAmount,
            'Discount'     =>  0,
            'invoice_discount' => $invoice_discount,
            'sale_descriptions' => $bill_description,
            'CreatedBy'     => $userID,
            'CreatedAt'     => date('Y-m-d')
        );
        if ($SalemanID != '') {
            $SaleArray['SalemanCommesion'] = $Commesion;
            $SaleArray['SalemanID'] = $SalemanID;
        }

        $SaleArray['bill_details'] = json_encode(request()->ProductList);
        $sale = TempSale::create($SaleArray);
        $last_id = $sale->SaleID;





        foreach (request()->ProductList as $row) {
            $soldQuantity = $row['Quantity'];

            // Validate product discount (same as retail sale)
            $product = Product::find($row['ProductID']);
            $discount_percentage = isset($row['discount_percentage']) ? $row['discount_percentage'] : 0;
            $discount_percentage_amount = isset($row['discount_percentage_amount']) ? $row['discount_percentage_amount'] : 0;

            $returnQuantity = isset($row['ReturnQuantity']) ? $row['ReturnQuantity'] : 0;

            // Calculate actual quantity after returns
            $actualQuantity = $soldQuantity - $returnQuantity;

            // Apply same rules as retail sale:
            // 1. If allow_percentage is 0, don't apply any discount
            // 2. If requested discount > allowed, apply the allowed percentage
            // 3. If requested discount <= allowed, apply the requested percentage
            // 4. If quantity after return is zero, then discount_percentage_amount = 0
            if ($actualQuantity <= 0) {
                // Rule 4: If quantity after return is zero, then discount_percentage_amount = 0
                $discount_percentage_amount = 0;
            } else if ($product) {
                $allowPercentage = $product->allow_percentage ?? 0;

                if ($allowPercentage === 0) {
                    // Rule 3: If allow_percentage is zero, don't apply percentage
                    $discount_percentage = 0;
                    $discount_percentage_amount = 0;
                } else if ($discount_percentage > $allowPercentage) {
                    // Rule 1: If requested > allowed, apply the allowed percentage
                    $discount_percentage = $allowPercentage;
                    $unitPrice = $row['UnitePrice'];
                    // Bill Amount = (quantity - returnquantity) * UnitePrice
                    $itemTotal = $unitPrice * $actualQuantity;
                    $discount_percentage_amount = ($itemTotal * $allowPercentage) / 100;
                } else {
                    // Rule 2: If requested <= allowed, apply the requested percentage
                    $unitPrice = $row['UnitePrice'];
                    $itemTotal = $unitPrice * $actualQuantity;
                    $discount_percentage_amount = ($itemTotal * $discount_percentage) / 100;
                }
            }
            $Detail_array = array(
                'SaleID'   => $last_id,
                'ProductID' => $row['ProductID'],
                'UnitePrice'  => $row['UnitePrice'],
                'taxPercentage'  => $row['taxPercentage'],
                'taxAmount'  => $row['taxAmount'],
                'discount_percentage' => $discount_percentage,
                'discount_percentage_amount' => $discount_percentage_amount,
                'ReturnQuantity' => $returnQuantity,
            );
            $Detail_array['Quantity'] = $soldQuantity;
            TempSaleDetails::create($Detail_array);
        }

        return ["status" => true, "message" => "Temp Sale Completed Successfully", "id" => $last_id];
    }

    public function print_temp_sale($SaleID = '', $customer_id = '', $date = '', $received_amount = '')
    {

        $data['record'] = TempSale::where(['SaleID' => $SaleID])->get();
        $data['customer'] = Customer::where(["SCID" => $customer_id])->get();
        $data['receiveable'] = $received_amount;
        $data['PreviousBalance'] = (new CustomerPayments())->customer_previous_balance($customer_id, $date);

        $data['data'] = TempSaleDetails::with('product')->get();
        $data['show_customer_contact'] = "yes";
        $data['title'] = 'Sale Details Report';
        $return = "No";
        /*echo "<pre>";
        print_r($data);
        exit();*/
        foreach ($data['data'] as $rec) {
            $rec->AvaliableQuantity = ($rec->Quantity) - ($rec->ReturnQuantity);
            $rec->totalAmount = ($rec->AvaliableQuantity) * ($rec->UnitePrice);
            if ($rec->ReturnQuantity > 0) {
                $return = "Yes";
            }
        }
        if ($return == "Yes") {
            $data['return'] = "Yes";
        } else {
            $data['return'] = "No";
        }

        TempSale::truncate();
        TempSaleDetails::truncate();
        return view('reports/print_new_invoice', $data);
        // exit();
    } //--- End of function print_purchase_detail() ---//

    function returnInvoiceNumber()
    {
        $result = Sale::orderBy("SaleID", "DESC")->first();
        if ($result) {
            return ($result->SaleID) + 1;
        } else {
            return 1;
        }
    }

    function returnTransferInvoiceNumber()
    {
        $result = PharmacyTransfer::orderBy("id", "DESC")->first();
        if ($result) {
            return ($result->id) + 1;
        } else {
            return 1;
        }
    }

    public function getTransectionNo()
    {
        $rec = ReceiveablesDetail::orderBy("RDID", "DESC")->first();

        if (!empty($rec)) {
            return (($rec->RDID) + 1);
        } else {
            return (1);
        }
    }

    public function print_purchase_detail($SaleID = '', $date = '')
    {

        if ($date == '') {
            $date = date("Y-m-d");
        }
        $pTable = "sale";
        $columns = array('*');
        $where = array();
        $joins = '';

        $data['record'] = Sale::where(['SaleID' => $SaleID])->get();
        $customer_id = $data['record'][0]->SCID;
        $billDate = date("d-m-Y", strtotime($data['record'][0]->Date));

        //$data['PreviousBalance']=(new CustomerPayments())->customer_previous_balance($customer_id,$date);

        $data['data'] = SaleDetails::with('product')->where(['SaleID' => $SaleID])->get();
        $data['title'] = 'Sale Details Report';
        $return = "No";
        $totalAmount = 0;
        $data['prev_balance'] = (new CustomerPayments())->customer_previous_balance($customer_id, '');

        foreach ($data['data'] as $rec) {
            $rec->AvaliableQuantity = ($rec->Quantity) - ($rec->ReturnQuantity);
            $rec->totalAmount = ($rec->AvaliableQuantity) * ($rec->UnitePrice);
            $totalAmount = ($totalAmount) + ($rec->totalAmount);
            if ($rec->ReturnQuantity > 0) {
                $return = "Yes";
            }
        }

        $result = [];

        // Iterate through the array remove duplicate items . sum the quantity ,totalamount, taxamount and remove duplication for bill print only...//
        foreach ($data['data'] as $item) {
            $productId = $item->ProductID;

            // If ProductID already exists in the result, sum up the Quantity and UnitePrice
            if (isset($result[$productId])) {
                $result[$productId]->Quantity += $item->Quantity;
                $result[$productId]->totalAmount += $item->totalAmount;
                $result[$productId]->taxAmount += $item->taxAmount;
            } else {
                // Add new ProductID to result
                $result[$productId] = clone $item;
            }
        }
        $result = array_values($result);
        $data['data'] = $result;


        if ($return == "Yes") {
            $data['return'] = "Yes";
        } else {
            $data['return'] = "No";
        }


        $data['TotalAmount'] = $totalAmount;
        $data['show_customer_contact'] = "true";

        $data['customer'] = Customer::where("SCID", $customer_id)->get();

        return view('reports/customer_purchase_report_new', $data);
        //$this->load->view('reports/customer_purchase_report',$data);


        //
        // exit();
    } //--- End of function print_purchase_detail() ---//


    public function return_item()
    {
        $sale_details = SaleDetails::where(["SDID" => request()->SDID])->first();
        $sale = Sale::where(["SaleID" => $sale_details->SaleID])->first();
        $admission_id = $sale->admission_id;


        $retrun_qty = request()->ReturnQuantity;
        $total_return_price = ($sale_details->UnitePrice) * ($retrun_qty);
        $total_return_qty = ($sale_details->ReturnQuantity) + ($retrun_qty);

        $total_sale_amount = ($sale->TotalSale) - ($total_return_price);
        $received_amount = ($total_sale_amount) - ($sale->Discount);


        //------- sale related operations  ------------//
        Sale::where(["SaleID" => $sale_details->SaleID])->update(["TotalSale" => $total_sale_amount, "received_amount" => $received_amount]);
        SaleDetails::where(["SDID" => request()->SDID])->update(['ReturnQuantity' => $total_return_qty, 'return_by' => auth()->user()->id]);

        //---------- end of sale related operations   ---------//

        //------------- now grn detals .........//
        $result = GrnDetails::where(["GDID" => $sale_details->GDID])->first();
        $remainingQuanity = ($result->RemainingQuantity) + ($retrun_qty);
        $soldQuantity = ($result->SoldQuantity) - ($retrun_qty);
        $TotalReturn = ($result->TotalReturn) + ($retrun_qty);
        $grn_detailDate = array(
            "SoldQuantity" => $soldQuantity,
            "TotalReturn"  => $TotalReturn,
            "RemainingQuantity" => $remainingQuanity,
            "ProductStatus"     => 1
        );
        GrnDetails::where(['GDID' => $sale_details->GDID])->update($grn_detailDate);
        (new PatientAdmissionController())->updateAdmissionDetails($admission_id);
        return response()->json(["status" => true, "message" => "done"]);
        //$this->Zk_Common_Model->update_records('grn_details',$grn_detailDate,array('GDID'=>$GDID));
    }

    public function return_pharmacy_item()
    {
        $sale_details = SaleDetails::where(["SDID" => request()->SDID])->first();
        $sale = Sale::where(["SaleID" => $sale_details->SaleID])->first();

        $admission_id = $sale->admission_id;
        $sale_id = $sale->SaleID;
        $is_admitted_patient = "no";

        //---- check if patient is admitt or not  ---//
        if ($admission_id) {
            $check_patient_admission = InPatientAdmission::whereId($admission_id)->where("admission_status", "Admit")->first();
            if ($check_patient_admission) {
                $is_admitted_patient = "yes";
            } else {
                $is_admitted_patient = "no";
            }
        }
        //----- end of check -----//

        $retrun_qty = request()->ReturnQuantity;
        $total_return_qty = ($sale_details->ReturnQuantity) + ($retrun_qty);

        // Update the sale_details return quantity and recalculate discount amounts
        $active_quantity_after_return = max(0, $sale_details->Quantity - $total_return_qty);

        // Calculate new proportional discount amount for the returned item
        $new_discount_percentage_amount = 0;
        if ($active_quantity_after_return > 0 && $sale_details->Quantity > 0) {
            $proportion = $active_quantity_after_return / $sale_details->Quantity;
            if (isset($sale_details->discount_percentage_amount) && $sale_details->discount_percentage_amount > 0) {
                $new_discount_percentage_amount = $sale_details->discount_percentage_amount * $proportion;
            } else if (isset($sale_details->discount_percentage) && $sale_details->discount_percentage > 0) {
                $line_amount_before_discount = $active_quantity_after_return * $sale_details->UnitePrice;
                $new_discount_percentage_amount = ($line_amount_before_discount * $sale_details->discount_percentage) / 100;
            }
        }

        // Update the specific returned item first
        SaleDetails::where(["SDID" => request()->SDID])->update([
            'ReturnQuantity' => $total_return_qty,
            'discount_percentage_amount' => $new_discount_percentage_amount,
            'return_by' => auth()->user()->id
        ]);
        TempSaleDetails::where(["SaleID" => $sale_details->SaleID, "ProductID" => $sale_details->ProductID])->update([
            'ReturnQuantity' => $total_return_qty,
            'discount_percentage_amount' => $new_discount_percentage_amount,
            'return_by' => auth()->user()->id
        ]);

        // Now update discount_percentage_amount for ALL items in the sale based on their current active quantities
        $all_sale_details = SaleDetails::where(["SaleID" => $sale_details->SaleID])->get();

        foreach ($all_sale_details as $detail) {
            $active_quantity = max(0, $detail->Quantity - $detail->ReturnQuantity);
            $updated_discount_amount = 0;

            if ($active_quantity > 0 && $detail->Quantity > 0) {
                $proportion = $active_quantity / $detail->Quantity;

                // Get the original discount amount (before any returns)
                $original_discount_amount = 0;
                if (isset($detail->discount_percentage) && $detail->discount_percentage > 0) {
                    $original_line_amount = $detail->Quantity * $detail->UnitePrice;
                    $original_discount_amount = ($original_line_amount * $detail->discount_percentage) / 100;
                } else if (isset($detail->discount_percentage_amount)) {
                    // If we already have a stored amount, use it as reference for proportion
                    $original_discount_amount = $detail->discount_percentage_amount / ($detail->Quantity > 0 ? ($detail->Quantity - $detail->ReturnQuantity) / $detail->Quantity : 1);
                }

                $updated_discount_amount = $original_discount_amount * $proportion;
            }

            // Update the discount amount in database
            SaleDetails::where(["SDID" => $detail->SDID])->update([
                'discount_percentage_amount' => $updated_discount_amount
            ]);
            TempSaleDetails::where(["SaleID" => $detail->SaleID, "ProductID" => $detail->ProductID])->update([
                'discount_percentage_amount' => $updated_discount_amount
            ]);
        }

        // Fetch fresh data after updating ALL discount amounts
        $all_sale_details = SaleDetails::where(["SaleID" => $sale_details->SaleID])->get();

        $recalculated_total_before_discount = 0; // Sum of (Quantity - ReturnQuantity) * UnitePrice for all items
        $recalculated_total_discount_amount = 0; // Sum of all ITEM discount amounts (no invoice_discount here)

        foreach ($all_sale_details as $detail) {
            $active_quantity = max(0, $detail->Quantity - $detail->ReturnQuantity); // Ensure non-negative
            $line_amount_before_discount = $active_quantity * $detail->UnitePrice;
            $recalculated_total_before_discount += $line_amount_before_discount;

            // Calculate ITEM discount amount for this line - only if active_quantity > 0
            // NOTE: invoice_discount is NOT applied to individual items, only to final bill
            if ($active_quantity > 0) {
                if (isset($detail->discount_percentage_amount) && $detail->discount_percentage_amount > 0) {
                    // Proportional item discount for active quantity
                    $proportion = $active_quantity / max(1, $detail->Quantity); // Avoid division by zero
                    $line_discount_amount = $detail->discount_percentage_amount * $proportion;
                    $recalculated_total_discount_amount += max(0, $line_discount_amount); // Ensure non-negative
                } else if (isset($detail->discount_percentage) && $detail->discount_percentage > 0) {
                    // Calculate item discount percentage
                    $line_discount_amount = ($line_amount_before_discount * $detail->discount_percentage) / 100;
                    $recalculated_total_discount_amount += max(0, $line_discount_amount); // Ensure non-negative
                }
            }
            // If active_quantity is 0, item discount is automatically 0 (6% of 0 = 0)
        }

        // Calculate final amounts according to specifications
        // TotalSale = amount before discount of sum of per item (no invoice_discount applied here)
        $new_total_sale = max(0, $recalculated_total_before_discount); // Amount before discount, ensure non-negative

        // received_amount = sum of per item sale amount - total discount per item - invoice_discount (applied to final bill only)
        $amount_after_item_discounts = max(0, $recalculated_total_before_discount - $recalculated_total_discount_amount);
        $new_received_amount = max(0, $amount_after_item_discounts - ($sale->invoice_discount ?? 0)); // Invoice discount applied to final bill only


        //---- check if patient is admitt then correct the bill otherwise make entry in pharmacy_return_items table for user closing balance.---//
        //--- close balance will adjust from pharmacy return table only amount will be minus from total sale amount of user during closing  ---//

        if (($is_admitted_patient == "yes" && $sale->received_amount == 0)) {
            if ($sale->admission_id == 0) {  // if walking customer sale then also make changes in salepayment table
                SalePayment::where(["sale_id" => $sale_details->SaleID])->update(["amount" => $new_received_amount]);
            }
            Sale::where(["SaleID" => $sale_details->SaleID])->update([
                "is_return_made" => 1,
                "ModifiedAt" => date("Y-m-d H:i:s"),
                "ModifiedBy" => auth()->user()->id,
                "TotalSale" => $new_total_sale,
                "received_amount" => $new_received_amount,
                "Discount" => $recalculated_total_discount_amount
            ]);
            TempSale::where(["SaleID" => $sale_details->SaleID])->update([
                "is_return_made" => 1,
                "ModifiedAt" => date("Y-m-d H:i:s"),
                "ModifiedBy" => auth()->user()->id,
                "TotalSale" => $new_total_sale,
                "received_amount" => $new_received_amount,
                "Discount" => $recalculated_total_discount_amount
            ]);
        } else {
            PharmacyRetrun::create([
                "sale_id" => $sale_details->SaleID,
                "sale_detail_id" => request()->SDID,
                "product_id" => $sale_details->ProductID,
                "quantity" => request()->ReturnQuantity,
                "amount" => request()->return_amount,
                "created_by" => auth()->user()->id,
                "created_at" => date("Y-m-d H:i:s"),
            ]);

            Sale::where(["SaleID" => $sale_details->SaleID])->update([
                "is_return_made" => 1,
                "ModifiedAt" => date("Y-m-d H:i:s"),
                "ModifiedBy" => auth()->user()->id,
                "TotalSale" => $new_total_sale,
                "received_amount" => $new_received_amount,
                "Discount" => $recalculated_total_discount_amount
            ]);
            TempSale::where(["SaleID" => $sale_details->SaleID])->update([
                "is_return_made" => 1,
                "ModifiedAt" => date("Y-m-d H:i:s"),
                "ModifiedBy" => auth()->user()->id,
                "TotalSale" => $new_total_sale,
                "received_amount" => $new_received_amount,
                "Discount" => $recalculated_total_discount_amount
            ]);

            if ($admission_id != 0) {
                SalePayment::where(["admission_id" => $admission_id])->update(["amount" => $new_received_amount]);
            } else {
                SalePayment::where(["sale_id" => $sale_details->SaleID])->update(["amount" => $new_received_amount]);
            }
        }
        //-------- end of check  ------//

        //------------- now grn detals .........//
        $result = GrnDetails::where(["GDID" => $sale_details->GDID])->first();
        $remainingQuanity = ($result->RemainingQuantity) + ($retrun_qty);
        $soldQuantity = ($result->SoldQuantity) - ($retrun_qty);
        $TotalReturn = ($result->TotalReturn) + ($retrun_qty);
        $grn_detailDate = array(
            "SoldQuantity" => $soldQuantity,
            "TotalReturn"  => $TotalReturn,
            "RemainingQuantity" => $remainingQuanity,
            "ProductStatus"     => 1
        );
        GrnDetails::where(['GDID' => $sale_details->GDID])->update($grn_detailDate);

        return response()->json(["status" => true, "message" => "done"]);
        //$this->Zk_Common_Model->update_records('grn_details',$grn_detailDate,array('GDID'=>$GDID));
    }


    public function return_pharmacy_item_backup()
    {


        $sale_details = SaleDetails::where(["SDID" => request()->SDID])->first();
        $sale = Sale::where(["SaleID" => $sale_details->SaleID])->first();
        $discount_percentage =  $sale->discount_percentage;
        $admission_id = $sale->admission_id;


        $retrun_qty = request()->ReturnQuantity;
        $total_return_price = ($sale_details->UnitePrice) * ($retrun_qty);
        $total_return_qty = ($sale_details->ReturnQuantity) + ($retrun_qty);
        $discount_percentage_amount = round(($total_return_price * $discount_percentage) / 100);


        $total_sale_amount = ($sale->TotalSale) - ($total_return_price);
        //----- collect discount percentage from coustomer on return -----//
        $total_return_price = round($total_return_price - $discount_percentage_amount);
        $received_amount = ($sale->received_amount) - ($total_return_price);


        //------- sale related operations  ------------//
        Sale::where(["SaleID" => $sale_details->SaleID])->update(["TotalSale" => $total_sale_amount, "received_amount" => $received_amount]);
        SaleDetails::where(["SDID" => request()->SDID])->update(['ReturnQuantity' => $total_return_qty, 'return_by' => auth()->user()->id]);

        //---------- end of sale related operations   ---------//

        //------------- now grn detals .........//
        $result = GrnDetails::where(["GDID" => $sale_details->GDID])->first();
        $remainingQuanity = ($result->RemainingQuantity) + ($retrun_qty);
        $soldQuantity = ($result->SoldQuantity) - ($retrun_qty);
        $TotalReturn = ($result->TotalReturn) + ($retrun_qty);
        $grn_detailDate = array(
            "SoldQuantity" => $soldQuantity,
            "TotalReturn"  => $TotalReturn,
            "RemainingQuantity" => $remainingQuanity,
            "ProductStatus"     => 1
        );
        GrnDetails::where(['GDID' => $sale_details->GDID])->update($grn_detailDate);

        return response()->json(["status" => true, "message" => "done"]);
        //$this->Zk_Common_Model->update_records('grn_details',$grn_detailDate,array('GDID'=>$GDID));
    }



    public function get_bill_details($sale_id)
    {
        $patients = SaleDetails::with("product", "sale")
            ->when($sale_id, function ($query) use ($sale_id) {
                $query->where('SaleID', $sale_id);
            })
            ->when(request()->medicine_type, function ($query) {
                $query->whereHas('sale', function ($q) {

                    $q->where('medicine_type', request()->medicine_type);
                });
            });
        return DataTables::of($patients)
            ->addColumn("actions", function ($patient) {
                if ($patient->ReturnQuantity == $patient->Quantity) {
                    return "";
                } else {
                    return '<a href="javascript:void(0)"  data-details=\'' . $patient . '\' sale-price=\'' . $patient->UnitePrice . '\' data-discount-percentage=\'' . $patient->discount_percentage . '\'  class="btn btn-sm btn-primary return_product">Return</a>';
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

            ->rawColumns(["actions", "total_amount", "total_consumed"])
            ->make(true);
    }

    public function retail_sale_point()
    {
        $store = Store::where("id", "!=", env('SEHAT_CARD_PHARMACY_STORE_ID'))->first();
        session(['store_id' => 2]);
        session(['store_name' => "Retail Pharmacy Sale Point"]);
        session(['is_free' => 0]);
        /*if($store){
            session(['store_id' => $store->id]);
            session(['store_name' => $store->store_name]);
            session(['is_free' => $store->use_purchase_price_as_sale_price]);
        }*/

        $type = $_GET['type'] ?? "Home";
        $data['type'] = $type;
        $data["ward_request"] = $_GET["ward_request"] ?? "";
        $data['patient_id'] = "";
        $data['list_products'] = [];

        $data['appointments'] = Appointment::where('is_active', 1)
            ->where('created_at', '>=', Carbon::now()->subDays(2)) // last 5 days
            ->with(['patient'])
            ->orderBy('appointment_date', 'desc')
            ->get();


        $data["title"] = "Retail Sale Point";
        /*$data['products'] = Product::orderBy("ProductName", "ASC")
             ->when(session('store_id'),function ($q){
                 $q->where('store_id',session('store_id'));
             })
            ->get();*/
        //Cache::forget('products_store_2');
        $data["products"] =  Product::with('generic_name')
            ->when(session('store_id'), function ($q) {
                $q->where('store_id', session('store_id'));
            })
            ->orderBy("ProductName", "ASC")
            ->where("IsActive", 1)
            ->where("ProductName", "!=", '')
            ->where("pack_size", "!=", 0)
            ->where("pack_price", "!=", 0)

            ->get();
        foreach ($data['products'] as $key => $value) {
            $value->avaliable_qty = GrnDetails::where(["ProductID" => $value->ProductID])->sum('RemainingQuantity');
        }
        //$data['customers'] = Customer::where(["Type" => 2])->orderBy("Name", "ASC")->get();
        $data['admitted_patients'] = Patient::where("patient_type", "walking_customer")->get();

        $data['invoiceNo'] = $this->returnInvoiceNumber();

        return view("sale.retail_sale_point", $data);
    }
}