<?php
/** @var UserSession $userSession */
$page = 'messages-index';
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4 mr-auto p-2">Messages - <span class="text-muted">Overview</span></h1>
        <a href="/messages/export" class="btn btn-secondary p-2 mr-2">
            <i class="fas fa-file-export"></i>
            Export Messages
        </a>
        <?php if($userSession->getUser()->getRole() != 1): //1 = non-PHI?>
        <button type="button" class="btn btn-success p-2" data-toggle="modal" data-target="#messageModal">
            <i class="fas fa-plus"></i>
            Send Message
        </button>
<?php endif; ?>
    </div>
<?php if($userSession->getUser()->getRole() == 1): //1 = non-PHI, 2=PI?> 
    <div class="row">
        <div class="col">
            <table id="collection" class="table table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th>Outgoing/Incoming</th>
                    <th style="text-align: center;">Message Body</th>
                    <th style="text-align: center;">Timestamp</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>Outgoing/Incoming</th>
                    <th style="text-align: center;">Message Body</th>
                    <th style="text-align: center;">Timestamp</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

<?php else: //everyone else?> 
    <div class="row">
        <div class="col">
            <table id="collection" class="table table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th>Outgoing/Incoming</th>
                    <th style="text-align: center;">Participant Name</th>
                    <th style="text-align: center;">Message Body</th>
                    <th style="text-align: center;">Timestamp</th>
                    <th style="text-align: center;">Time Zone</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>Outgoing/Incoming</th>
                    <th style="text-align: center;">Participant Name</th>
                    <th style="text-align: center;">Message Body</th>
                    <th style="text-align: center;">Timestamp</th>
                    <th style="text-align: center;">Time Zone</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
<? endif; ?>

    <!-- Submit message modal -->  
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Send Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <select class="selectpicker" data-width="fit" id="messageModalParticipant" data-none-selected-text="Select Participants" multiple data-live-search="true" data-live-search-placeholder="Search"></select>
                                <label id="messageModalParticipant"></label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3 form-floating">
                            <textarea class="form-control" style="pointer-events: auto;" id="messageModalMessage" placeholder="Message..."></textarea>
                            <label for="messageModalMessage">Message</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button id="send-button" type="button" class="btn btn-primary" onclick="send_message();">Send Message</button>
                </div>
            </div>
        </div>
    </div>
   
    <script type="text/javascript">
        var messageModal = $('#messageModal');
        var messageModalParticipant = $('#messageModalParticipant');
        var messageModalMessage = $('#messageModalMessage');

        var collection = {};
        var collectionTable = $('#collection');
        var collectionDataTable = null;

        $(function() {
            let user_role = <?php echo $userSession->getUser()->getRole(); ?>;
            if (user_role == 1) {
                collectionDataTable = collectionTable.DataTable({
                    serverSide: false,
                    ajax: {
                        url: "/messages/list"
                    },
                    order: [[ 2, "desc" ]],
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: [
                        'pageLength', 'colvis'
                    ],
                    columnDefs: [
                        {
                            className: "dt-center",
                            targets: [0,1,2,3]
                        },
                        {
                            orderable: true,
                            targets: [0,1,2,3]
                        },
                        {
                            type: "date",
                            targets: [2]
                        }
                    ],
                    language: {
                        emptyTable: "No messages have been sent/received yet."
                    },
                    pagingType: "full_numbers",
                    columns: [
                        {
                            data: 'direction'
                        },
                        {
                            data: null,
                            render: function (data) {
                                let body_json = JSON.parse(data.json);
                                let body = body_json.Body;
                                return body;
                            } 
                        },
                        {
                            data: 'TS',
                        },
                        {
                            data: 'time_zone',
                        }
                    ]
                });
                collectionDataTable.buttons().container().prependTo('#collection_filter');
                collectionDataTable.buttons().container().addClass('float-left');
                $('.dt-buttons').addClass('btn-group-sm');
                $('.dt-buttons div').addClass('btn-group-sm');
                collectionTable.on('xhr.dt', function (e, settings, data) {
                    messages = {};
                    $.each(data.data, function(i, v) {
                        messages[v.uuid] = v;
                    });
                });
            } else {
                collectionDataTable = collectionTable.DataTable({
                    serverSide: false,
                    ajax: {
                        url: "/messages/list"
                    },
                    order: [[ 3, "desc" ]],
                    responsive: true,
                    dom: 'Bfrtip',
                    buttons: [
                        'pageLength', 'colvis'
                    ],
                    columnDefs: [
                        {
                            className: "dt-center",
                            targets: [0,1,2,3,4]
                        },
                        {
                            orderable: true,
                            targets: [0,1,2,3,4]
                        },
                        {
                            type: "date",
                            targets: [3]
                        }
                    ],
                    language: {
                        emptyTable: "No messages have been sent/received yet."
                    },
                    pagingType: "full_numbers",
                    columns: [
                        {
                            data: 'direction'
                        },
                        {
                            data: 'participant_name'
                        },
                        {
                            data: null,
                            render: function (data) {
                                let body_json = JSON.parse(data.json);
                                let body = body_json.Body;
                                return body;
                            } 
                        },
                        {
                            data: 'TS',
                        },
                        {
                            data: 'time_zone',
                        }
                    ]
                });

                collectionDataTable.buttons().container().prependTo('#collection_filter');
                collectionDataTable.buttons().container().addClass('float-left');
                $('.dt-buttons').addClass('btn-group-sm');
                $('.dt-buttons div').addClass('btn-group-sm');
                collectionTable.on('xhr.dt', function (e, settings, data) {
                    messages = {};
                    $.each(data.data, function(i, v) {
                        messages[v.uuid] = v;
                    });
                });
            }
        });

        messageModal.on('hidden.bs.modal', function() {
            clear_message_form();
        });


        function clear_message_form() {
            messageModalParticipant.val('default');
            messageModalParticipant.selectpicker('refresh');
            messageModalMessage.val('');
        }

        $(document).ready( function () {
            // ajax to get Participant Name (Phone number)
            $.ajax({
                url: '/participants/all',
                type: 'GET',
                success: function(data) {
                    if (data.success) {
                        for(const participant of data.participants) {
                            part_json = JSON.parse(participant.json);
                            messageModalParticipant.append('<option value="' + participant.uuid + '">' + part_json['first_name']+' '+part_json['last_name']+ ' (' + part_json['number']+ ')</option>');
                        }
                        messageModalParticipant.selectpicker('refresh');
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
        });

        function send_message(){
            // ajax to send message
            $.ajax({
                url: '/messages/send',
                type: 'POST',
                data: {
                    'participant_uuid': messageModalParticipant.val(),
                    'body': messageModalMessage.val()
                },
                success: function(data) {
                    if (data.success) {
                        showSuccess('Message sent successfully.');
                        messageModal.modal('hide');
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
    </script>
<?php
include_once __DIR__ . '/../_footer.php';
