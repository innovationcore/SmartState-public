<?php
/** @var UserSession $userSession */
$page = 'participants-index';
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Participants - <span class="text-muted">Overview</span></h1>
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#participantModal">
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
                    <th style="text-align: center;">Protocol Types</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th style="text-align: center;">Name</th>
                    <th style="text-align: center;">Phone Number</th>
                    <th style="text-align: center;">Protocol Types</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>


    <div class="modal fade" id="participantModal" tabindex="-1" role="dialog" aria-labelledby="participantModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="participantModalLabel">Add Participant</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" id="participantModalId" value="" />
                        <div class="col-sm-12 mb-3 form-floating">
                            <input class="form-control" type="text" style="pointer-events: auto;" id="participantModalFirstName" placeholder="First Name" />
                            <label for="participantModalFirstName">First Name</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <input class="form-control" type="text" style="pointer-events: auto;" id="participantModalLastName" placeholder="Last Name" />
                            <label for="participantModalLastName">Last Name</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <input class="form-control" type="text" style="pointer-events: auto;" id="participantModalNumber" placeholder="Phone Number" value="+1" />
                            <label for="participantModalNumber">Phone Number</label>
                            <p>Note: Please do not put any hyphens in the phone number.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <input class="form-control" type="text" style="pointer-events: auto;" id="participantModalDevEUI" placeholder="Device EUI" />
                            <label for="participantModalDevEUI">Glucose Device EUI</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                        <select class="selectpicker" data-width="fit" id="participantModalGroup" data-none-selected-text="Select Protocol" multiple data-live-search="true" data-live-search-placeholder="Search">
                        </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <select id="participantModalLocation" style="width: 200px;" onchange="fillTimeZones()">
                                <option value="" disabled selected>--Select Location--</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <select id="participantModalTimeZone" style="width: 200px;">
                                <option value="" disabled selected>--Select Time Zone--</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
            let user_role = <?php echo $userSession->getUser()->getRole(); ?>;
            if (user_role == 2) {
                collectionDataTable = $('#collection').DataTable({
                    serverSide: false,
                    ajax: {
                        url: "/participants/list"
                    },
                    order: [[ 0, "asc" ]],
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: [
                        'pageLength', 'colvis'
                    ],
                    columnDefs: [
                        {"className": "dt-center", "targets": "_all"}
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
                            data: null,
                            render: function ( data ) {
                                var html = "<button class='btn btn-danger btn-xs' onclick='delete_participant(\"" + data.id + "\");'>" +
                                        "<span class='fas fa-user-slash' data-toggle='tooltip' data-placement='left' title='Delete Participant'></span>" +
                                        "</button>";
                                return html;
                            }
                        }
                    ]
                });
                collectionDataTable.buttons().container().prependTo('#collection_filter');
                collectionDataTable.buttons().container().addClass('float-left');
                $('.dt-buttons').addClass('btn-group-sm');
                $('.dt-buttons div').addClass('btn-group-sm');
                collectionTable.on('xhr.dt', function (e, settings, data) {
                    if (data == null || data.data == null) {
                        return;
                    }
                    participants = {};
                    $.each(data.data, function(i, v) {
                        participants[v.id] = v;
                    });
                });
            } else {
                collectionDataTable = $('#collection').DataTable({
                    serverSide: false,
                    ajax: {
                        url: "/participants/list"
                    },
                    order: [[ 0, "asc" ]],
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: [
                        'pageLength', 'colvis'
                    ],
                    columnDefs: [
                        {"className": "dt-center", "targets": "_all"}
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
                            data: 'group'
                        },
                        {
                            data: null,
                            render: function ( data ) {
                                var html = "<button class='btn btn-primary btn-xs' onclick='edit_participant(\"" + data.id + "\");'>" +
                                        "<span class='fas fa-user-edit' data-toggle='tooltip' data-placement='left' title='Edit Participant'></span>" +
                                        "</button>&nbsp;" +
                                        "<button class='btn btn-danger btn-xs' onclick='delete_participant(\"" + data.id + "\");'>" +
                                        "<span class='fas fa-user-slash' data-toggle='tooltip' data-placement='left' title='Delete Participant'></span>" +
                                        "</button>";
                                return html;
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
            }
       });

        $(document).ready( function () {
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
                url : '/participants/fill-location-dropdown',
                type : 'GET',

                success : function(data) {
                    data['data'].forEach(function(currentValue, index, arr){
                        $('#participantModalLocation').append("<option value='"+currentValue+"'>"+ currentValue +"</option>");
                     });
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
            $('#participantModalTimeZone').append("<option value='' disabled selected>--Select Time Zone--</option>");
            $.ajax({
                url : '/participants/fill-timezone-dropdown',
                type : 'GET',
                data: {
                    'location': location
                },

                success : function(data) {
                    data['data'].forEach(function(currentValue, index, arr){
                        $('#participantModalTimeZone').append("<option value='"+currentValue+"'>"+ currentValue +"</option>");
                        if (selected == currentValue) {
                            $('#participantModalTimeZone').val(selected);
                        }
                     });
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
            $('#participantModalNumber').val('+1');
            $('#participantModalDevEUI').val('');
            $('#participantModalGroup').val('');
            $('#participantModalGroup').selectpicker('refresh');
            $('#participantModalLocation').val('');
            $('#participantModalTimeZone').val('');
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
                url: '/participants/get-participant',
                type: 'GET',
                data: {
                    'id': id
                },
                success: function(data) {
                    info = data['data'][0];
                    $('#participantModalFirstName').val(info['first_name']);
                    $('#participantModalLastName').val(info['last_name']);
                    number = "+1" + info['number'];
                    $('#participantModalNumber').val(number);
                    $('#participantModalDevEUI').val(info['devEUI']);
                    $('#participantModalGroup').val(info['group']);
                    $('#participantModalGroup').selectpicker('refresh');
                    $('#participantModalLocation').val(info['location']);
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
            let number = $('#participantModalNumber').val();
            let devEUI = $('#participantModalDevEUI').val().toLowerCase();
            let group = $('#participantModalGroup').val();
            let timeZone = $('#participantModalTimeZone').val();

            if (firstName == "") {
                showError("First name must not be blank");
                return false;
            }
            if (lastName == "") {
                showError("Last name must not be blank");
                return false;
            }
            
            if (number.length !== 12) {
                showError("Phone number must be of format '+1XXXXXXXXXX'");
                return false;
            }
            if(/[a-z]/i.test(number)){
                showError("Phone number cannot contain letters");
                return false;
            }

            if (devEUI == ""){
                showError("Please enter your Glucose device EUI number.");
                return false;
            }
            if (devEUI.length !== 16){
                showError("Your Glucose device EUI should be 16 characters long. Please check your entry.");
                return false;
            } 
            
            if (group == null) {
                showError("You must select a protocol type");
                return false;
            }
            
            if (timeZone == null) {
                showError("You must select a time zone");
                return false;
            }
            let participant_info = {
                "first_name": firstName,
                "last_name": lastName,
                "number": number,
                "devEUI": devEUI,
                "group": group,
                "time_zone": timeZone
            }

            let encoded_participant = JSON.stringify(participant_info);
            $.ajax({
                url : '/participants/update-participant',
                type : 'POST',
                data : {
                    'info':encoded_participant,
                    'id': id
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
            let group = $('#participantModalGroup').val();
            let number = $('#participantModalNumber').val();
            let devEUI = $('#participantModalDevEUI').val().toLowerCase();
            let timeZone = $('#participantModalTimeZone').val();

            if (firstName == "") {
                showError("First name must not be blank");
                return false;
            }
            if (lastName == "") {
                showError("Last name must not be blank");
                return false;
            }
           
            if (number.length !== 12) {
                showError("Phone number must be of format '+1XXXXXXXXXX'");
                return false;
            }
            if(/[a-z]/i.test(number)){
                showError("Phone number cannot contain letters");
                return false;
            }

            if (devEUI == ""){
                showError("Please enter your Glucose device EUI number.");
                return false;
            }
            if (devEUI.length !== 16){
                showError("Your Glucose device EUI should be 16 characters long. Please check your entry.");
                return false;
            } 

            if (group == null) {
                showError("You must select a protocol type");
                return false;
            }
            
            if (timeZone == null) {
                showError("You must select a time zone");
                return false;
            }
            let participant_info = {
                "first_name": firstName,
                "last_name": lastName,
                "number": number,
                "devEUI": devEUI,
                "group": group,
                "time_zone": timeZone
            }
            $.ajax({
                    url : '/participants/add-participant',
                    type : 'POST',
                    data : {'info': participant_info},

                    success : function(data) {
                        if (data.success){
                            $('#participantModal').modal('hide');
                            collectionDataTable.ajax.reload();
                            showSuccess("Added Participant");
                        } else {
                            showError("Could not add participant.");
                            console.error(data.error_message);
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
                    url: '/participants/delete-participant',
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