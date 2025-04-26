<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">


    <style>
        body{
            overflow-x:hidden;

        }
        table body td:first-child table thead th:first-child {
            width: 20%;
        }


        th {
            width: 10%;
        }
        table input{
            width: 100px;
        }
        .wrapper{
            overflow: hidden;
        }
        input {
            height: 30px;
            font-weight: bold;
        }
        label{
            font-weight: bold;
            color: white;
        }
        td{
            font-size: 13px;
            font-weight: bold;
        }
        th{
            background-color: lightgrey !important;
        }
    </style>
</head>

<body>


<div class="wrapper">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body" style="background-color: #836f76b3">
                    <form method="post" action="{{ route('pos.add_item_to_bill') }}">
                        @csrf
                        <div class="row">

                            <div class="col-md-3 ">
                                <input type="hidden" id="GRNID" name="GRNID" value="{{ $id }}">
                                <label>Product</label>
                                @error('item_exist')
                                <div style="color: red;">{{ $message }}</div>
                                @enderror
                                <select required class="form-select" id="product_id" name="ProductID">
                                    <option value="">Select Product...</option>
                                    <?php foreach ($products as $key => $value): ?>
                                    <option value="{{ $value->ProductID }}"
                                            data-purchasePrice={{ $value->PurchasePrice }}
                                                    data-taxPercentage="{{ $value->taxPercentage }}" data-pack_price={{ $value->pack_price }} data-packsize="{{ $value->pack_size }}">{{ $value->ProductName." | ( ".$value->generic_name?->name." ) | PS: ".$value->pack_size }}
                                    </option>
                                    <?php endforeach; ?>
                                </select>

                            </div>

                            <div class="col-md-1">
                                <label for="">Quantity</label>
                                <input type="number" required id="pack_qty" class="form-control" > PS= <span class="pack_size_text"></span>
                            </div>
                            <div class="col-md-1">
                                <label for="">Total Unit</label>
                                <input type="text" required name="Quantity" readonly id="Quantity" class="form-control" >
                                <input type="hidden" required name="UnitPrice" readonly id="UnitPrice" class="form-control" >
                                <input type="hidden" required name="pack_size" readonly id="pack_size" class="form-control" >
                            </div>

                            <div class="col-md-1">
                                <label for="">Price</label>
                                <input type="text" required name="pack_price" id="pack_price" class="form-control" >
                            </div>
                            <div class="col-md-1" style="display: none">
                                <label for="">Disc %</label>
                                <input type="number" value="0" required name="discount" id="discount" class="form-control" >
                            </div>

                            <div class="col-md-1" style="display: none">
                                <label for="">Gst</label>
                                <input type="text" value="0" required name="gst_tax" id="gst_tax" class="form-control" >
                            </div>
                            <div class="col-md-1" style="display: none">
                                <label for="">Adv.Tax</label>
                                <input type="text" value="0" required name="advance_tax" id="advance_tax" class="form-control" >
                            </div>


                            <div class="col-md-2">
                                <label for="">Batch No.</label>
                                <input type="text" required name="batch_no" id="batch_no" class="form-control" placeholder="Batch No">
                            </div>
                            <div class="col-md-2">
                                <label for="">Expiry</label>
                                <input type="date" value="{{date("Y-m-d", strtotime("+8 months"))}}" required name="expiry_date" id="expiry_date" class="form-control" placeholder="Expiry">
                            </div>


                            <div class="col-md-2  mt-4">
                                <label for=""></label>
                                <button class="btn btn-primary" type="submit">Add Item</button>
                            </div>






                            {{-- <div class="col-md-1">
                                <label for="">advance_tax &</label>
                                <input type="text" name="advance_tax" id="advance_tax" class="form-control" >
                            </div> --}}


                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="card border-0">
                <div class="card-body ">




                    <table class="table table-bordered table-responsive m-0">
                        <thead>
                        <tr>
                            <th style="width: 50px">#</th>
                            <th style="width: 14%">Name</th>
                            <th style="width: 5%">Qty</th>
                            <th style="width: 8%">Units</th>
                            <th style="width: 8%">Pack Price</th>
                            <th style="width: 6%">Total</th>
                            <th style="width: 5%">GST</th>

                            <th style="width: 5%">Adv.Tax</th>
                            <th style="width: 6%">Disc %</th>
                            <th style="width: 5%">Adv.Tax%</th>

                            <th style="width: 8%">GST%</th>
                            <th style="width: 15%">Expiry</th>
                            <th style="width: 10%">Action</th>
                        </tr>
                        </thead>
                        @php
                            $gstTotalAmount = 0;
                            $advanceTaxTotalAmount = 0;
                            $grantTotalAmount = 0;
                            $billDiscount = $grn->Discount ?? 0;
                            $grantPerItemTotalDiscount = 0;
                        @endphp
                        <tbody>
                        @foreach ($purchase as $key => $item)
                            @php
                                $gstTotalAmount += $item->gst_tax_amount;
                                $qty = $item->Quantity / $item->pack_size;
                               // $totalAmount = $qty * $item->pack_price;
                               $totalAmount = $item->Quantity * $item->UnitPrice;
                                $grantTotalAmount += $totalAmount;
                                //dd($grantTotalAmount);
                                $advanceTaxTotalAmount += $item->advance_tax_amount;

                                $item_dicount = ($item->discount) / 100;
                               $per_item_discount = ($totalAmount) * $item_dicount;
                                $grantPerItemTotalDiscount += $per_item_discount;

                            @endphp
                            <tr >
                                <form method="post" action="{{ route('pos.update_grn') }}">
                                    @csrf
                                    <input type="hidden" name="GRNID" value="{{ $id }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="GDID[]" value="{{ $item->GDID }}">
                                        <input type="hidden" name="pack_size[]" value="{{ $item->pack_size }}">

                                        {{ $item->products->ProductName }}
                                    </td>
                                    <td class="text-center"><input type="number" id="bill_item_{{$item->GDID}}" onchange="handleChange(this, {{ $item->pack_size }})"
                                                                   name="pack_qty[]"
                                                                   value="{{ ceil($qty) }}"><br/><p style="font-size: 9px">{{ number_format($qty,2) . ' x ' . $item->pack_size }}</p></td>
                                    <td class="text-center"> <input  type="number" name="Quantity[]"
                                                                     value="{{ $item->Quantity }}"></td>

                                    <td><input type="number" step="0.01" name="pack_price[]" value="{{ $item->pack_price }}"> <span style="color: red;font-size: 9px;">U.Price: {{$item->UnitPrice}}</span>
                                    </td>
                                    <td>{{ $totalAmount }}</td>
                                    <td>{{ $item->gst_tax_amount }}</td>
                                    <td>{{ $item->advance_tax_amount }}</td>
                                    <td class="text-center"><input type="number" style="width: 80% !important;" step="0.01" name="discount[]" value="{{ $item->discount ?? "0" }}">%</td>
                                    <td class="text-center"><input type="number" step="0.01" name="advance_tax[]"  value="{{ $item->advance_tax }}"></td>

                                    <td class="text-center"><input type="number" name="gst_tax[]" step="0.01" value="{{ $item->gst_tax }}"></td>
                                    <td class="text-center"><input style="width: 100%" name="expiry_date[]" type="date" value="{{ ($item->expiry_date) ? date("Y-m-d",strtotime($item->expiry_date)) :"" }}"></td>
                                    <td>
                                        <button type="submit" class="btn btn-primary">Upd</button>
                                        <a class="btn btn-danger delete_item" data_id="{{ $item->GDID }}" href="javascript:void(0)">X</a></td>
                                </form>
                            </tr>
                        @endforeach
                        </tbody>


                    </table>



                    <table class="table table-bordered table-responsive m-0">

                        <tfoot>
                        <tr>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <td style="width: 10%"></td>
                            <th style="width: 10%">Net Amount: {{ number_format($grantTotalAmount,2) }}</th>
                            <th style="width: 10%">GST: {{ number_format($gstTotalAmount,2) }}</th>
                            <th style="width: 10%">Adv.Tax: {{ number_format($advanceTaxTotalAmount,2) }}</th>
                            <th style="width: 10%">
                                <p style="font-size: 12px">Discount on Bill: {{number_format($billDiscount,2)}}</p>
                                <p style="font-size: 12px">Per Item Discount:</p> {{number_format($grantPerItemTotalDiscount,2)}}
                            </th>
                            <th style="width: 10%">Gros Amount: {{ number_format(($grantTotalAmount + $gstTotalAmount + $advanceTaxTotalAmount) - ($grantPerItemTotalDiscount + $billDiscount),2) }}</th>
                            <th></th>

                        </tr>
                        </tfoot>
                    </table>

                </div>
            </div>



        </div>
    </div>
</div>

<script src="{{ asset('assets/js/jquery-3.5.1.min.js') }}"></script>
<link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script>

    $("#product_id").select2();

    setTimeout(function () {
        // $("#product_id").select2('open');
    },500);

    var edited_field = "{{$_GET['edit_id'] ?? ''}}";
    if(edited_field == ''){
        $("#product_id").select2('open');
    }else{
        $("#bill_item_"+edited_field).focus();
    }

    $(document).on("keyup", "#pack_qty", function() {
        var pack_qty = $(this).val();

        var totalqty = (pack_qty) * (pack_size);
        $("#Quantity").val(totalqty);

    });

    $(document).on("blur", "#pack_qty", function() {
        $("#pack_price").focus();

    });

    $(document).on("blur", "#pack_price", function() {
        $("#batch_no").focus();
    });
    $(document).on("change", "#product_id", function() {
        unit_price = $('#product_id option:selected').attr('data-purchaseprice');
        pack_price = $('#product_id option:selected').attr('data-pack_price');
        taxPercentage = $('#product_id option:selected').attr('data-taxPercentage');
        pack_size = $('#product_id option:selected').attr('data-packsize');
        $(".pack_size_text").text(pack_size);
        $("#pack_price").val(pack_price);
        $("#UnitPrice").val(unit_price);
        $("#pack_size").val(pack_size);




        // getItemDetails();
    });
    $('.delete_item').on('click', function () {
        var id = $(this).attr("data_id");
        if (confirm('Do you want to proceed?')) {
            var url = "{{ route('pos.delete_item_from_bill') }}/"+id;
            window.location = url;
        } else {

        }
    });

    function handleChange(e, pack_size) {
        const nextInput = e.closest('td').nextElementSibling.querySelector('input');

        if (nextInput) {
            nextInput.value = e.value * pack_size;
        }


    }
</script>
</body>

</html>
