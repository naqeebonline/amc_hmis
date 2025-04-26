
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link rel="stylesheet" href="{{asset('')}}Jameel Noori Nastaleeq Regular.ttf">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.1/css/bootstrap.min.css" />
    <!-- <link rel="stylesheet" href="style.css"> -->

    <style>
        @media print {
            @page {
                margin: 0;
                /* Removes the default margin */
            }

            body {
                margin: 0;
                padding: 15px;
                /* Ensures the body content aligns with the page */
            }
        }

        @font-face {
            font-family: 'CustomFont';
            /* Name of the font */
            src: url('{{asset('')}}Jameel Noori Nastaleeq Regular.ttf') format('truetype');
            /* Path to the .ttf file */
            font-weight: normal;
            /* Optional: define font weight */
            font-style: normal;
            /* Optional: define font style */
        }

        .inv-header .head {
            border-top: 15px solid #333;
            border-bottom: 5px solid #333;
            padding: 10px;
        }

        .inv-header h4 {
            margin: 0;
            font-size: 14px;
        }

        .inv-header ul {
            margin: 0;
            padding-left: 10px;
        }

        .inv-header ul li {
            font-size: 12px;
            margin: 0;
            line-height: 1.3;

        }

        .inv-header ul li:last-child {
            line-height: .7;
        }

        .inv-header p {
            font-size: 12px;
            margin: 0;
        }

        .logo {
            /* height: 40px; */
            object-fit: contain;
        }

        .pt_detail p strong {
            white-space: nowrap;
        }

        .pt_detail p {
            display: flex;
            font-size: 12px;
        }

        .pt_detail span {
            margin-left: 10px;
            width: 50%;
            display: inline-block;
            border-bottom: 1px solid #333;
        }

        .main {
            padding: 30px 20px;
            width: 100%;
            height: 825px;
            margin-top: 10px;
        }

        .side {
            border-right: 1px solid #333 !important;
        }

        .side .rx-logo {
            width: 40px;
            border: 2px solid #333;
            object-fit: contain;
            position: absolute;
            left: calc(100% + 10px);
            bottom: calc(100% - 10px);
        }

        footer {
            border-bottom: 15px solid #333;
            border-top: 5px solid #333;
            position: relative;
            padding: 5px 0;
            /* bottom:10px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 20px); */
            position: relative;
        }

        footer h4 {
            font-size: 28px;
            font-weight: bolder;
            font-family: 'CustomFont', sans-serif;
            padding: 0 20px;
        }

        footer p {
            font-size: 10px;
            color: #000 !important;

        }

        footer a {
            color: #000;
        }

        .ft-sign{
            position: absolute;
            right: 0;
            bottom: calc(100% + 10px);
        }
        .ft-sign p{
            padding-right: 10px;
            font-size: 14px;
            margin-bottom: 5px !important;
        }
    </style>
</head>

<body>

<div class="wrapper">
    <header class="inv-header">
        <div class="head">
            <div class="row">
                <div class="col-4">
                    <img class="logo" width="100%" src="{{asset('')}}amc.jpeg" alt="">
                </div>

                <div class="col-4">
                    <h4>{{$data->consultant->name ?? ""}}</h4>

                    <ul class="list-unstyled">
                        <li>{{ucfirst($data->consultant->consultant_speciality->name) ?? ""}}</li>
                        <li>{{ucfirst($data->consultant->consultant_type->name) ?? ""}}</li>

                    </ul>
                </div>

                <div class="col">
                    <p><strong>{{date('l, F d, Y', strtotime($data->appointment_date))}} <br/> {{date('h:i A', strtotime($data->appointment_date))}}</strong></p>
                    <p><strong>{{ucfirst($data->opd_type->name) ?? ''}}</strong></p>
                </div>

            </div>
        </div>

        <div class="patient_detail pb-3">
            <p class="text-end my-2 me-5 pe-2">AMC-34534534</p>

            <div class="row g-0 pt_detail">
                <div class="name col-6 pe-2">
                    <p class=""><strong style="width: 100px; white-space: nowrap;">Patient Name:</strong> <span
                                style="width: 95%; text-align: center;font-weight: bold">{{ucfirst($data->patient->name) ?? ''}}</span></p>
                </div>

                <div class="gender col-3 pe-2">
                    <p><strong>Gender:</strong> <span style="width: 100%; text-align: center;font-weight: bold">{{ucfirst($data->patient->gender) ?? ''}}</span></p>
                </div>
                <div class="age col-3 ">
                    <p><strong>Age:</strong> <span style="width: 95%; text-align: center;font-weight: bold">
                            @if($data->patient->age !="" && $data->patient->age != 0)
                                {{($data->patient->age)}} Years
                            @elseif($data->patient->months !="" && $data->patient->months != 0)
                                {{($data->patient->months)}} Months
                            @else
                                {{($data->patient->days)}} days
                            @endif
                        </span></p>
                </div>

            </div>

        </div>

    </header>

    <div class="main">
        <div class="row h-100 ">
            <div class="col-3 side border-end position-relative">
                <img class="rx-logo" src="{{asset('')}}rx.png" alt="">
            </div>
            <div class="col-9"></div>
        </div>
    </div>



    <footer class="footer">
        <div class="text-end ft-sign">
            <p class="m-0"><strong>Sign & Stamp</strong></p>
            <p class="m-0"><strong>Not Valid For Court Purpose</strong></p>
        </div>
        <div class="row align-items-center">
            <div class="col-8  ">
                <h4 class="my-0">اکاخیل میڈیکل کمپلیکس نزد ولید فلنگ سٹیشن گاڑمنارہ ضلع وتحصیل صوابی</h4>
            </div>
            <div class="col-4">
                <p class="my-0"><strong>Website: <a href="#">www.amch.org.pk</a></strong></p>
                <p class="my-0"><strong>Mob: 0938-481111 , 0316-8481111</strong></p>
                <p class="my-0"><strong>Email: Info@amch.org.pk</strong></p>
            </div>
        </div>
    </footer>



</div>


</body>
<script>
    window.onload = function() {
        window.print(); // Automatically triggers the print dialog
    };
    window.onafterprint = function() {
        window.close(); // Close the window after printing
    };
</script>
</html>
