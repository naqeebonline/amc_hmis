<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GrnDetails;
use App\Models\Market;
use App\Models\Patient\Patient;
use App\Models\Patient\PatientAdmission;
use App\Models\Product;
use App\Models\ReceiveablesDetail;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\TempSale;
use App\Models\TempSaleDetails;
use App\Models\WardRequest;
use App\Models\WardRequestDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;

class SaleController extends Controller
{
    public function add_new_sale()
    {
        $type = $_GET['type'] ?? "";
        $data['type'] = $type;
        $data["ward_request"] = $_GET["ward_request"] ?? "";
        $data['patient_id'] = "";
        $data['list_products'] = [];
        if($data['ward_request']){
            $ward_request = WardRequest::whereId($data['ward_request'])->first();
            $data['patient_id'] = $ward_request->patient_id;
            $ward_request_details = WardRequestDetails::with(['products'])->where(["wr_id"=>$ward_request->id])->get();
            $list_products = [];
            foreach ($ward_request_details as $key => $value){
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
                array_push($list_products,$res);
            }
            $data['list_products'] = $list_products;

        }
        $data["title"] = "Add New Sale";
        $data['products'] = Product::orderBy("ProductName", "ASC")
            ->when($type == 'Home', function ($query) {
                return $query->where("item_form_id","!=",16);
            })
           ->get();
        foreach ($data['products'] as $key => $value){
            $value->avaliable_qty = GrnDetails::where(["ProductID"=> $value->ProductID])->sum('RemainingQuantity');

        }
        //$data['customers'] = Customer::where(["Type" => 2])->orderBy("Name", "ASC")->get();
        $data['admitted_patients'] = PatientAdmission::where(["admission_status" => "Admit","is_active"=>1])
            ->orWhereDate('discharge_date', '>=', Carbon::now()->subDay(2)->format('Y-m-d H:i:s'))
            ->with(["patient"])->get();

        $data['invoiceNo'] = $this->returnInvoiceNumber();
        return view("sale.new_sale", $data);
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
        $admission_id = request()->patient_admission_id;
        $customer = Patient::where(["id"=>$patient_id])->first();
        //-------------------------------------------//
        $Invoice = $this->returnInvoiceNumber();
        $SupplierID = $patient_id;
        $Freight = 0;
        $PDate = date("Y-m-d",strtotime(request()->bill_date));
        $Description = request()->BillDiscription;
        $medicine_type = request()->medicine_type;
        $bill_description = request()->BillDiscription;
        $Discount = 0;
        $demage = 0;
        $ReceivedAmount = request()->ReceivedAmount;
        $userID = auth()->user()->id;
        $totalTax = 0;
        $TotalSale = request()->BillAmount;
        $SalemanID = 0;
        $Commesion = 0;
        $CustomerName = $customer->name." - ".$customer->mr_no;
        /*foreach(request()->ProductList as $row){
            $totalTax = ($totalTax) + ($row['taxAmount']);
        }*/
        $total = ($TotalSale) + $totalTax;

        $SaleArray = array(
            'SCID'     => 1,// sehat card user
            'wr_id'     => request()->ward_request_id ?? 0,// sehat card user
            'patient_id'   => $patient_id,
            'admission_id'   => $admission_id,
            'InvoiceNo' => $Invoice,
            'medicine_type' => $medicine_type,
            'Date'  =>$PDate ,
            'Description'   =>  $CustomerName,
            'TotalSale'     => $total,
            'received_amount'     => $ReceivedAmount,
            'Discount'     =>  0,
            'sale_descriptions' => $bill_description,
            'CreatedBy'     => $userID,
            'CreatedAt'     => date('Y-m-d')
        );
        if($SalemanID!=''){
            $SaleArray['SalemanCommesion']=$Commesion;
            $SaleArray['SalemanID']=$SalemanID;
        }

        $SaleArray['bill_details']=json_encode(request()->ProductList);
        $sale = Sale::create($SaleArray);
        $last_id = $sale->SaleID;

        /*if($ReceivedAmount!=0 && $ReceivedAmount!=''){
            $paymentArray = array(
                'InvoiceNo'=>$this->returnInvoiceNumber(),
                'SCID'     => $SupplierID,
                'Amount'=>$ReceivedAmount,
                'TransectionNo'=>$this->getTransectionNo(),
                'Payment_type_ID'=>1,
                'BankID'=>0,
                'Date'=> date("Y-m-d"),
                'CreatedBy'     => $userID,
                'CreatedAt'     => date('Y-m-d')
            );
            ReceiveablesDetail::create($paymentArray);
        }*/


        foreach(request()->ProductList as $row){
            $soldQuantity=$row['Quantity'];
            $result = GrnDetails::where(["ProductID"=>$row['ProductID'],"ProductStatus"=>1])->get();
            $Detail_array = array(
                'SaleID'   => $last_id,
                'patient_id'   => $patient_id,
                'admission_id'   => $admission_id,
                'ProductID' => $row['ProductID'],
                'UnitePrice'  => $row['UnitePrice'],
                'taxPercentage'  => $row['taxPercentage'],
                'dose_type'  => $row['dose_type'],
            );
            $applyTax = $row['taxPercentage'] / 100;
            foreach($result as $key=>$value){
                if($soldQuantity <= $value->RemainingQuantity && $soldQuantity!=0){
                    //echo "yes";
                    $total = ($soldQuantity) * ($row['UnitePrice']);
                    $taxAmount = ($total) * $applyTax;

                    $Detail_array['PurchasePrice']=$value->UnitPrice;
                    $Detail_array['Quantity']=$soldQuantity;
                    $Detail_array['GDID']=$value->GDID;
                    $Detail_array['taxAmount']=$taxAmount;
                    $remainingQuantity=$value->RemainingQuantity - $soldQuantity;
                    SaleDetails::create($Detail_array);
                    GrnDetails::where(["GDID"=>$value->GDID])->update(['RemainingQuantity'=>$remainingQuantity,'SoldQuantity'=>($value->SoldQuantity + $soldQuantity)]);
                    if($remainingQuantity==0){
                        GrnDetails::where(['GDID'=>$value->GDID])->update(['ProductStatus'=>0]);
                    }
                    $soldQuantity=0;
                }
                else{
                    if($soldQuantity > $value->RemainingQuantity && $soldQuantity!=0){
                        $total = ($value->RemainingQuantity) * ($row['UnitePrice']);
                        $taxAmount = ($total) * $applyTax;


                        $Detail_array['PurchasePrice']=$value->UnitPrice;
                        $Detail_array['Quantity']=$value->RemainingQuantity;
                        $Detail_array['GDID']=$value->GDID;
                        $Detail_array['taxAmount']=$taxAmount;
                        $soldQuantity=($soldQuantity)-($value->RemainingQuantity);
                        //echo $soldQuantity;
                        SaleDetails::create($Detail_array);
                        GrnDetails::where(['GDID'=>$value->GDID])->update(['RemainingQuantity'=>0,'SoldQuantity'=>($value->SoldQuantity + $value->RemainingQuantity),'ProductStatus'=>0]);
                    }
                }

            }//.... end of foreach
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
        }//------------ end of main foreach   -----------//

        if(request()->ward_request_id){
            WardRequest::whereId(request()->ward_request_id)->update(["issued_by"=>auth()->user()->id,"issued_at"=>date("Y-m-d H:i:s"),"status"=>1]);
        }

        return ["status"=>true,"message" => "Sale Completed Successfully","id"=>$last_id];
    }


    public function temp_save_sale()
    {
        TempSale::truncate();
        TempSaleDetails::truncate();
        /*return response()->json([
            "data" => $request->all()
        ]);*/
        $customer = Customer::where(["SCID"=>request()->SID])->first();
        //-------------------------------------------//
        $Invoice = $this->returnInvoiceNumber();
        $SupplierID = request()->SID;
        $Freight = 0;
        $PDate = date("Y-m-d",strtotime(request()->bill_date));
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

        $SaleArray = array(
            'SCID'     => $SupplierID,
            'InvoiceNo' => $Invoice,
            'Date'  =>$PDate ,
            'Description'   =>  $CustomerName,
            'TotalSale'     => $total,
            'received_amount'     => $ReceivedAmount,
            'Discount'     =>  0,
            'sale_descriptions' => $bill_description,
            'CreatedBy'     => $userID,
            'CreatedAt'     => date('Y-m-d')
        );
        if($SalemanID!=''){
            $SaleArray['SalemanCommesion']=$Commesion;
            $SaleArray['SalemanID']=$SalemanID;
        }

        $SaleArray['bill_details']=json_encode(request()->ProductList);
        $sale = TempSale::create($SaleArray);
        $last_id = $sale->SaleID;





        foreach(request()->ProductList as $row){
            $soldQuantity=$row['Quantity'];
            $Detail_array = array(
                'SaleID'   => $last_id,
                'ProductID' => $row['ProductID'],
                'UnitePrice'  => $row['UnitePrice'],
                'taxPercentage'  => $row['taxPercentage'],
                'taxAmount'  => $row['taxAmount'],
            );
            $Detail_array['Quantity']=$soldQuantity;
            TempSaleDetails::create($Detail_array);
        }

        return ["status"=>true,"message" => "Temp Sale Completed Successfully","id"=>$last_id];
    }

    public function print_temp_sale($SaleID = '',$customer_id='',$date='',$received_amount='')
    {

        $data['record']=TempSale::where(['SaleID'=> $SaleID])->get();
        $data['customer'] = Customer::where(["SCID"=>$customer_id])->get();
        $data['receiveable']=$received_amount;
        $data['PreviousBalance']=(new CustomerPayments())->customer_previous_balance($customer_id,$date);

        $data['data']=TempSaleDetails::with('product')->get();
        $data['show_customer_contact'] = "yes";
        $data['title'] = 'Sale Details Report';
        $return="No";
        /*echo "<pre>";
        print_r($data);
        exit();*/
        foreach($data['data'] as $rec){
            $rec->AvaliableQuantity=($rec->Quantity)-($rec->ReturnQuantity);
            $rec->totalAmount = ($rec->AvaliableQuantity) * ($rec->UnitePrice);
            if($rec->ReturnQuantity >0){
                $return="Yes";
            }
        }
        if($return=="Yes"){
            $data['return']="Yes";
        }else{
            $data['return']="No";
        }

        TempSale::truncate();
        TempSaleDetails::truncate();
        return view('reports/print_new_invoice',$data);
        // exit();
    }//--- End of function print_purchase_detail() ---//

    function returnInvoiceNumber(){
        $result = Sale::orderBy("SaleID","DESC")->first();
        if($result){
            return ($result->SaleID)+1;
        }else{
            return 1;
        }
    }

    public function getTransectionNo()
    {
        $rec = ReceiveablesDetail::orderBy("RDID","DESC")->first();

        if(!empty($rec)){
            return (($rec->RDID)+1);
        }else{
            return (1);
        }

    }

    public function print_purchase_detail($SaleID = '',$date='')
    {

        if($date==''){
            $date=date("Y-m-d");
        }
        $pTable="sale";
        $columns=array('*');
        $where=array();
        $joins ='';

        $data['record']=Sale::where(['SaleID'=> $SaleID])->get();
        $customer_id = $data['record'][0]->SCID;
        $billDate = date("d-m-Y",strtotime($data['record'][0]->Date));

        //$data['PreviousBalance']=(new CustomerPayments())->customer_previous_balance($customer_id,$date);

        $data['data']=SaleDetails::with('product')->where(['SaleID'=> $SaleID])->get();
        $data['title'] = 'Sale Details Report';
        $return="No";
        $totalAmount=0;
        $data['prev_balance'] = (new CustomerPayments())->customer_previous_balance($customer_id,'');

        foreach($data['data'] as $rec){
            $rec->AvaliableQuantity=($rec->Quantity)-($rec->ReturnQuantity);
            $rec->totalAmount = ($rec->AvaliableQuantity) * ($rec->UnitePrice);
            $totalAmount=($totalAmount)+($rec->totalAmount);
            if($rec->ReturnQuantity >0){
                $return="Yes";
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


        if($return=="Yes"){
            $data['return']="Yes";
        }else{
            $data['return']="No";
        }


        $data['TotalAmount']=$totalAmount;
        $data['show_customer_contact']="true";

        $data['customer'] = Customer::where("SCID",$customer_id)->get();

        return view('reports/customer_purchase_report_new',$data);
        //$this->load->view('reports/customer_purchase_report',$data);


        //
        // exit();
    }//--- End of function print_purchase_detail() ---//


    public function return_item()
    {
        $sale_details = SaleDetails::where(["SDID"=>request()->SDID])->first();
        $sale = Sale::where(["SaleID"=>$sale_details->SaleID])->first();

        $retrun_qty = request()->ReturnQuantity;
        $total_return_price = ($sale_details->UnitePrice) * ($retrun_qty);
        $total_return_qty = ($sale_details->ReturnQuantity) + ($retrun_qty);

        $total_sale_amount = ($sale->TotalSale)-($total_return_price);


        //------- sale related operations  ------------//
            Sale::where(["SaleID"=>$sale_details->SaleID])->update(["TotalSale"=>$total_sale_amount]);
            SaleDetails::where(["SDID"=>request()->SDID])->update(['ReturnQuantity'=>$total_return_qty,'return_by'=>auth()->user()->id]);

        //---------- end of sale related operations   ---------//

        //------------- now grn detals .........//
        $result = GrnDetails::where(["GDID"=>$sale_details->GDID])->first();
        $remainingQuanity=($result->RemainingQuantity)+($retrun_qty);
        $soldQuantity=($result->SoldQuantity)-($retrun_qty);
        $TotalReturn=($result->TotalReturn)+($retrun_qty);
        $grn_detailDate=array(
            "SoldQuantity" =>$soldQuantity,
            "TotalReturn"  =>$TotalReturn,
            "RemainingQuantity" =>$remainingQuanity,
            "ProductStatus"     =>1
        );
        GrnDetails::where(['GDID'=>$sale_details->GDID])->update($grn_detailDate);
        return response()->json(["status"=>true,"message"=>"done"]);
        //$this->Zk_Common_Model->update_records('grn_details',$grn_detailDate,array('GDID'=>$GDID));
    }

}
