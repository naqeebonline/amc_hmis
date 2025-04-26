<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        body {
            font-family: "Poppins", serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }

        strong,
        p {
            font-size: 10px;
            margin: 0;
            font-weight: 600;
            line-height: 1.2;
        }

        table {
            border-collapse: collapse;
        }

        table th,
        td {
            padding: 0 3px;
            border: 1px solid #000;
            font-size: 10px;
        }

        table th {
            background-color: #B2BBC3;
            font-weight: 600;
            padding: 3px;
        }

        .text-blue{
            color: rgb(0, 151, 211);
        }

        .text-red {
            color: rgb(231, 12, 12);
        }
        body{
            padding: 10px 20px;
            background-color: #fff;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0px auto;
            /* padding: 10px 20px; */
            background: #fff;
            /* border: 1px solid #ddd; */
            border-radius: 5px;
        }

        header {
            display: flex;
            justify-content: space-between;
            padding: 0px 0px;
            padding-bottom: 0px;
            width: 100%;

        }

        .company-details {
            width: 50%;
        }

        .header-right{
            width: 50%;
        }
        .header-right table{
            margin-left: auto;
        }

        .company-details h1 {
            margin: 0;
            font-size: 20px;
            color: #333;
            font-weight: 500;
            line-height: 1;
            margin-bottom: 3px;
        }

        .company-details p {
            margin: 0;
            font-weight: 600;
            text-transform: uppercase;
        }

        .logo img {
            max-width: 80px;
            margin-bottom: 10px;
        }

        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .invoice-details>div{
            width: 50%;
        }


        .to-details h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            line-height: 1;
            margin-bottom: 3px;
        }

        .to-details p {
            margin: 0;

        }


        .invoice-meta table tbody tr td:first-child {
            background-color: #B2BBC3;
            min-width: 120px;
            font-weight: 600;
        }


        .text-center {
            text-align: center !important;
        }

        .items table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items table th,
        .items table td {
            text-align: left;
            font-size: 10px;
        }

        .items table th {}

        .items table td {
            padding: 2px 3px;

        }



        .totals {
            display: flex;
            justify-content: end;
        }

        .totals table strong {
            font-size: 10px !important;
        }

        .totals table td:first-child {
            background-color: #B2BBC3;
        }

        .totals table td:last-child {
            width: 110px;
            font-weight: 600;
        }

        .totals-row {
            margin: 5px 0;
        }

        .total-balance {
            font-size: 18px;
            color: #d00;
        }

        footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>
<div class="invoice-container">
    <header>
        <div class="company-details">
            <h1 style="text-align: center;margin-left: 50%;width: 100%;">Akakhel Medical Complex</h1>

        </div>

        <div class="invoice-meta header-right">


        </div>
    </header>



    <section class="items">
        <table>
            <tr>
                <td style="font-weight: bold; width: 8%">From Date:</td>
                <td style=" width: 10%">{{$from_date ?? ''}}</td>
                <td style="font-weight: bold; width: 8%">To Date</td>
                <td style="width: 10%">{{$to_date ?? ''}}</td>
                <td style="font-weight: bold; width: 8%">Consultant:</td>
                <td style="width: 15%">{{$consultant_name ?? ''}}</td>
                <td style="font-weight: bold; width: 8%">Procedure</td>
                <td style="width: 15%">{{$procedure_name}}</td>

            </tr>
        </table>

        <table style="margin-top: 5px">
            <thead>
            <tr>
                <th style="width: 5%;">S.No</th>
                <th style="width: 15%; ">Patient Name</th>
                <th style="width: 10%;">Mr.No</th>
                <th style="width: 7%;">Referral#</th>
                <th style="width: 13%;">Consultant Name</th>
                <th style="width: 10%;">Procedure</th>
                <th style="width: 10%;">Admission Date</th>
                <th style="width: 10%;">Discharge Date</th>
                <th style="width: 10%;">Status</th>

            </tr>
            </thead>
            <tbody>
            @foreach($patients as $key => $value)
                <tr>
                    <td >{{ $key + 1 }}</td>
                    <td >{{$value->patient->name ?? ''}}</td>
                    <td >{{$value->patient->mr_no ?? ''}}</td>
                    <td >{{$value->sc_ref_no}}</td>
                    <td >{{$value->consultant->name ?? ''}}</td>
                    <td >{{$value->procedure_type->name ?? ''}}</td>
                    <td >{{date("d-m-Y h:i A",strtotime($value->admission_date))}}</td>
                    <td>{{date("d-m-Y h:i A",strtotime($value->discharge_date))}}</td>
                    <td >{{$value->admission_status}}</td>

                </tr>
            @endforeach

            </tbody>

        </table>
    </section>




</div>
</body>

</html>