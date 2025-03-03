<?php
/** @var User $user */
$page = 'participants-index';
global $rootURL;
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Participants - <span class="text-muted">Overview</span></h1>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#participantModal">
            <i class="fas fa-plus"></i>
            Add Participant
        </button>
    </div>
    <div class="row">
        <div class="col">
            <table id="collection" class="table table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th style="text-align: center;">Name</th>
                    <th style="text-align: center;">Phone Number</th>
                    <th style="text-align: center;">Email</th>
                    <th style="text-align: center;">Protocols</th>
                    <th style="text-align: center;">Device EUI</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th style="text-align: center;">Name</th>
                    <th style="text-align: center;">Phone Number</th>
                    <th style="text-align: center;">Email</th>
                    <th style="text-align: center;">Protocols</th>
                    <th style="text-align: center;">Device EUI</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>


    <div class="modal fade" id="participantModal" tabindex="-1" role="dialog" aria-labelledby="participantModalLabel" aria-hidden="true" data-study="null">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="participantModalLabel">Add Participant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" id="participantModalId" value="" />
                        <div class="col-md-6 form-floating mb-3 ms-auto gx-1">
                            <input class="form-control" type="text" id="participantModalFirstName" placeholder="First Name"/>
                            <label for="participantModalFirstName">First Name</label>
                        </div>
                        <div class="col-md-6 form-floating mb-3 ms-auto gx-1">
                            <input class="form-control" type="text" id="participantModalLastName" placeholder="Last Name" />
                            <label for="participantModalLastName">Last Name</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 ms-auto gx-1">
                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="participantModalNumber" placeholder="1112223333" aria-label="Phone Number" aria-describedby="phonenumber" value="">
                                <label for="participantModalNumber">Phone Number</label>
                            </div>
                        </div>
                        <div class="col-sm-6 ms-auto gx-1">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="participantModalEmail" placeholder="name@example.com">
                                <label for="participantModalEmail">Email address</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 form-floating ms-auto gx-1">
                            <select class="selectpicker" id="participantModalGroup" data-live-search="true" title="--Select Protocol Types--" multiple></select>
                        </div>
                        <div class="col-sm-6 ms-auto gx-1">
                            <div class="form-floating mb-3">
                                <input class="form-control" type="text" style="pointer-events: auto;" id="participantModalDevEUI" placeholder="Device EUI" />
                                <label for="participantModalDevEUI">Glucose Device EUI</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 form-floating gx-1">
                            <select class="selectpicker form-control" title="Select Location" id="participantModalLocation" onchange="fillTimeZones()" data-live-search="true"></select>
                        </div>
                        <div class="col-md-6 form-floating gx-1">
                            <select class="selectpicker form-control" title="Select Time Zone" id="participantModalTimeZone" data-live-search="true"></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button id="update-button" type="button" class="btn btn-primary" onclick="add_participant();">Submit Participant</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var collection = {};
        var collectionTable = $('#collection');
        var collectionDataTable = null;

        $(function() {
            collectionDataTable = $('#collection').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "/participants/list",
                    study: "Default"
                },
                order: [[ 0, "asc" ]],
                responsive: true,
                buttons: [
                    'pageLength','colvis'
                ],
                layout: {
                    topStart: 'buttons',
                },
                columnDefs: [
                    {
                        "className": "dt-center",
                        "targets": "_all"
                    },
                    {
                        orderable: false,
                        targets: [3]
                    },
                    {
                        visible: false,
                        targets: [4]
                    }
                ],
                language: {
                    emptyTable: "No participants have been added"
                },
                pagingType: "full_numbers",
                columns: [
                    {
                        data: 'name'
                    },
                    {
                        data: 'number'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'group'
                    },
                    {
                      data: 'dev_eui'
                    },
                    {
                        data: null,
                        render: function ( data ) {
                            return "<button class='btn btn-primary btn-xs' onclick='edit_participant(\"" + data.id + "\");'>" +
                                "<span class='fas fa-user-edit' data-toggle='tooltip' data-placement='left' title='Edit Participant'></span>" +
                                "</button>&nbsp;" +
                                "<button class='btn btn-danger btn-xs' onclick='delete_participant(\"" + data.id + "\");'>" +
                                "<span class='fas fa-user-slash' data-toggle='tooltip' data-placement='left' title='Delete Participant'></span>" +
                                "</button>";
                        }
                    }
                ]
            });
            collectionDataTable.buttons().container().prependTo('#collection_filter');
            collectionDataTable.buttons().container().addClass('float-left');
            $('.dt-buttons').addClass('btn-group-sm');
            $('.dt-buttons div').addClass('btn-group-sm');
            collectionTable.on('xhr.dt', function (e, settings, data) {
                participants = {};
                $.each(data.data, function(i, v) {
                    participants[v.id] = v;
                });
            });

            $.ajax({
                url : '/participants/fill-group-dropdown',
                type : 'GET',

                success : function(data) {
                    data['data'].forEach(function(currentValue, index, arr){
                        $('#participantModalGroup').append("<option value='"+currentValue['name']+"'>"+ currentValue['name'] +"</option>");
                     });
                     $('#participantModalGroup').selectpicker('refresh');
                },
                error : function(request,error) {
                    console.error(error);
                }
            });

            $.ajax({
                url : '<?= $rootURL ?>/participants/fill-location-dropdown',
                type : 'GET',

                success : function(data) {
                    $('#participantModalLocation').find('option').remove();
                    data['data'].forEach(function(currentValue, index, arr){
                        $('#participantModalLocation').append("<option value='"+currentValue+"'>"+ currentValue +"</option>");
                    });
                    $('#participantModalLocation').selectpicker('refresh');
                },
                error : function(request,error) {
                    console.error(error);
                    console.error(request);
                }
            });
        } );

        function fillTimeZones(selected) {
            let location = $("#participantModalLocation").val();
            $("#participantModalTimeZone").empty();
            $.ajax({
                url : '<?= $rootURL ?>/participants/fill-timezone-dropdown',
                type : 'GET',
                data: {
                    'location': location
                },

                success : function(data) {
                    let tzSelect = $('#participantModalTimeZone');
                    tzSelect.selectpicker('destroy');
                    data['data'].forEach(function(currentValue, index, arr){
                        tzSelect.append(`<option value="${currentValue}">${currentValue}</option>`);
                        if (selected === currentValue) {
                            tzSelect.selectpicker('refresh');
                            tzSelect.val(selected);
                        }
                    });
                    tzSelect.selectpicker('refresh');
                },
                error : function(request,error) {
                    console.error(error);
                    console.error(request);
                }
            });
        }

        function clear_participant_form() {
            $('#participantModalFirstName').val('');
            $('#participantModalLastName').val('');
            $('#participantModalNumber').val('');
            $('#participantModalEmail').val('');
            $('#participantModalDevEUI').val('');
            $('#participantModalGroup').val('');
            $('#participantModalGroup').selectpicker('refresh');
            $('#participantModalLocation').val('');
            $('#participantModalLocation').selectpicker('refresh');
            $('#participantModalTimeZone').val('');
            $('#participantModalTimeZone').selectpicker('refresh');
            $('#participantModalLabel').text('Add Participant');
            $('#update-button').text('Submit Participant');
            $('#update-button').attr('onclick', 'add_participant();');
        }

        function edit_participant(id) {
            $('#participantModalLabel').text('Edit Participant');
            $('#update-button').text('Update Participant');
            $('#update-button').attr('onclick', 'update_participant();');
            fill_participant_form(id);
            $('#participantModal').modal('show');
        }

        $('#participantModal').on('hidden.bs.modal', function() {
            clear_participant_form();
        });

        function fill_participant_form(id) {
            $('#participantModalId').val(id);
            $.ajax({
                url: '<?= $rootURL ?>/participants/get-participant',
                type: 'GET',
                data: {
                    'id': id
                },
                success: function(data) {
                    info = data['data'][0];
                    $('#participantModalFirstName').val(info['first_name']);
                    $('#participantModalLastName').val(info['last_name']);
                    $('#participantModalNumber').val(info['number']);
                    $('#participantModalEmail').val(info['email']);
                    $('#participantModalDevEUI').val(info['devEUI']);
                    $('#participantModalGroup').val(info['group']);
                    $('#participantModalGroup').selectpicker('refresh');
                    $('#participantModalLocation').val(info['location']);
                    $('#participantModalLocation').selectpicker('refresh');
                    fillTimeZones(info['time_zone']);
                },
                error: function (xhr, status, error) {
                    showError("Error communicating with the server.");
                    return null;
                }
            });
        }

        function update_participant() {
            let id = $('#participantModalId').val();
            let firstName = $('#participantModalFirstName').val();
            let lastName = $('#participantModalLastName').val();
            if (firstName === "") {
                showError("Please enter participants first name.");
                return false;
            }
            if (lastName === "") {
                showError("Please enter participants last name.");
                return false;
            }

            firstName = firstName.trim();
            lastName  = lastName.trim();

            let number = $('#participantModalNumber').val();
            if (number.length !== 10) {
                showError("Please enter the participants 10 digit phone number.");
                return false;
            }

            if(/[a-z]/i.test(number)){
                showError("Phone numbers cannot contain letters.");
                return false;
            }
            number = '+1' + number;

            // Email
            let email = $('#participantModalEmail').val();

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError("Please enter a valid email address.");
                return false;
            }

            let devEUI = $('#participantModalDevEUI').val().toLowerCase()
            if (devEUI.length > 0 && devEUI.length !== 16){
                showError("Your Glucose device EUI should be 16 characters long. Please check your entry.");
                return false;
            }

            let group = $('#participantModalGroup').val();
            if (group === null || group.length === 0) {
                showError("Please select a protocol type.");
                return false;
            }

            let timeZone = $('#participantModalTimeZone').val();
            if (timeZone == null) {
                showError("Please select the participant's time zone.");
                return false;
            }

            let participant_info = {
                "first_name": firstName,
                "last_name": lastName,
                "number": number,
                "email": email,
                "devEUI": devEUI,
                "group": group,
                "time_zone": timeZone
            }

            let encoded_participant = JSON.stringify(participant_info);
            $.ajax({
                url : '<?= $rootURL?>/participants/update-participant',
                type : 'POST',
                data : {
                    'info':encoded_participant,
                    'id': id,
                    'study': 'Default'
                },
                success : function(data) {

                    $('#participantModal').modal('hide');
                    collectionDataTable.ajax.reload();
                    showSuccess("Participant updated.");
                },
                error : function(request,error){
                    showError("Could not update participant.");
                }
            });
        }

         function add_participant() {
            let firstName = $('#participantModalFirstName').val();
            let lastName = $('#participantModalLastName').val();
            if (firstName === "") {
                showError("Please enter participants first name.");
                return false;
            }
            if (lastName === "") {
                showError("Please enter participants last name.");
                return false;
            }

            firstName = firstName.trim();
            lastName  = lastName.trim();

            let number = $('#participantModalNumber').val();
            if (number.length !== 10) {
                showError("Please enter the participants 10 digit phone number.");
                return false;
            }

            if(/[a-z]/i.test(number)){
                showError("Phone numbers cannot contain letters.");
                return false;
            }
            number = '+1' + number;

            // Email
            let email = $('#participantModalEmail').val();

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError("Please enter a valid email address.");
                return false;
            }

            let devEUI = $('#participantModalDevEUI').val().toLowerCase()
            if (devEUI.length > 0 && devEUI.length !== 16){
                showError("Your Glucose device EUI should be 16 characters long. Please check your entry.");
                return false;
            }

            let group = $('#participantModalGroup').val();
            if (group === null || group.length === 0) {
                showError("Please select a protocol type.");
                return false;
            }

            let timeZone = $('#participantModalTimeZone').val();
            if (timeZone == null) {
                showError("Please select the participant's time zone.");
                return false;
            }

            let participant_info = {
                "first_name": firstName,
                "last_name": lastName,
                "number": number,
                "email": email,
                "devEUI": devEUI,
                "group": group,
                "time_zone": timeZone
            }

            $.ajax({
                url : '<?= $rootURL ?>/participants/add-participant',
                type : 'POST',
                data : {
                    'info': participant_info,
                    'study': 'Default'
                },

                success : function(data) {
                    if (data.success){
                        $('#participantModal').modal('hide');
                        collectionDataTable.ajax.reload();
                        showSuccess("Added Participant");
                    } else {
                        showError("Could not add participant.");
                        showError(data.error_message);
                    }
                },
                error : function(request, error)
                {
                    console.log("Request: "+JSON.stringify(request));
                }
            });
        }

         function delete_participant(id) {
            let isExecuted = confirm("Are you sure you want to delete this participant? This action is not reversible.");
            if (isExecuted) {
                $.ajax({
                    url: '<?= $rootURL ?>/participants/delete-participant',
                    type: 'POST',
                    data: {
                        'id': id
                    },
                    success: function(data) {
                        if (data.success) {
                            showSuccess("Deleted Participant.");
                            collectionDataTable.ajax.reload();
                        } else {
                            showError(data.error_message);
                            return null;
                        }
                    },
                    error: function (xhr, status, error) {
                        showError("Error communicating with the server.");
                        return null;
                    }
                });
            }
        }
    </script>
<?php
include_once __DIR__ . '/../_footer.php';