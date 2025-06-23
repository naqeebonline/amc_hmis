<!doctype html>
<head>
	<meta charset="utf-8">
	<title>POS</title>
	<link rel="stylesheet" href="{{asset('assets/css/print_style.css')}}">
</head>
<style>
    h6{
        margin: 3px 0;
        padding: 2px 0;
    }


</style>

 <script>
       /* window.onload = function () {
            window.print(); // Open the print dialog when the page finishes loading
            setTimeout(function() {
                window.print(); // Second print after a short delay
            }, 1000);
        };*/
    </script>
<body>

	<div class="wrap">

		<div class="logo">
			<h2 style="font-size: 22px">Akakhel Medical Complex</h2>
			<small style="font-size: 13px">{{date("d-m-Y h:i A")}} </small>
		</div>




	<div class="main">
        <h6 style="font-size: 14px">Name: {{ $patient->name ?? '' }} </h6>

        <h6 style="font-size: 14px">Printed By: {{ auth()->user()->name ?? "" }}</h6>
		   <table style="margin-top: 5px">
                <thead>
                    <tr>
                        <th style="width: 5%; font-size: 14px">S.No</th>
                        <th style="width: 45%; font-size: 14px">Description</th>
                        <th style="width: 10%; font-size: 14px">Qty</th>
                        <th style="width: 10%; font-size: 14px">Price</th>
                        <th style="width: 10%; font-size: 14px">Amount</th>

                    </tr>
                </thead>
                <tbody>
                <?php
                $i=1; $taxAmount = 0; $totalAmount = 0;

                foreach($data as $d){
                    $taxAmount = ($taxAmount) + ($d->taxAmount);
                    $totalAmount = ($totalAmount) + ($d->totalAmount);
                    $quantity = ($d->Quantity) - ($d->ReturnQuantity);
                    ?>
                    <tr>
                        <td style="font-weight: bold !important; font-size: 12px"><?php echo $i; $i++;;?></td>
                        <td style="font-weight: bold !important; font-size: 12px"><?php echo $d->product->ProductName;?></td>
                        <td style="font-weight: bold !important; font-size: 12px">
                            <?php echo $quantity;?>
                            <?php if($d->ReturnQuantity > 0): ?>
                                Return: (<?php echo $d->ReturnQuantity ?>)
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold !important; font-size: 12px"><?php echo $d->UnitePrice;?></td>
                        <td style="font-weight: bold !important; font-size: 12px"><?php echo $d->totalAmount;?></td>

                    </tr>
                <?php } ?>

                <tr>
                    <th style="width: 5%; font-size: 14px; border-top: 2px solid black;"></th>
                    <th style="width: 45%; font-size: 14px; border-top: 2px solid black;"></th>
                    <th style="width: 10%; font-size: 14px; border-top: 2px solid black;"></th>
                    <th style="width: 10%; font-size: 12px; border-top: 2px solid black;">Total: </th>
                    <th style="width: 10%; font-size: 12px; border-top: 2px solid black;">{{$totalAmount}}</th>

                </tr>
                <tr>
                    <th style="width: 5%; font-size: 14px"></th>
                    <th style="width: 45%; font-size: 14px"></th>
                    <th style="width: 10%; font-size: 14px"></th>
                    <th style="width: 10%; font-size: 12px">Discount: </th>
                    <th style="width: 10%; font-size: 12px">{{$record->Discount}}</th>

                </tr>

                <tr>
                    <th style="width: 5%; font-size: 14px"></th>
                    <th style="width: 45%; font-size: 14px"></th>
                    <th style="width: 10%; font-size: 14px"></th>
                    <th style="width: 10%; font-size: 14px">Amount: </th>
                    <th style="width: 10%; font-size: 14px">{{round(($totalAmount) - ($record->Discount))}}</th>

                </tr>

                </tbody>

            </table>


	</div>
        <br>
        <br>
        <strong style="font-size: 12px; text-align: center !important;"></strong>
        <br>
	</div>



</body>
</html>