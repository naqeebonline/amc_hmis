@extends('layouts.'.config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp

@section('content')

    <style>
        .tags {
            list-style: none;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
            width: 95%;
            margin: 0 auto;

        }

        .tags li {
            padding: 0 20px;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .tags li.warning:after {
            background-color: orange;
        }

        .tags li.success:after {
            background-color: green;
        }

        .tags li.danger:after {
            background-color: red;
        }

        .tags li:after {
            content: '';
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 0px;
            width: 10px;
            border-radius: 10px;
            height: 10px;
        }

        .text_height_map{
            line-height: 18px;
        }



    </style>

    <!-- Content -->

    <div class="container-xxl flex-grow-1 px-0">
        <div class="row">
            <div class="col-lg-4 col-md-4 mb-4">
                <label style="color:white;font-weight: bold">From Date</label>
                 <input type="date" class="form-control" id="from_date" value="{{$from_date}}">
            </div>
            <div class="col-lg-4 col-md-4 mb-4">
                <label style="color:white;font-weight: bold">To Date</label>
                <input type="date" class="form-control" id="to_date" value="{{$to_date}}">
            </div>

            <div class="col-lg-4 col-md-4 mb-4">

                <a class="btn btn-primary mt-4" style="color: white; font-weight: bold" href="javascript:void(0)" id="search_dashobard">Search</a>
            </div>
        </div>
        <div class="row">


            <div class="col-lg-12 col-md-12 mt-3">
                <div class="row">
                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                            <span class="avatar-initial bg-label-primary rounded-circle"><i class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{ $data->TotalSale ?? 0 }}</h5>
                                            <small class="text-muted">Total Pharmacy Sale</small>
                                        </div>
                                    </div>
                                    <div id="conversationChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                            <span class="avatar-initial bg-label-primary rounded-circle"><i class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{ ($data->Discount ?? 0) + ($data->invoice_discount ?? 0) }}</h5>
                                            <small class="text-muted">Pharmacy Discount</small>
                                        </div>
                                    </div>
                                    <div id="conversationChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                        <span class="avatar-initial bg-label-warning rounded-circle"><i
                                                    class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{round($data->received_amount)}}</h5>
                                            <small class="text-muted">Pharmacy Cash in Hand</small>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
            </div>



                <div class="row">
                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                            <span class="avatar-initial bg-label-primary rounded-circle"><i class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{ $appointments->total_fees ?? 0 }}</h5>
                                            <small class="text-muted">Total Appointments Fees</small>
                                        </div>
                                    </div>
                                    <div id="conversationChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                            <span class="avatar-initial bg-label-primary rounded-circle"><i class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{ $appointments->total_hospital_share ?? 0 }}</h5>
                                            <small class="text-muted">Hospital Share</small>
                                        </div>
                                    </div>
                                    <div id="conversationChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                        <span class="avatar-initial bg-label-warning rounded-circle"><i
                                                    class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{round($appointments->total_consultant_share)}}</h5>
                                            <small class="text-muted">Consultant Share</small>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                            <span class="avatar-initial bg-label-primary rounded-circle"><i class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{ $investigations->total_inv_amount ?? 0 }}</h5>
                                            <small class="text-muted">Total Investigation Amount</small>
                                        </div>
                                    </div>
                                    <div id="conversationChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                            <span class="avatar-initial bg-label-primary rounded-circle"><i class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{ $investigations->total_discount_amount ?? 0 }}</h5>
                                            <small class="text-muted">Total Discount</small>
                                        </div>
                                    </div>
                                    <div id="conversationChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar">
                                        <span class="avatar-initial bg-label-warning rounded-circle"><i
                                                    class="bx bx-user fs-4"></i></span>
                                        </div>
                                        <div class="card-info">
                                            <h5 class="card-title mb-0 me-2">{{round($investigations->total_inv_amount - $investigations->total_discount_amount)}}</h5>
                                            <small class="text-muted">Investigations Cash in Hand</small>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>



        </div>




    </div>
    <!-- / Content -->


@endsection


@push('scripts')



@endpush

@push('vendor-style')

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
@endpush

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/js/app-custom.js') }}"></script>

            <script type="text/javascript">
                $("body").on("click","#search_dashobard",function () {
                   var from_date = $("#from_date").val();
                   var to_date = $("#to_date").val();
                   window.location = "{{route('pos.retailPharmacyDashboard')}}?from_date="+from_date+"&to_date="+to_date
                });
            </script>



@endpush
