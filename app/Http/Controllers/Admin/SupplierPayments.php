<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointments\Appointment;
use App\Models\Customer;
use App\Models\Grn;
use App\Models\GrnDetails;
use App\Models\GrnRequest;
use App\Models\GrnRequestDetails;
use App\Models\Market;
use App\Models\Patient\Patient;
use App\Models\PaymentDetail;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class SupplierPayments extends Controller
{
    public function supplier_payments()
    {
        $data["title"] = "Supplier Payments";
        $data["suppliers"] = Customer::where("Type", 1)->get();
        $data["payment_type"] = PaymentType::get();
        return view("warehouse/supplier_payments", $data);
    }


    public function save_payments(Request $request)
    {
       
        $data = $request->all();
        $data["CreatedBy"] = Auth::user()->id;
        $data["CreatedAt"] = Carbon::today();
        $data["SCID"] = $request->SID;

        $paymentDetail = PaymentDetail::create($data);

        return response()->json([
            "data" => $paymentDetail
        ]);
    }


    public function get_payments($id)
    {
        $data = PaymentDetail::where('SCID', $id)
            ->with('paymentType');
        return DataTables::of($data)
            ->addColumn('payment_type', function ($data) {
                return $data->paymentType->payment_type ?? ''; // Safeguard against null
            })
           
            ->addColumn('action', function ($data) {
                // Example: Add an Edit/Delete button for actions
                return '<a class="btn btn-sm btn-primary">Edit</a>
                    <a class="btn btn-sm btn-danger">Delete</a>';
            })
            ->rawColumns(['payment_type','action']) // Allow raw HTML for action buttons
            ->make(true);
    }

    // public function get_payments($id){
    //     $data = PaymentDetail::where("SCID", $id)->with("paymentType")->get();

    //     // dd($data);
    //     return DataTables::of($data)
    //     ->addColumn('payment_type', function ($data) {
    //         return $data->paymentType->payment_type ?? '';
    //     });
    //     // return response()->json([
    //     //     "data"=> $data
    //     // ]);

    // }

    public function purchase_details($id=''){
        
        $data =  Grn::when($id, function ($query) use ($id) {
            return $query->where("SCID",$id);
        });
        //  <a class="btn btn-sm btn-primary" href="'.route('pos.edit_purchase_bill',[$data->GRNID]).'">Edit</a>
        return DataTables::of($data)
            ->addColumn('final_bill', function ($data) {
                return $data->TotalPurchase - ($data->per_item_discount) - ($data->Discount);
            })
            ->addColumn('action', function ($data) {

                return '<a class="btn btn-sm btn-success" href="'.route('pos.print_purchase',[$data->SCID, $data->GRNID]).'">Print Bill</a>
                <a class="btn btn-sm btn-success" href="'.route('pos.add_bill_items',[$data->GRNID]).'">Edit</a>
               
                    <a class="btn btn-sm btn-danger">Delete</a>';
            })
            ->rawColumns(['final_bill','action']) // Allow raw HTML for action buttons
            ->make(true);

        
    }

    function supplier_previous_balance($customer_id,$date=''){
        $customer = Customer::where(["sup_cus_details.SCID"=>$customer_id])->first();

        $openingBalance=$customer->OpeningBalance;
        if(!$openingBalance){
            $openingBalance=0;
        }
        $where=array('SCID'=>$customer_id);
        if($date!=''){
            $where['Date <']=$date;

        }
        /*$TotalSale = Grn::where(["SCID"=>$customer_id])
            ->when($date, function ($query) use ($date) {
                return $query->where('Dated', '>=', date("Y-m-d",strtotime($date)));
            })->sum('TotalPurchase');*/

        $query = Grn::where('SCID', $customer_id)
            ->when($date, function ($query) use ($date) {
                return $query->where('Dated', '>=', date("Y-m-d", strtotime($date)));
            });

        $totals = $query->selectRaw('SUM(TotalPurchase) as total_bill, SUM(Discount) as discount, SUM(per_item_discount) as per_item_discount')
            ->first();

        $TotalSale = $totals->total_bill;
        $totalDiscount = $totals->discount;
        $per_item_discount = $totals->per_item_discount;


        $TotalPaid = PaymentDetail::where(["SCID"=>$customer_id])
            ->when($date, function ($query) use ($date) {
                return $query->where('Dated', '<', date("Y-m-d",strtotime($date)));
            })->sum('Amount');

        $TotalAmount = ($openingBalance + $TotalSale) - ($totalDiscount) - ($per_item_discount) - $TotalPaid;
        if($TotalAmount){
            return $TotalAmount;
        }else{
            return 0;
        }

    }

    public function get_purchase_bill_items($id)
    {
        $data = GrnDetails::where('GRNID', $id)
            ->with('products');
        return DataTables::of($data)

            ->addColumn('total', function ($data) {
                return ($data->Quantity * $data->pack_price);

            })
            ->addColumn('action', function ($data) {
                return '<a class="btn btn-sm btn-primary edit_bill_item" data-details=\''.$data.'\'>Edit</a>
                    ';
            })
            ->rawColumns(['total','action']) // Allow raw HTML for action buttons
            ->make(true);
    }
    

    public function add_bill_items($id){
        /*$data["products"]= Product::with('generic_name')
            ->when(session('store_id'),function ($q){
                $q->where('store_id',session('store_id'));
            })
            ->where("IsActive", 1)->where("pack_size","!=",0)->where("pack_price","!=",0)->get();*/
        //Cache::forget('products_store_2');
        $data["products"] = Product::with('generic_name')
            ->when(session('store_id'), function ($q) {
                $q->where('store_id', session('store_id'));
            })
            ->orderBy("ProductName", "ASC")
            ->where("ProductName", "!=", '')
            ->where("IsActive", 1)
            ->where("pack_size", "!=", 0)
            ->where("pack_price", "!=", 0)
            ->get();
        //$data["products"] = [];
        $data['grn'] = GrnRequest::where('GRNID', $id)->with("products")->with('store')->first();
        $data['purchase'] = GrnRequestDetails::where('GRNID', $id)->with("products")->orderBy("GDID","DESC")->where(["ProductStatus" => 1])->paginate(400);
        $data['id'] = $id;



        // return $data;
        return view('reports/print_purchase_details', $data);
    }

    public function print_thermel_purchase_details($SaleID)
    {

        
        $date = date("Y-m-d");

        $data['record'] = Sale::where(['SaleID' => $SaleID])->get();
        $data['patient'] = Patient::where(["id"=> $data['record'][0]->patient_id])->first();
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

        return view('reports/print_sale_invoice', $data);
    }

    public function print_retail_thermel_purchase_details($SaleID)
    {


        $date = date("Y-m-d");

        $data['record'] = Sale::where(['SaleID' => $SaleID])->first();
        $data['patient'] = Patient::where(["id"=> $data['record']->patient_id])->first();
        $appointments = Appointment::where('is_active', 1)
            ->where('id', $data['record']->appointment_id) // last 5 days
            ->with(['patient'])
            ->first();
        $data['appointment_patient_name'] = $appointments ? $appointments->patient?->name." | Appointment# ".$appointments->appointment_number : "";


        $customer_id = $data['record']->SCID;
        $billDate = date("d-m-Y", strtotime($data['record']->Date));

        $discount_percentage = $data['record']->discount_percentage;

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
        $data['TotalDiscount'] = ($totalAmount * $discount_percentage)/100;

        $data['show_customer_contact'] = "true";

        $data['customer'] = Customer::where("SCID", $customer_id)->get();

      //  $this->printFormattedReceipt($data['data'], $data['record'], $data['patient'], $data['TotalDiscount']);
        //$this->printFormattedReceipt($data['data'], $data['record'], $data['patient'], $data['TotalDiscount']);


        return view('reports/print_retail_sale_invoice', $data);
    }

    public function printFormattedReceipt($data, $record, $patient, $TotalDiscount)
    {
        try {
            $connector = new WindowsPrintConnector(env("THERMAL_PRINTER_NAME"));
            $printer = new Printer($connector);

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(env('COMPANY_NAME')."\n");
            $printer->text(date("d-m-Y h:i A") . "\n");
            $printer->text("------------------------------------------\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Name: " . ($patient->name ?? '') . "\n");
            $printer->text("Printed By: " . (auth()->user()->name ?? '') . "\n");
            $printer->text("------------------------------------------\n");

            // Table Header
            $printer->text(
                str_pad("No", 4) .
                str_pad("Item", 18) .
                str_pad("Qty", 5, ' ', STR_PAD_LEFT) .
                str_pad("Price", 7, ' ', STR_PAD_LEFT) .
                str_pad("Amt", 8, ' ', STR_PAD_LEFT) . "\n"
            );
            $printer->text("------------------------------------------\n");

            $i = 1;
            $totalAmount = 0;

            foreach ($data as $d) {
                $qty = $d->Quantity - $d->ReturnQuantity;
                $name = substr($d->product->ProductName, 0, 18); // truncate if too long
                $price = number_format($d->UnitePrice, 0);
                $amount = number_format($d->totalAmount, 0);
                $totalAmount += $d->totalAmount;

                $printer->text(
                    str_pad($i++, 4) .
                    str_pad($name, 18) .
                    str_pad($qty, 5, ' ', STR_PAD_LEFT) .
                    str_pad($price, 7, ' ', STR_PAD_LEFT) .
                    str_pad($amount, 8, ' ', STR_PAD_LEFT) . "\n"
                );

                if ($d->ReturnQuantity > 0) {
                    $printer->text("     (Return: {$d->ReturnQuantity})\n");
                }
            }

            $printer->text("------------------------------------------\n");

            $discount = round($TotalDiscount + $record->invoice_discount);
            $finalAmount = max(0, round($totalAmount - $record->Discount - $record->invoice_discount));

            $printer->text(str_pad("Total:", 34) . str_pad(number_format($totalAmount, 0), 8, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Discount:", 34) . str_pad(number_format($discount, 0), 8, ' ', STR_PAD_LEFT) . "\n");
            $printer->text(str_pad("Amount Due:", 34) . str_pad(number_format($finalAmount, 0), 8, ' ', STR_PAD_LEFT) . "\n");

            $printer->text("\nThank you for visiting!\n");
            $printer->text("\n\n\n");
            $printer->cut();
            $printer->close();

            return response("Printed successfully");

        } catch (\Exception $e) {
            return response("Print failed: " . $e->getMessage(), 500);
        }
    }

    public function previous_bills(){
        $bills = Sale::orderBy("SaleID", "DESC")->with("patient")
            ->where('store_id',env('SEHAT_CARD_PHARMACY_STORE_ID'))
            ->limit(50);

        return DataTables::of($bills)
            
            ->addColumn('action', function ($data) {
                return '<a target="_blank" href="' . route("pos.print_thermel_purchase_details", [$data->SaleID]) . '" class="btn btn-sm btn-success ">Print Thermal</a>';
            })
            ->rawColumns(["action"])
            ->make(true);
    }

    public function retail_previous_bills(){
        $bills = Sale::orderBy("SaleID", "DESC")->with("patient")
            ->when(session('store_id'),function ($q){
                $q->where('store_id',session('store_id'));
            })
            ->when((userRole() != "Super Admin"), function ($q) {
                return $q->where(["CreatedBy" => auth()->user()->id]);
            })
            ->limit(50);

        return DataTables::of($bills)

            ->addColumn('action', function ($data) {
                return '<a target="_blank" href="' . route("pos.print_retail_thermel_purchase_details", [$data->SaleID]) . '" class="btn btn-sm btn-success ">Print Bill</a>
                    <a target="_blank" href="' . route("pos.return_pharmacy_product", [$data->SaleID]) . '" class="btn btn-sm btn-success ">Return</a>';
            })
            ->rawColumns(["action"])
            ->make(true);
    }
    
    public function print_purchase($SCID, $GRNID){
        $data["supplier"] = Customer::where("Type", 1)->where("SCID", $SCID)->with("market")->first();
        $data["products"]= Product::where("IsActive", 1)->get();
        $data['purchase'] = GrnDetails::where('GRNID', $GRNID)->with("grn","products")->orderBy("GDID","DESC")->where(["GRNID" => $GRNID])->get();
        $data['id'] = $GRNID;
        
        // return $data;
        return view('reports/print_purchase', $data);
    }

    public function print_purchase_request($SCID, $GRNID){
        $data["supplier"] = Customer::where("Type", 1)->where("SCID", $SCID)->with("market")->first();
        $data["products"]= Product::where("IsActive", 1)->get();
        $data["grn_request"]= GrnRequest::where("GRNID", $GRNID)->first();
        $data['purchase'] = GrnRequestDetails::where('GRNID', $GRNID)->with("products")->orderBy("GDID","DESC")->where(["GRNID" => $GRNID])->get();


        $data['id'] = $GRNID;

        // return $data;
        return view('reports/print_purchase', $data);
    }



    
    
}
