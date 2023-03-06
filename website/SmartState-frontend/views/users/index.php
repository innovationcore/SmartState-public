<?php
/** @var UserSession $userSession */
$page = "users";
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Users</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#userModal">
                <i class="fas fa-user-plus"></i> Add New User
            </button>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <table id="users" class="table table-striped table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Account Type</th>
                    <th>Role</th>
                    <th>Phone Number</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>Username</th>
                    <th>Account Type</th>
                    <th>Role</th>
                    <th>Phone Number</th>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">

                        </div>
                    </div>
                    <div class="row">
                        <input type="hidden" id="userModalUserId" value="" />
                        <div class="col-sm-12 mb-3 form-floating">
                            <input class="form-control" type="text" style="pointer-events: auto;" id="userModalLinkblue" placeholder="Username" />
                            <label for="userModalLinkblue">Username</label>
                        </div>
                    </div>
                    <div class="row">
                        <div id="password-fields" class="col-sm-12 mb-3 form-floating">
                            <input class="form-control" type="password" style="pointer-events: auto;" id="userModalPassword" placeholder="Password" />
                            <label for="userModalPassword">Password</label>
                            <small>
                                <p id="pass-info-text"></p>
                                Password must:
                                <ul>
                                    <li>Be longer than 8 characters</li>
                                    <li>Include a special character (!@#$%^&*)</li>
                                    <li>Include a number</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <select class="form-control" name="user-roles" id="user-roles">
                                <option value="-1">-- Select Role --</option>
                            </select>
                            <label for="user-roles">Account Role:</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating" id="phone-number-fields">
                            <input class="form-control" type="text" style="pointer-events: auto;" id="phone-number" placeholder="Phone Number" value="+1">
                            <label for="phone-number">Phone Number</label>
                            <small>Note: Please do not include hyphens or parentheses in the phone number.</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <select class="form-control" name="account-type" id="account-type">
                                <option value="0">Linkblue</option>
                                <option value="1">Non-Linkblue</option>
                            </select>
                            <label for="account-type">Account Type<label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submit_user();">Submit User</button>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var users_table = $('#users');
        var users_datatable = null;
        var users = {}

        var userModal = $('#userModal');
        var userModalUserId = $('#userModalUserId');
        var userModalLinkblue = $('#userModalLinkblue');
        var userModalPass = $('#userModalPassword');
        var userModalAccountTypeOptions = $('#userModalAccountTypeOptions');
        var userAccountType = $('#account-type');
        var userRoles = $('#user-roles');

        $.ajax(
            {
                url: "/users/getRoles", 
                success: 
                function(result){
                    $.each(result.roles, function(index){
                        $('#user-roles').append('<option value="'+result.roles[index][0]+'">'+result.roles[index][1]+'</option>');
                    });
                }
            }
        );

        function fill_user_form(user) {
            if (user !== null) {
                userModalUserId.val(user.id);
                userModalLinkblue.val(user.linkblue);
                $('#user-roles').val(user.role);
                $('#user-roles').each(function(){
                    $(this).removeAttr('hidden');
                });
                if (user.type == "Non-Linkblue"){
                    $('#pass-info-text').html('Leave password blank to keep the same.');
                }
                if(user.type == "Non-Linkblue"){
                    $('#account-type').val(1);
                    $('#password-fields').show();
                }
                else{
                    $('#account-type').val(0);
                    $('#password-fields').hide();
                }
                if (user.role == 1){
                    $('#phone-number-fields').show();
                    $('#phone-number').val(user.phone_number);
                }
                else{
                    $('#phone-number-fields').hide();
                }
            }
        }

        function edit_user(user_id, isRolesDisabled) {
            fill_user_form(users[user_id]);
            userModalLinkblue.attr('disabled', 'disabled');
            if (isRolesDisabled) {
                $('#user-roles').attr('disabled', 'disabled');
            } else {
                $('#user-roles').prop('disabled', false);
            }
            userModal.modal('show');
        }

        function delete_user(user_id) {
            let isExecuted = confirm("Are you sure to delete this user? This action is irreversible.");
            if (isExecuted) {
                $.post({
                    url: '/users/deleteUser',
                    data: {'id': user_id},
                    dataType: 'json'
                }).done(function(data) {
                    if (data.success) {
                        showSuccess('Successfully removed user');
                        users_datatable.ajax.reload( null, false );
                        userModal.modal('hide');
                    } else {
                        showError(data.error_message);
                    }
                });
            }
        }

        function clear_user_form() {
            userModalUserId.val('');
            userModalLinkblue.val('');
            userModalLinkblue.attr('disabled', false);
            $('#user-roles').prop('disabled', false);
            userModalPass.val('');
            $('#user-roles').val("-1");
            $('#user-roles').each(function(){
                $(this).attr('hidden');
            });
            $('#pass-info-text').html('');
            $('#account-type').val("0");
            $('#password-fields').hide();
            $('#phone-number').val("+1");
            $('#phone-number-fields').hide();
        }

        userModal.on('hidden.bs.modal', function() {
            clear_user_form();
        });

        function submit_user() {
            if (userModalLinkblue.val() === null || userModalLinkblue.val() === '') {
                showError('You must supply a Linkblue or username.');
                return;
            }

            let userRole = $('#user-roles').val();
            if (userRole === "-1"){
                showError('You must choose a user role');
                return;
            } 

            let phoneNumber = $('#phone-number').val();
            if (userRole == 1){
                if(/[a-z]/i.test(phoneNumber)){
                    showError("Phone number cannot contain letters.");
                    return;
                }

                if (phoneNumber.indexOf("+") != 0){
                    showError("Phone number must start with a \"+\"");
                    return;
                }

                if (phoneNumber.indexOf("-") >= 0 || phoneNumber.indexOf("(") >= 0 || phoneNumber.indexOf(")") >= 0){
                    showError("Phone number cannot contain hyphens or parentheses.");
                    return;
                }
            } else {
                phoneNumber = "";
            }
            

            let accountType = userAccountType.val();
            let userPass = "";
            if (accountType == 1){
                userPass = userModalPass.val();
                let regex = /^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{8,32}$/;

                if (userPass.length !== 0){
                    if(!userPass.match(regex)) { 
                        showError('Password does not have 8 characters, a special character, or a number.');
                        return;
                    }
                }
            }
            var formData = {
                'id': userModalUserId.val(),
                'linkblue': userModalLinkblue.val(),
                'role': userRole,
                'pass': userPass,
                'phone_number': phoneNumber,
                'account_type': accountType,
            };
            $.post({
                url: '/users/submit',
                data: formData,
                dataType: 'json'
            }).done(function(data) {
                if (data.success) {
                    showSuccess('Successfully ' + data.action + ' user');
                    users_datatable.ajax.reload( null, false );
                    userModal.modal('hide');
                } else {
                    showError(data.error_message);
                }
            });
        }

        $(function() {
            clear_user_form();
            users_datatable = users_table.DataTable({
                serverSide: false,
                ajax: {
                    url: "/users/list"
                },
                order: [[ 0, "asc" ]],
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'pageLength', 'colvis'
                ],
                columnDefs: [
                    {
                        className: "dt-center",
                        targets: [0, 1, 2, 3, 4]
                    },
                    {
                        orderable: false,
                        targets: [4]
                    }
                ],
                language: {
                    emptyTable: "No users have been added"
                },
                pagingType: "full_numbers",
                columns: [
                    {
                        data: 'linkblue'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'role',
                        render: function ( data ) {
                            if (data == 0) {
                                return "Website Admin";
                            } else if (data == 1) {
                                return "Study Admin";
                            }
                            else {
                                return "User";
                            }
                            
                        }
                    },
                    {
                        data: 'phone_number'
                    },
                    {
                        data: null,
                        render: function ( data ) {
                            let isRolesDisabled = false;
                            if(data.linkblue === "<?php echo $userSession->getUser()->getLinkblue(); ?>"){
                                isRolesDisabled = true;
                            }
                            var html = "<button class='btn btn-primary btn-xs' onclick='edit_user(\"" + data.id + "\", "+isRolesDisabled+");'>" +
                                    "<span class='fas fa-user-edit' data-toggle='tooltip' data-placement='left' title='Edit User'></span>" +
                                    "</button>&nbsp;";
                            if (data.linkblue !== "<?php echo $userSession->getUser()->getLinkblue(); ?>") {
                                    html += "<button class='btn btn-danger btn-xs' onclick='delete_user(\"" + data.id + "\");'>" +
                                    "<span class='fas fa-user-slash' data-toggle='tooltip' data-placement='left' title='Delete User'></span>" +
                                    "</button>";
                            }
                            return html;
                        }
                    }
                ],
            });
            users_datatable.buttons().container().prependTo('#users_filter');
            users_datatable.buttons().container().addClass('float-left');
            $('.dt-buttons').addClass('btn-group-sm');
            $('.dt-buttons div').addClass('btn-group-sm');
            users_table.on('xhr.dt', function (e, settings, data) {
                users = {};
                $.each(data.data, function(i, v) {
                    users[v.id] = v;
                });
            });
        });

        userAccountType.change(function(){
            if ($(this).val() == 0){
                $('#password-fields').hide();
            } else {
                $('#password-fields').show();
            }
        });

        userRoles.change(function(){
            if ($(this).val() == 1){
                $('#phone-number-fields').show();
            } else {
                $('#phone-number-fields').hide();
            }
        });

        $(function(){
            if (userAccountType.val() == 0){
                $('#password-fields').hide();
            } else {
                $('#password-fields').show();
            }
            if (userRoles.val() == 1){
                $('#phone-number-fields').show();
            } else {
                $('#phone-number-fields').hide();
            }
        });


    </script>
<?php
include_once __DIR__ . '/../_footer.php';