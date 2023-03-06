<?php
/** @var UserSession $userSession */
$page = 'protocol-types-index';
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Protocol Types - <span class="text-muted">Overview</span></h1>
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#protocolModal">
            <i class="fas fa-plus"></i>
            Add Protocol
        </button>
    </div>
    <div class="row">
        <div class="col">
            <table id="collection" class="table table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Submit/Edit Protocol Modal -->
    <div class="modal fade" id="protocolModal" tabindex="-1" role="dialog" aria-labelledby="protocolModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="protocolModalLabel">Add Protocol</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" id="protocolModalProtocolId" value="" />
                        <div class="col-sm-12 mb-3 form-floating">
                            <input class="form-control" type="text" style="pointer-events: auto;" id="protocolModalName" placeholder="Name" autofocus/>
                            <label for="protocolModalName">Name</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button id="update-button" type="button" class="btn btn-primary" onclick="submit_protocol();">Submit Protocol</button>
                </div>
            </div>
        </div>
    </div>
   
    <script type="text/javascript">
        var protocolModal = $('#protocolModal');
        var protocolModalName = $('#protocolModalName');
        var protocolModalLabel = $('#protocolModalLabel');

        var collection = {};
        var collectionTable = $('#collection');
        var collectionDataTable = null;

        $(function() {
            collectionDataTable = collectionTable.DataTable({
                serverSide: false,
                ajax: {
                    url: "/protocol-types/list"
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
                        targets: [0,1]
                    },
                    {
                        orderable: true,
                        targets: [0]
                    }
                ],
                language: {
                    emptyTable: "No protocols have been added"
                },
                pagingType: "full_numbers",
                columns: [
                    {
                        data: 'name'
                    },
                    {
                        data: null,
                        render: function ( data ) {
                            var html = "<button class='btn btn-primary btn-xs' onclick='edit_protocol(\"" + data.id + "\");'>" +
                                    "<span class='fas fa-user-edit' data-toggle='tooltip' data-placement='left' title='Edit Protocol'></span>" +
                                    "</button>&nbsp;" +
                                    "<button class='btn btn-danger btn-xs' onclick='delete_protocol(\"" + data.id + "\");'>" +
                                    "<span class='fas fa-user-slash' data-toggle='tooltip' data-placement='left' title='Delete Protocol'></span>" +
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
                protocols = {};
                $.each(data.data, function(i, v) {
                    protocols[v.id] = v;
                });
            });
        });

        function clear_protocol_form() {
            protocolModalName.val('');
            protocolModalLabel.text('Add Protocol');
            $('#update-button').text('Submit Protocol');
            $('#update-button').attr('onclick', 'submit_protocol();');
        }

        protocolModal.on('hidden.bs.modal', function() {
            clear_protocol_form();
        });

        function fill_protocol_form(id) {  
            $.ajax({
                url: '/protocol-types/get-name',
                type: 'POST',
                data: {
                    'id': id
                },
                success: function(data) {
                    if (data.success) {
                        protocolModalName.val(data.name);
                    } else {
                        showError(data.error_message);
                        return null;
                    }
                    $('#update-button').attr('onclick', 'update_protocol("'+id+'");');
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    console.error(status);
                    console.error(error);
                    showError("Error communicating with the server.");
                    return null;
                }
            });
        }

        

        function edit_protocol(id) {
            protocolModalLabel.text('Edit Protocol');
            $('#update-button').text('Update Protocol');
            fill_protocol_form(id);
            protocolModal.modal('show');
        }

        function update_protocol(id) {
            let name = protocolModalName.val();
            $.ajax({
                url: '/protocol-types/update',
                type: 'POST',
                data: {
                    'id': id,
                    'name': name
                },
                success: function(data) {
                    if (data.success) {
                        showSuccess("Updated Protocol.");
                        collectionDataTable.ajax.reload();
                        protocolModal.modal('hide');
                    } else {
                        showError(data.error_message);
                        return null;
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    console.error(status);
                    console.error(error);
                    showError("Error communicating with the server.");
                    return null;
                }
            });
        }

        function delete_protocol(id) {
            let isExecuted = confirm("Are you sure to delete this protocol? This action is not reversible.");
            if (isExecuted) {
                $.ajax({
                    url: '/protocol-types/delete',
                    type: 'POST',
                    data: {
                        'id': id
                    },
                    success: function(data) {
                        if (data.success) {
                            showSuccess("Deleted Protocol.");
                            collectionDataTable.ajax.reload();
                        } else {
                            showError(data.error_message);
                            return null;
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        console.error(status);
                        console.error(error);
                        showError("Error communicating with the server.");
                        return null;
                    }
                });
            }
        }

        function submit_protocol() {
            let name = protocolModalName.val();
            if (name.length == 0) {
                showError("Please enter a name for the protocol.");
                return null;
            } else {
                $.ajax({
                    url: '/protocol-types/create',
                    type: 'POST',
                    data: {
                        'name': name
                    },
                    success: function(data) {
                        if (data.success) {
                            showSuccess("Added Protocol.");
                            collectionDataTable.ajax.reload();
                            protocolModal.modal('hide');
                        } else {
                            showError(data.error_message);
                            return null;
                        }

                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        console.error(status);
                        console.error(error);
                        showError("Error communicating with the server.");
                        return null;
                    }
                });
            }
        }
    </script>
<?php
include_once __DIR__ . '/../_footer.php';