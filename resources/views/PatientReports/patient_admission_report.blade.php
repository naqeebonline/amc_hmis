@extends('layouts.' . config('settings.active_layout'))
@php $app_id = config('settings.app_id') @endphp

@push('stylesheets')
    <style>
        .table> :not(caption)>*>* {
            padding: 5px;
        }
    </style>

    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">


            {{-- LISTIN PATIENTS --}}
            <div class="card my-5">
                <div class="card-body">
                    <h5 class="card-title">All Patients</h5>
                    @if(in_array(auth()->user()->roles->pluck('name')[0],["Super Admin","Sehat Card Focal Person"]))
                    <div class="row">
                         <div class="col-md-2">
                             <label>From Date</label>
                             <input type="date" class="form-control" id="from_date">
                         </div>
                         <div class="col-md-2">
                             <label>To Date</label>
                             <input type="date" class="form-control" id="to_date">
                         </div>

                        <div class="col-md-2">
                             <label>Consultant</label>
                            <select name="consultant_id"  id="consultant_id" class="form-select">
                                <option value="">View All</option>
                                @foreach ($consultant as $value)
                                    <option date_consultant_share="{{$value->share_percentage}}" value="{{ $value->id }}">{{ $value->name }}</option>
                                @endforeach
                            </select>
                         </div>

                        <div class="col-md-2">
                             <label>Procedure Type</label>
                            <select class="form-select" id="filter_by_procedure_type">
                                <option value="">View All...</option>
                                @foreach($procedure_type as $key => $value)
                                    <option value="{{$value->id}}">{{$value->name}}</option>
                                @endforeach
                            </select>
                         </div>
                         <div class="col-md-3 mt-4">
                             <div class="btn btn-primary print_all_details">Print</div>
                         </div>
                     </div>
                    @endif
                    <div class="table-responsive" style="min-height: 200px">

                        <table id="patient-list"
                            class="table table-responsive table-striped data_mf_table table-condensed">
                            <thead>
                                <tr>
                                    <th>Patient MR no.</th>
                                    <th>Patient Name</th>
                                    <th>Sehat Card Refrence No</th>

                                    <th>Guardian Name</th>
                                    <th>Consultant</th>
                                    <th>Procedure Type</th>
                                    <th>Ward Name</th>
                                    <th>Bed no.</th>

                                    <th>Admission Date</th>
                                    <th>Discharge Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <!-- /traffic sources -->
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/libs/datatables/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-responsive/datatables.responsive.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
    <script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.form.min.js') }}"></script>
    <script>
        user_table = '';
        $("body").on("click", ".print_all_details", function(e) {
            var from_date = $("#from_date").val();
            var to_date = $("#to_date").val();
            var procedure_type_id = $("#filter_by_procedure_type").val();
            var consultant_id = $("#consultant_id").val();
            if(from_date == ''){
                from_date = "nill";
            }

            if(to_date == ''){
                to_date = 'nill';
            }
            if(procedure_type_id == ''){
                procedure_type_id = 0;
            }
            if(consultant_id == ''){
                consultant_id = 0;
            }

            var url = "{{route('pos.print_patient_admission_report')}}/"+from_date+"/"+to_date+"/"+consultant_id+"/"+procedure_type_id;
            var newWindow = window.open(url, '_blank', 'width=1200,height=800');
            newWindow.focus();

        });

        setTimeout(function() {
            $("#filter_by_procedure_type").select2();
            $("#consultant_id").select2();
        }, 1000);
        $("body").on("change","#filter_by_procedure_type",function () {
            user_table.ajax.reload();
        });
        $("body").on("change","#consultant_id",function () {
            user_table.ajax.reload();
        });

        $("body").on("change", "#from_date,#to_date", function(e) {
            var from_date = $("#from_date").val();
            var to_date = $("#to_date").val();
            if (new Date(from_date) > new Date(to_date)) {
                alert("From date cannot be greater than To date.");
                return false;
            }
            if(from_date != '' && to_date != ""){
                user_table.ajax.reload();
            }


        });

        user_table = $('#patient-list').DataTable({
            processing: true,
            serverSide: true,

            pageLength: 100,
            ajax: {
                url: "{{ route('pos.list_all_patients') }}",
                data: function (d) {

                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                    d.procedure_type_id = $('#filter_by_procedure_type').val();
                    d.consultant_id = $('#consultant_id').val();



                }


            },

            columns: [

                {
                    data: 'patient.mr_no',
                    name: 'patient.mr_no',
                    searchable: true
                },
                {
                    data: 'patient.name',
                    name: 'patient.name',
                    searchable: true
                },
                 {
                    data: 'sc_ref_no',
                    name: 'sc_ref_no',
                    searchable: true
                },

                {
                    data: 'guardian_name',
                    name: 'guardian_name',
                    searchable: true
                },
                {
                    data: 'consultant.name',
                    name: 'consultant.name',
                    searchable: true
                },
                {
                    data: 'procedure_type.name',
                    name: 'procedure_type.name',
                    searchable: true
                },
                {
                    data: 'ward.name',
                    name: 'ward.name',
                    searchable: true
                },
                {
                    data: 'bed.name',
                    name: 'bed.name',
                    searchable: true
                },



                {
                    data: 'admission_date',
                    name: 'admission_date',
                    searchable: true
                },
                {
                    data: 'discharge_date',
                    name: 'discharge_date',
                    searchable: true
                },
                {
                    data: 'admission_status',
                    name: 'admission_status',
                    searchable: true
                },
                {
                    data: 'actions',
                    name: 'actions',
                    searchable: true
                },

            ],

            responsive: true,
            processing: true,
            serverSide: true,
            searching: true,
            sorting: true,
            paging: true,
            dom: 'Bfrtip',
            // buttons: [
            //     'copy', 'csv', 'excel', 'pdf', 'print'
            // ]
        });






    </script>
@endpush
