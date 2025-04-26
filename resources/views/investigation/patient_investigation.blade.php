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

            <!-- Traffic sources -->
            <div class="card">
                <div class="card-body">
                    <form class=" form-submit-event" id="patient_register">
                        <div class="row" style="background-color: #2790b2; padding: 5px">


                            <div class="col-md-2 col-sm-4 mt-1 float-right">
                                <input  type="text" id="mr_number" value=""  class="form-control" placeholder="MR.No" autocomplete="off">
                            </div>
                        </div>


                        <div class="row">
                            <input type="hidden" required id="id" name="id" value="0"
                                   class="form-control" />
                            <input type="hidden" name="list_investigations" id="list_investigations">
                            <input type="hidden" name="invoice_no" id="invoice_no">

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">Contact Number<span
                                            class="asterisk">*</span></label>
                                <input type="text" required id="contact_no" oninput="if(this.value.length > 11) this.value = this.value.slice(0, 11);"  name="contact_no" class="form-control"
                                       placeholder="" autocomplete="off">
                            </div>

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label class="form-label">CNIC (without -)<span class="asterisk"></span></label>
                                <input type="text"  id="cnic" name="cnic"

                                       class="form-control" placeholder="" autocomplete="off"   pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="13" title="Only digits are allowed"/>
                            </div>



                            <div class="col-md-2 col-sm-4 mb-3">
                                <label class="form-label">Patient Name<span class="asterisk">*</span></label>
                                <input type="text"  required id="name" name="name" class="form-control"
                                       placeholder="" autocomplete="off">
                            </div>

                            <div class="col-md-3 col-sm-4 ">
                                <label style="text-align: center; width: 100%; font-weight: bold; color:red">Age</label>
                                <div class="d-flex align-items-center">
                                    <label for="years" class="me-2">Years:</label>
                                    <input type="text" id="age" name="age" class="form-control" style="width: 50px;" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="4" title="Only digits are allowed"/>

                                    <label for="months" class="me-2">Months:</label>
                                    <input type="text" id="months" value="0" name="months" class="form-control" style="width: 50px;" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="2" title="Only digits are allowed"/>

                                    <label for="days" class="me-2">Days:</label>
                                    <input type="text" id="days" value="0" name="days" class="form-control" style="width: 50px;" pattern="\d*" oninput="this.value = this.value.replace(/[^0-9]/g, '');" maxlength="2" title="Only digits are allowed"/>
                                </div>
                            </div>

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label class="form-label">Gender:<span class="asterisk">*</span></label>
                                <select name="gender" id="gender" class="form-control">
                                    <option selected value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">Father/Husband Name<span
                                            class="asterisk">*</span></label>
                                <input type="text" required id="father_husband_name" name="father_husband_name"
                                       class="form-control" placeholder="" autocomplete="off">
                            </div>



                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">District<span class="asterisk">*</span></label>
                                <select name="district_id" required id="district_id" class="form-control">
                                    <option value="">Select District</option>
                                    @foreach ($district as $dist)
                                        <option value="{{ $dist->id }}">{{ $dist->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">location<span class="asterisk">*</span></label>

                                <select name="location_id" required id="location_id" class="form-select">
                                    <option value="">Select Locations</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>


                            </div>

                            <div class="col-md-3 col-sm-4 mb-3" >
                                <label for="nameBasic" class="form-label">Dob<span class="asterisk">*</span></label>
                                <input  type="date" required id="dob" name="dob" class="form-control"
                                        placeholder="" autocomplete="off">
                            </div>

                            <div class="col-md-2 col-sm-4 mb-3">
                                <label for="nameBasic" class="form-label">Discount<span class="asterisk">*</span></label>

                                <select name="discount_percentage" id="discount_percentage" required class="form-select">
                                    <option value="">Select Discount</option>
                                    <option value="5">2</option>
                                    <option value="5">5</option>
                                    <option value="7">7</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="30">30</option>
                                    <option value="40">40</option>
                                    <option value="50">50</option>

                                </select>


                            </div>


                            <div class="col-md-4" >
                                <select  id="investigation_id" class="form-select">
                                    <option value="">Select Investigations...</option>
                                    @foreach ($investigation as $value)
                                        <option sale_price="{{$value->sale_price}}" investigation_name="{{ $value->name }}" value="{{ $value->id }}">{{ $value->name }}</option>
                                    @endforeach
                                </select>
                                <br>
                                <br>
                                <input type="number" class="form-control" id="frequency" value="1" placeholder="frequency">
                            </div>

                            <div class="col-md-8" >
                                <table class="table table-bordered">
                                    <thead>
                                        <tr style="background-color: #b8e8f7">
                                            <th style="width: 30%">Name</th>
                                            <th>Frequency</th>
                                            <th>Amount</th>
                                            <th>Discount</th>
                                            <th>Net Amount</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="inv_body">

                                    </tbody>

                                    <tfoot>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td><span style="font-weight: bold" id="total_bill_amount"></span></td>
                                        <td><span style="font-weight: bold" id="total_bill_discount"></span></td>
                                        <td><span style="font-weight: bold" id="net_bill_amount"></span></td>
                                        <td></td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>






                        </div>

                        <div class="row">
                            <div class="text-left mt-3 float-right">
                                <button class="btn btn-success" style="float: right" id="submit_btn">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>


            {{-- LISTIN PATIENTS --}}
            <div class="card my-5">
                <div class="card-body">
                    <h5 class="card-title">General Patient Investigations</h5>

                        <div class="row">
                            <div class="col-md-2">
                                <label>From Date</label>
                                <input type="date" class="form-control" value="{{date("Y-m-d")}}" id="filter_from_date">
                            </div>
                            <div class="col-md-2">
                                <label>To Date</label>
                                <input type="date" class="form-control" value="{{date("Y-m-d")}}" id="filter_to_date">
                            </div>



                            <div class="col-md-3 mt-4">
                                <div class="btn btn-primary print_all_details">Print</div>
                            </div>
                        </div>

                    <div class="table-responsive" style="min-height: 200px">


                        <table id="patient-list" style="width: 100% !important"
                               class="table table-responsive table-striped data_mf_table ">

                            <thead>
                            <tr>
                                <th style="display: none">Patient</th>
                                <th style="width: 15%">Patient</th>
                                <th>Invoice#</th>
                                <th>Investigation</th>

                                <th>Amount</th>
                                <th>Discount</th>
                                <th>Net Amount</th>
                                <th>Date</th>
                                <th style="width: 10%">Action</th>
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



    <div class="modal fade" id="add_new_record_model" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content form-submit-event" id="from_submit">
                <input type="hidden" id="id" name="id" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Patients</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                     <table class="table table-bordered">
                         <tr>
                             <th>Serial</th>
                             <th>Name</th>
                             <th>Father Name</th>
                             <th></th>
                         </tr>
                         <tbody id="prev_patients">

                         </tbody>


                     </table>

                    </div>




                </div>

            </form>
        </div>
    </div>


    <div class="modal fade my_modal" id="patient_admission_edit_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content form-submit-event" id="cancel_admission_form">
                <input type="hidden" id="cancel_admission_id" name="id" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel1">Update Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">

               -


                    </div>




                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close                </button>
                    <div id="update_record" class="btn btn-primary">Update</div>
                </div>
            </form>
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
        registered_patients = [];
        list_investigation = [];
        setTimeout(function() {
            $(".select2").select2();
            $("#district_id").select2();
            $("#investigation_id").select2();
            $("#location_id").select2();

            $("#edit_consultant_id").select2({dropdownParent: $('.my_modal')});
            $("#edit_opd_type_id").select2({dropdownParent: $('.my_modal')});

            let time = Date.now();
            $("#invoice_no").val(time);
        }, 1000);


        $("body").on("change", "#investigation_id", function(e) {
            setTimeout(function () {
                $("#frequency").focus();
            },100);
        });

        $("body").on("click", ".remove_item", function(e) {
            var id = $(this).attr("item_id");
            removeItem(id);
        });

        $("body").on("change", "#discount_percentage", function(e) {
            load_investigation();
        });

        $('form').on('keydown', function (e) {
            if (e.key === 'Enter') {
                var investigation_id =  $("#investigation_id").val();
                var frequency =  $("#frequency").val();
                if(frequency == '' || frequency == 0){
                    frequency = 1;
                }
                if(investigation_id == ""){
                    alert("Please select investigation");
                    return false;
                }
                let rate = $("#investigation_id").find("option:selected").attr("sale_price");
                let investigation_name = $("#investigation_id").find("option:selected").attr("investigation_name");

                const exists = list_investigation.some(item => item.investigation_id == investigation_id);
                if (!exists) {
                    list_investigation.push({investigation_id:investigation_id,investigation_name:investigation_name,frequency:frequency,rate:rate});
                    load_investigation();
                } else {
                    alert("Investigation already exist");

                }



               // $("#investigation_id").val("").trigger("change");
                $("#investigation_id").select2('open');
                $("#investigation_id").focus();


                e.preventDefault(); // Prevent the default form submission
            }
        });

        function load_investigation(){
            $("#inv_body").html("");
            var discount = $("#discount_percentage").val();
            if(discount == '')
                discount = 0;

            var total_bill_amount = 0;
            var total_discount = 0;
            var net_bill_amount = 0;
            list_investigation.forEach(function (value,key) {
                discount_amount = (value.rate) * (discount/100);
                discount_amount = (discount_amount.toFixed(2)) * value.frequency;
                list_investigation[key]['discount_percentage'] = discount;
                list_investigation[key]['discount_amount'] = discount_amount;

                var net_rate = (value.rate) - (discount_amount);
                 net_rate = (net_rate) * value.frequency;


                total_bill_amount += parseFloat(value.rate);
                total_discount += parseFloat(discount_amount);
                net_bill_amount += parseFloat(net_rate);

               var html = `<tr>
                                <td>${value.investigation_name}</td>
                                <td>${value.frequency}</td>
                                <td>${value.rate * value.frequency}</td>
                                <td>${discount_amount}</td>
                                <td>${net_rate}</td>
                                <td ><a href="javascript:void(0)" class="remove_item" item_id='${value.investigation_id}' style="color:red">X remove</a></td>

                                `;
                $("#inv_body").append(html);
            });

            setTimeout(function () {
                $("#total_bill_amount").html(total_bill_amount);
                $("#net_bill_amount").html(net_bill_amount);
                $("#total_bill_discount").html(total_discount);
                $("#list_investigations").val(JSON.stringify(list_investigation));

            },100);
        }

        function removeItem(id){
            list_investigation = list_investigation.filter(item => item.investigation_id != id);
            load_investigation();
        }




        $("body").on("click", ".print_all_details", function(e) {
            var from_date = $("#filter_from_date").val();
            var to_date = $("#filter_to_date").val();
            var filter_opd_type_id = $("#filter_opd_type_id").val();
            var filter_consultant_id = $("#filter_consultant_id").val();
            if(from_date == ''){
                from_date = 'nill';
            }
            if(to_date == ''){
                to_date = 'nill';
            }
            if(filter_opd_type_id == ''){
                filter_opd_type_id = 0;
            }
            if(filter_consultant_id == ''){
                filter_consultant_id = 0;
            }
            var url = "{{route('pos.print_all_appointments')}}/"+from_date+"/"+to_date+"/"+filter_opd_type_id+"/"+filter_consultant_id;
            var newWindow = window.open(url, '_blank', 'width=1200,height=800');
            newWindow.focus();

        });

        function calculateDOB(years, months, days) {
            // Get the current date
            const currentDate = new Date();

            // Create a new date object for the calculation
            const dob = new Date(currentDate);

            // Subtract years, months, and days
            dob.setFullYear(dob.getFullYear() - years); // Subtract years
            dob.setMonth(dob.getMonth() - months);      // Subtract months
            dob.setDate(dob.getDate() - days);          // Subtract days


            const date = new Date(dob);

            // Format the date as d-m-Y
            const day = String(date.getDate()).padStart(2, '0'); // Get day and add leading zero
            const month = String(date.getMonth() + 1).padStart(2, '0'); // Get month (0-indexed, so add 1) and add leading zero
            const year = date.getFullYear(); // Get the full year

            // Combine into the desired format
            var formattedDate = `${year}-${month}-${day}`;

            return formattedDate;
        }

        function calculateAgeDetails(birthDate) {
            const currentDate = new Date(); // Current date
            const birth = new Date(birthDate); // Convert input to Date object

            // Calculate the differences
            let years = currentDate.getFullYear() - birth.getFullYear();
            let months = currentDate.getMonth() - birth.getMonth();
            let days = currentDate.getDate() - birth.getDate();
            if (days < 0) {
                months -= 1;
                const prevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0); // Last day of the previous month
                days += prevMonth.getDate(); // Add days from the previous month
            }
            if (months < 0) {
                years -= 1;
                months += 12;
            }
            $("#age").val(years);
            $("#months").val(months);
            $("#days").val(days);
        }




        $("body").on("click", "#update_record", function() {
            var id = $("#edit_id").val();
            var opd_type_id =  $("#edit_opd_type_id").val();
            var consultant_id = $("#edit_consultant_id").val();
            if(id == '' || opd_type_id == '' || consultant_id == ''){
                alert("Please Fill all fields correctly");
                return false;
            }

            $.ajax({
                type: 'post',
                url: "{{ route('pos.update_appointment') }}",
                data: {
                    id: id,
                    opd_type_id: opd_type_id,
                    consultant_id: consultant_id,

                    _token: '{{ csrf_token() }}'

                },
                success: function(res) {
                    $("#patient_admission_edit_modal").modal("hide");
                    user_table.ajax.reload();
                }
            })
        });

        $("body").on("keyup", "#days,#months,#age", function() {
            var year = $("#age").val();
            var months = $("#months").val();
            var days = $("#days").val();
            if(year == ''){
                year = 0;
            }
            if(months == ''){
                months = 0;
            }
            if(days == ''){
                days = 0;
            }

            var dob = calculateDOB(year,months,days);
            $("#dob").val(dob);
        });



        $("body").on("change", "#dob", function() {
            calculateAgeDetails($(this).val());
        });
        $("body").on("blur", "#contact_no", function() {


            var contact_no = $("#contact_no").val();
            var id = $("#id").val();
            if ((contact_no.length > 8)) {
                $.ajax({
                    type: 'post',
                    url: "{{ route('pos.get_patient_by_cnic') }}",
                    data: {

                        contact_no: contact_no,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                       // console.log(res);
                        if (res.status) {

                            $("#prev_patients").html('');
                            registered_patients = res.data;
                            if(registered_patients.length > 0){
                                $.each(registered_patients, function(index, value) {
                                    var html = `<tr>
                                            <td>${index + 1}</td>
                                            <td>${value.name}</td>
                                            <td>${value.father_husband_name}</td>
                                            <td><div class="btn btn-success select_patient" data-id='${index}' >Select</div></td>

                                            `;
                                    $("#prev_patients").append(html);

                                });
                                $("#add_new_record_model").modal("show");
                            }
                        }else{
                            registered_patients = [];
                            reset_fields();
                        }

                    }
                });

            }
        });

        $("body").on("blur", "#mr_number", function() {
            var mr_number = $("#mr_number").val();


            var id = $("#id").val();
            if ((mr_number.length > 3 || contact_no.length > 8)) {
                $.ajax({
                    type: 'post',
                    url: "{{ route('pos.get_patient_by_cnic') }}",
                    data: {
                        mr_number: mr_number,

                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        // console.log(res);
                        if (res.status) {

                            $("#prev_patients").html('');
                            registered_patients = res.data;
                            if(registered_patients.length > 0){
                                $.each(registered_patients, function(index, value) {
                                    var html = `<tr>
                                            <td>${index + 1}</td>
                                            <td>${value.name}</td>
                                            <td>${value.father_husband_name}</td>
                                            <td><div class="btn btn-success select_patient" data-id='${index}' >Select</div></td>

                                            `;
                                    $("#prev_patients").append(html);

                                });
                                $("#add_new_record_model").modal("show");
                            }
                        }else{
                            registered_patients = [];
                            reset_fields();
                        }

                    }
                });

            }
        });

        $("body").on("change","#filter_from_date,#filter_to_date,#filter_opd_type_id,#filter_consultant_id",function () {
            user_table.ajax.reload();
        });
        $("body").on("click",".select_patient",function () {
           var index = $(this).attr("data-id");
            var details = registered_patients[index];

            $("#id").val(details.id);
            $("#cnic").val(details.cnic);
            $("#name").val(details.name);
            $("#contact_no").val(details.contact_no);
            $("#district_id").val(details.district_id).trigger('change');
            $("#location_id").val(details.location_id).trigger('change');
            $("#dob").val(details.dob);
            $("#father_husband_name").val(details.father_husband_name);
            $("#g4no").val(details.g4no);
            $("#age").val(details.age);
            $("#gender").val(details.gender);
            //$("#regdate").val(details.formatted_date);
            registered_patients = [];
            $("#add_new_record_model").modal("hide");

        });


        user_table = $('#patient-list').DataTable({
            processing: true,
            serverSide: true,

            pageLength: 20,
            ajax: {
                url: "{{ route('pos.get_list_investigations') }}",
                 data: function (d) {
                     d.from_date = $('#filter_from_date').val();
                     d.to_date = $('#filter_to_date').val();
                     d.opd_type_id = $('#filter_opd_type_id').val();
                     d.consultant_id = $('#filter_consultant_id').val();

                 }
            },

            columns: [
                {
                    data: 'patient.name',
                    visible: false
                },
                {
                    data: null, // Use null because you're creating a custom render
                    name: 'patient',
                    searchable: false, // If you want it searchable, additional backend support is required
                    render: function (data, type, row) {
                        // Combine first_name and last_name to display full_name
                        return '<b style="font-size:12px;color:green;">'+row.patient.name + '</b> <br><b style="font-size:12px;color:red;">' + row.patient.mr_no +'</b>';
                    }
                },


                {
                    data: 'print_invoice_number',
                    name: 'print_invoice_number',
                    searchable: true
                }, {
                    data: 'investigation.name',
                    name: 'investigation.name',
                    searchable: true
                },

                {
                    data: 'sale_price',
                    name: 'sale_price',
                    searchable: true
                },
                {
                    data: 'discount_amount',
                    name: 'discount_amount',
                    searchable: true
                },
                {
                    data: null, // Use null because you're creating a custom render
                    name: 'Net Amount',
                    searchable: false, // If you want it searchable, additional backend support is required
                    render: function (data, type, row) {
                        // Combine first_name and last_name to display full_name
                        return '<b style="color:green;">'+ (row.sale_price - row.discount_amount) + '</b> <br>';
                    }
                },
                {
                    data: 'inv_date',
                    name: 'inv_date',
                    searchable: true
                },





                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
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


        $("body").on("click", ".edit_record", function(e) {
            record_id = $(this).attr("data-id");
            var details = JSON.parse($(this).attr("data-details"));
            $("#edit_id").val(details.id);
            $("#edit_opd_type_id").val(details.opd_type_id).trigger('change');
            $("#edit_consultant_id").val(details.consultant_id).trigger('change');
            $("#patient_admission_edit_modal").modal('show');

        });


        //$("body").on("click", "#submit_btn", function(e) {
        $("#patient_register").on("submit", function(e) {

            e.preventDefault();

            let isValid = true;

            var list_investigations = $("#list_investigations").val();
            if(list_investigations == '[]' || list_investigations == ''){
                alert("Please Add investigations");
                return false;
            }

            // Clear previous error messages
            $(".error-message").remove();
            $(".is-invalid").removeClass("is-invalid");

            // Validate required fields
            $(this).find("[required]").each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    $(this).addClass("is-invalid"); // Highlight invalid field
                    $(this).after(
                        `<span class="error-message text-danger">This field is required.</span>`
                    ); // Show error message
                }
            });

            if (isValid) {

                $("#patient_register").ajaxSubmit({
                    url: '{{ route('pos.save_general_patient_investigation') }}',
                    type: 'post',
                    data: {
                        _token: '{{ csrf_token() }}'

                    },
                    success: function(response) {
                        if(response.status =='empty'){
                            alert(response.message);
                            return false;
                        }
                       // alert(response.appointment_id);
                          var url = "";
                        //window.open(url, '_blank');

                        reset_fields();
                        //window.location.reload();
                        user_table.ajax.reload();
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        //console.log();
                        //alert("Status: " + textStatus); alert("Error: " + errorThrown);
                        alert(JSON.parse(XMLHttpRequest.responseText).message);
                    }
                });
            }
        });

        $("body").on("click", ".delete_record", function(e) {
            var id = $(this).attr("data-id");
            if (confirm('Are you sure to delete this record ?')) {
                $.ajax({
                    type: 'post',
                    url: "{{ route('pos.deactivate_record') }}",
                    data: {
                        id: id,
                        table: "appointments",
                        _token: '{{ csrf_token() }}'

                    },
                    success: function(res) {
                        // user_table.dataTable.reload();
                        window.location.reload();
                    }
                })
            } else {
                alert('Why did you press cancel? You should have confirmed');
            }
        });

        function reset_fields() {
            $("#id").val(0);
            $("#contact_no").val('');
            $("#consultant_id").val('').trigger("change");
            $("#district_id").val('').trigger("change");
            $("#dob").val('');
            $("#father_husband_name").val('');
            $("#g4no").val(0);
            $("#age").val('');
            $("#months").val('');
            $("#days").val('');
            $("#gender").val('');
            $("#id").val('');
            $("#location_id").val('').trigger("change");
            $("#cnic").val('');
            $("#cnic").val('');
            $("#name").val('');
            $("#regdate").val('{{date("Y-m-d")}}');
            //$("#relation_id").val('');
            $("#sc_ref_no").val('');
            let time = Date.now();
            $("#discount_percentage").val('');
            $("#invoice_no").val(time);
            list_investigation = [];
            load_investigation();
        }
    </script>
@endpush
