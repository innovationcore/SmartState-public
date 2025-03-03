<?php
/** @var User $user */
$page = "users";
global $rootURL;
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Users</h1>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="fas fa-user-plus"></i> Add User
        </button>
    </div>
    <div class="row">
        <div class="col">
            <table id="users" class="table table-striped table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Vanity Email</th>
                    <th>Phone Number</th>
                    <th>Organization</th>
                    <th>Affiliation</th>
                    <th>IDP</th>
                    <th>Roles</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Vanity Email</th>
                    <th>Phone Number</th>
                    <th>Organization</th>
                    <th>Affiliation</th>
                    <th>IDP</th>
                    <th>Roles</th>
                    <th>Actions</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">User Management</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" id="user-id" value="" />
                        <div class="col-md-12">
                            <div class="input-group mb-3">
                                <span class="input-group-text" id="user-email-label">Email</span>
                                <input id="user-email" type="text" class="form-control" placeholder="abc123@uky.edu" aria-label="Email" aria-describedby="user-email">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="input-group mb-3">
                                <label class="input-group-text" for="user-roles">Roles</label>
                                <select id="user-roles" class="form-select" aria-label="Select User Role" title="Select User Role"></select>
                            </div>
                        </div>
                    </div>
                    <div class="study-admin-extras" style="display:none">
                        <hr>
                        <h6>Please enter your phone number and timezone to receive alerts about participants.</h6>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="input-group mb-3">
                                    <span class="input-group-text" id="user-number-label">Phone Number</span>
                                    <input id="user-number" type="text" class="form-control" placeholder="5555555555" aria-label="Phone Number" aria-describedby="user-number">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-floating">
                                <select class="selectpicker form-control" title="Select Location" id="user-location" onchange="fillTimeZones()" data-live-search="true"></select>
                            </div>
                            <div class="col-md-6 form-floating">
                                <select class="selectpicker form-control" title="Select Time Zone" id="user-timezone" data-live-search="true"></select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submit_user();">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var users_table = $('#users');
        var users_datatable = null;
        var users = {};
        var userModal = $('#userModal');
        var roles = {};

        $(function() {
            users_datatable = users_table.DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: "<?= $rootURL ?>/users/list"
                },
                order: [[ 3, "asc" ]],
                responsive: true,
                buttons: [
                    'pageLength','colvis'
                ],
                layout: {
                    topStart: 'buttons',
                },
                columnDefs: [
                    {
                        className: "dt-center",
                        targets: '_all'
                    },
                    {
                        orderable: false,
                        targets: [10]
                    },
                    {
                        visible: false,
                        targets: [0,1,2,5,8,9]
                    }
                ],
                language: {
                    emptyTable: "No users have been added."
                },
                pagingType: "full_numbers",
                columns: [
                    {
                        data: 'id'
                    },
                    {
                        data: 'firstname'
                    },
                    {
                        data: 'lastname'
                    },
                    {
                        data: null,
                        render: function (data) {
                            if (data.id.startsWith("notloggedin_")){
                                return "Waiting for user to log in..."
                            } else {
                                return data.fullname;
                            }
                        }
                    },
                    {
                        data: 'eppn'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'number'
                    },
                    {
                        data: 'idpname'
                    },
                    {
                        data: 'affiliation',
                        render: function (data) {
                            let html = "";
                            if (data !== null) {
                                let aff = data.split(';');
                                for (let i = 0; i < aff.length; i++) {
                                    if (i === aff.length - 1) {
                                        html += aff[i];
                                    } else {
                                        html += aff[i] + '<br>';
                                    }
                                }
                            }
                            return html;
                        }
                    },
                    {
                        data: 'idp'
                    },
                    {
                        data: null,
                        render: function (data) {
                            let html = "";
                            const roles = JSON.parse(data.roles);
                            for (const [roleId, roleName] of Object.entries(roles)) {
                                html += `${roleName}<br>`;
                            }
                            return html;
                        }
                    },
                    {
                        data: null,
                        render: function (data) {
                            let html = "";
                            if (data.id !== "<?php echo $user->getId(); ?>") {
                                html +=`<button class='btn btn-primary btn-xs me-1' onclick='edit_user("${data.id}");'>
                                            <span class='fas fa-user-edit' data-toggle='tooltip' data-placement='left' title='Edit User'></span>
                                        </button>
                                        <button class='btn btn-danger btn-xs me-1' onclick='delete_user("${data.id}");'>
                                            <span class='fas fa-user-slash' data-toggle='tooltip' data-placement='left' title='Delete User'></span>
                                        </button>`;
                            }
                            return html;
                        }
                    }
                ]
            });

            $.ajax({
                    url: "<?= $rootURL ?>/users/get-roles",
                    type: "GET",
                    success:
                        function(result){
                            $.each(result.roles, function(index){
                                $('#user-roles').append('<option value="'+result.roles[index][0]+'">'+result.roles[index][1]+'</option>');
                                roles[result.roles[index][0]] = result.roles[index][1];
                            });
                        }
                }
            );
            $.ajax({
                url : '<?= $rootURL?>/participants/fill-location-dropdown',
                type : 'GET',

                success : function(data) {
                    $('#user-location').find('option').remove();
                    data['data'].forEach(function(currentValue, index, arr){
                        $('#user-location').append("<option value='"+currentValue+"'>"+ currentValue +"</option>");
                    });
                    $('#user-location').selectpicker('refresh');
                },
                error : function(request,error) {
                    console.error(error);
                    console.error(request);
                }
            });
        });

        $('#user-roles').on('change', function() {
            if ($(this).val() === "2") {
                $('.study-admin-extras').show();
            } else {
                $('.study-admin-extras').hide();
            }
        });

        function fillTimeZones(selected) {
            let location = $("#user-location").val();
            $("#user-timezone").empty();
            $.ajax({
                url : '<?= $rootURL ?>/participants/fill-timezone-dropdown',
                type : 'GET',
                data: {
                    'location': location
                },

                success : function(data) {
                    let tzSelect = $('#user-timezone');
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

        function submit_user() {
            let user_id = $('#user-id').val();
            if (user_id === ""){
                user_id = null;
            }

            let user_email = $('#user-email').val();
            if (user_email === null || user_email === '') {
                showError('Please enter email for user.');
                return;
            }

            if (!validateEmail(user_email)) {
                showError("Please enter a valid email.");
                return;
            }

            let user_role = $('#user-roles').val();
            if (user_role === null){
                showError('Please choose this user\'s role.');
                return;
            }

            let number = "";
            let timezone = "";
            if (user_role === "2") {
                timezone = $('#user-timezone').val();
                if (timezone === null) {
                    showError('Please choose this user\'s timezone.');
                    return;
                }

                number = $('#user-number').val();
                if (number.length !== 10) {
                    showError("Please enter a 10 digit phone number.");
                    return false;
                }

                if(/[a-z]/i.test(number)){
                    showError("Please enter a valid phone number.");
                    return false;
                }
                number = '+1' + number;
            }

            let formData = {
                'id': user_id,
                'email': user_email,
                'number': number,
                'timezone': timezone,
                'role': [user_role]
            };

            $.ajax({
                url: '<?= $rootURL ?>/users/submit',
                type: "POST",
                data: formData,
                dataType: 'json'
            }).done(function(data) {
                if (data.success) {
                    showSuccess('Successfully ' + data.action + 'd user.');
                    users_datatable.ajax.reload();
                    userModal.modal('hide');
                } else {
                    showError(data.error_message);
                }
            });
        }

        function delete_user(user_id) {
            let isExecuted = confirm("Are you sure to delete this user? This action is not reversible.");
            if (isExecuted) {
                $.post({
                    url: '<?= $rootURL; ?>/users/delete',
                    data: {'id': user_id},
                    dataType: 'json'
                }).done(function(data) {
                    if (data.success) {
                        showSuccess('Successfully deleted user.');
                        users_datatable.ajax.reload( null, false );
                    } else {
                        showError(data.error_message);
                    }
                });
            }
        }

        function fill_user_form(user) {
            if (user !== null) {
                $('#user-id').val(user.id);
                $('#user-email').val(user.eppn);

                let usersRoles = JSON.parse(user.roles);
                let selectedRoles = Object.keys(roles).filter(roleId => usersRoles.hasOwnProperty(roleId));
                $('#user-roles').val(selectedRoles);

                if (selectedRoles.includes("2")) {
                    $('.study-admin-extras').show();
                    $('#user-number').val((user.number && user.number !== "") ? user.number.slice(2) : "");
                    $('#user-location').val(user.timezone.includes('/') ? user.timezone.split('/')[0] : user.timezone);
                    $('#user-location').selectpicker('refresh');
                    fillTimeZones(user.timezone);
                } else {
                    $('.study-admin-extras').hide();
                }
            }
        }

        function edit_user(user_id) {
            let row_data = users_datatable.rows().data().filter(function(data, index){
                return data['id'] === user_id;  // Assuming 'id' is the first column
            }).toArray();
            fill_user_form(row_data[0]);
            userModal.modal('show');
        }

        function clear_user_form() {
            $('#user-id').val('');
            $('#user-email').val('');
            $('#user-roles').val('1');
            $('#user-number').val('');
            $('#user-location').val('');
            $('#user-location').selectpicker('refresh');
            $('#user-timezone').val('');
            $('#user-timezone').selectpicker('refresh');
            $('.study-admin-extras').hide();
        }

        userModal.on('hidden.bs.modal', function() {
            clear_user_form();
        });

        function validateEmail(email) {
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return emailPattern.test(email);
        }
    </script>
<?php
include_once __DIR__ . '/../_footer.php';