<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>POS</title>
    <link rel="stylesheet" href="{{ asset('assets/css/print_style.css') }}">
    <style>
        h6 {
            margin: 3px 0;
            padding: 2px 0;
        }

        @media print {

            .cut-break {
                page-break-after: always;
            }
        }
    </style>
    <script>
        window.onload = function() {
            // window.print(); // Auto-trigger print dialog
        };
    </script>
</head>

<body>

    <div class="wrap">

        <div class="logo text-center">
            <h2 style="font-size: 22px">{{ config('app.COMPANY_NAME') }}</h2>
            <p style="font-size: 13px; font-weight:bold;">{{ config('app.BRANCH_NAME') }}</p>
            <small style="font-size: 13px">{{ date("d-m-Y h:i A") }}</small>
        </div>

        <div class="main">
            <h6 style="font-size: 14px">Invoice#: {{ $record->InvoiceNo ?? "" }} | Created At: {{ $record->CreatedAt ?? "" }}</h6>
            {{--<h6 style="font-size: 14px">Name: {{ $patient->name ?? '' }}</h6>--}}
            @if($appointment_patient_name !='')
            <h6 style="font-size: 14px">{!! $appointment_patient_name !!} </h6>
            @endif

            <h6 style="font-size: 14px">Created By: {{ $record->created_by->name ?? "" }} | Printed By: {{ auth()->user()->name ?? "" }}</h6>

            <table style="margin-top: 5px">
                <thead>
                    <tr>
                        <th style="width: 5%; font-size: 14px">S.No</th>
                        <th style="width: 35%; font-size: 14px">Description</th>
                        <th style="width: 8%; font-size: 14px">Qty</th>
                        <th style="width: 10%; font-size: 14px">Price</th>
                        <th style="width: 8%; font-size: 14px">Disc%</th>
                        <th style="width: 10%; font-size: 14px">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $taxAmount = 0;
                    $totalAmountBeforeDiscount = 0; // Total amount before percentage discounts are applied
                    $totalDiscountAmount = 0; // Sum of all item discount amounts
                    foreach ($data as $d) {
                        $taxAmount += $d->taxAmount;
                        $quantity = max(0, $d->Quantity - $d->ReturnQuantity); // Active quantity after returns
                        $discountPercentage = $d->discount_percentage ?? 0;

                        // Calculate line amount before discount (Active Quantity × Unit Price)
                        $lineAmountBeforeDiscount = max(0, $quantity * $d->UnitePrice);
                        $totalAmountBeforeDiscount += $lineAmountBeforeDiscount;

                        // Use the updated discount amount from database (already proportional after returns)
                        $itemDiscountAmount = 0;
                        if ($quantity > 0) {
                            if (isset($d->discount_percentage_amount) && $d->discount_percentage_amount > 0) {
                                // Use the stored discount amount (already updated proportionally by return_pharmacy_item)
                                $itemDiscountAmount = $d->discount_percentage_amount;
                            } else if (isset($d->itemDiscountAmount) && $d->itemDiscountAmount > 0) {
                                // Fallback: use itemDiscountAmount if available
                                $itemDiscountAmount = $d->itemDiscountAmount;
                            } else if ($discountPercentage > 0) {
                                // Calculate discount from percentage for active quantity
                                $itemDiscountAmount = ($lineAmountBeforeDiscount * $discountPercentage) / 100;
                            }
                        }
                        // If quantity is 0, discount is automatically 0

                        $totalDiscountAmount += max(0, $itemDiscountAmount); // Ensure non-negative discount

                        // Calculate amount after discount for display in Amount column
                        $lineAmountAfterDiscount = max(0, $lineAmountBeforeDiscount - $itemDiscountAmount); // Ensure non-negative
                    ?>
                        <tr>
                            <td style="font-weight: bold !important; font-size: 12px"><?php echo $i++; ?></td>
                            <td style="font-weight: bold !important; font-size: 12px"><?php echo $d->product->ProductName; ?></td>
                            <td style="font-weight: bold !important; font-size: 12px">
                                <?php echo $quantity; ?>
                                <?php if ($d->ReturnQuantity > 0): ?>
                                    Return: (<?php echo $d->ReturnQuantity ?>)
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: bold !important; font-size: 12px"><?php echo number_format($d->UnitePrice, 2); ?></td>
                            <td style="font-weight: bold !important; font-size: 12px"><?php echo number_format($discountPercentage, 0); ?>%</td>
                            <td style="font-weight: bold !important; font-size: 12px"><?php echo number_format($lineAmountAfterDiscount, 2); ?></td>
                        </tr>
                    <?php } ?>

                    <tr>

                        <th colspan="4" style="font-size: 12px; border-top: 2px solid black; text-align:right;">Total Amount:</th>
                        <th colspan="2" style="font-size: 12px; border-top: 2px solid black; text-align:right;">{{ number_format($totalAmountBeforeDiscount, 2) }}</th>
                    </tr>
                    <tr>

                        <th colspan="4" style="font-size: 12px; text-align:right;">Total Discount Amount:</th>
                        <th colspan="2" style="font-size: 12px; text-align:right;">{{ number_format($totalDiscountAmount, 2) }}</th>
                    </tr>
                    <tr>

                        <th colspan="4" style="font-size: 12px; text-align:right;">Invoice Discount:</th>
                        <th colspan="2" style="font-size: 12px; text-align:right;">{{ number_format($record->invoice_discount ?? 0, 2) }}</th>
                    </tr>

                    <tr>

                        <th colspan="4" style="font-size: 14px; text-align:right;">Final Result:</th>
                        <th colspan="2" style="font-size: 14px; text-align:right;">{{ number_format($totalAmountBeforeDiscount - $totalDiscountAmount - ($record->invoice_discount ?? 0), 2) }}</th>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="display: table; margin: 0 auto;">{!! DNS2D::getBarcodeHTML(config("app.LIVE_URL"), 'QRCODE', 3, 3) !!}</div>
        <p style="font-size: 12px; font-weight: bold; text-align: center !important;">Thank You For Visiting</p>
        <p style="font-size: 12px; font-weight: bold; text-align: center !important;">Note: Returns are accepted only with the original receipt/invoice in 48 Hours.</p>
        <br>
    </div>



</body>

</html>