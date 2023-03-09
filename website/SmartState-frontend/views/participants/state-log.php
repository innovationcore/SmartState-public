<?php
/** @var UserSession $userSession */
$page = 'participants-state';
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4">Participants - <span class="text-muted">State Log</span></h1>
    </div>


    <div class="row">
        <div class="col-md-6 mb-3 form-floating">
            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <select class="selectpicker" data-width="fit" id="select-participant-state" data-none-selected-text="Select Participant" data-live-search="true" data-live-search-placeholder="Search"></select>
                <select class="selectpicker" data-width="fit" id="select-participant-protocol" data-none-selected-text="Select Protocol" data-live-search="true" data-live-search-placeholder="Search"></select>
            </div>
        </div>
        <div class="col-md-6 mb-3 form-floating">
            <div id="state-info">
                <h5>Current State: <span id="current-state"></span></h5>
                <select class="selectpicker" data-width="fit" id="move-to-next-state" data-none-selected-text="Select State" data-live-search="false"></select>
                <button type="button" class="btn btn-sm btn-success" onclick=moveToState() id="btn-participant-state-refresh">Move State <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <button type="button" class="btn btn-secondary mb-2" data-toggle="collapse" data-target="#state-machine" aria-expanded="false" aria-controls="collapseExample">Show/Hide State Machine</button>
            <div class="collapse mb-2" id="state-machine">
                <div id="state-machine-content" class="card card-body"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <table id="collection" class="table table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th style="text-align: center;">Timestamp</th>
                    <th style="text-align: center;">Log</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th style="text-align: center;">Timestamp</th>
                    <th style="text-align: center;">Log</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>


    <!-- These scripts read graphviz files -->
    <script src="https://d3js.org/d3.v5.min.js"></script>
    <script src="https://unpkg.com/@hpcc-js/wasm@0.3.11/dist/index.min.js"></script>
    <script src="https://unpkg.com/d3-graphviz@3.0.5/build/d3-graphviz.js"></script>
   
    <script type="text/javascript">
        var collection = {};
        var collectionTable = $('#collection');
        var collectionDataTable = null;

        function updateDT() {
            let uuid = $('#select-participant-state').val();
            let protocol = $('#select-participant-protocol').find("option:selected").text();
    
            collectionDataTable = $('#collection').DataTable({
                serverSide: false,
                ajax: {
                    url: "/participants/get-state-log",
                    type: "POST",
                    data: {
                        'uuid': uuid,
                        'protocol': protocol},
                    error: function (xhr, error, thrown) {
                        console.log(xhr);
                        console.log(error);
                        console.log(thrown);
                    }
                },
                order: [[ 0, "desc" ]],
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'pageLength', 'colvis'
                ],
                columnDefs: [
                    {"className": "dt-center", "targets": "_all"},
                    {
                        type: "date",
                        targets: [0]
                    }
                ],
                language: {
                    emptyTable: "No logs for selected user."
                },
                pagingType: "full_numbers",
                columns: [
                    {
                        data: 'TS',
                    },
                    {
                        data: 'json',
                    }
                ]
            });
            collectionDataTable.buttons().container().prependTo('#collection_filter');
            collectionDataTable.buttons().container().addClass('float-left');
            $('.dt-buttons').addClass('btn-group-sm');
            $('.dt-buttons div').addClass('btn-group-sm');
            collectionTable.on('xhr.dt', function (e, settings, data) {
                if(data == null || data.data == null){
                    return;
                }
                logs = {};
                $.each(data.data, function(i, v) {
                    logs[v.id] = v;
                });
            });
            
       }

        $(document).ready( function () {
            $.ajax({
                url : '/participants/all',
                type : 'GET',

                success : function(data) {
                    if (data.success) {
                        data.participants.forEach(function(currentValue, index, arr){
                            // parse json;
                            let participant = JSON.parse(currentValue.json);
                            $('#select-participant-state').append("<option value='"+currentValue.uuid+"'>"+ participant.first_name + " "+ participant.last_name +"</option>");
                        });
                        $.ajax({
                            url : '/protocol-types/all',
                            type : 'GET',

                            success : function(data) {
                                if (data.success) {
                                    data.protocols.forEach(function(currentValue, index, arr){
                                        $('#select-participant-protocol').append("<option value='"+currentValue.id+"'>"+ currentValue.name +"</option>");
                                        
                                    });
                                    $('#select-participant-protocol').selectpicker('refresh');
                                    $('#select-participant-state').selectpicker('refresh');

                                    updateDT();
                                    updateCurrentState();
                                    updateNextStates();
                                    loadGraphFile();
                                } else {
                                    console.error(data.error_message);
                                }
                            },
                            error : function(request,error) {
                                console.error(error);
                            }
                        });
                    } else {
                        showError(data.error_message);
                    }
                },
                error : function(request,error) {
                    console.error(error);
                }
            });
        });

        $('#select-participant-state').on('changed.bs.select', function (e) {
            $('#collection').DataTable().destroy();
            updateDT();
            updateCurrentState();
        });

        $('#select-participant-protocol').on('change', function (){
            $('#collection').DataTable().destroy();
            updateDT();
            updateNextStates();
            updateCurrentState();
            loadGraphFile();
        });

        function updateCurrentState(){
            let uuid = $('#select-participant-state').val();
            let protocol = $('#select-participant-protocol').find("option:selected").text();
            $.ajax({
                url : '/participants/get-current-state',
                type : 'POST',
                data: {
                    'uuid': uuid,
                    'protocol': protocol},
                success : function(data) {
                    if (data.success) {
                        $('#current-state').html(data.state);
                    } else {
                        $('#current-state').html("Unknown");
                        console.error(data.error_message);
                    }
                },
                error : function(request,error) {
                    console.error(error);
                }
            });
        }

        function updateNextStates() {
            let protocol = $('#select-participant-protocol').find("option:selected").text();
            $.ajax ({
                url: 'http://localhost:9000/api/get-valid-next-states/', //change to this when developing locally
                type: 'GET',
                data: {
                    'participant_uuid': $('#select-participant-state').val(),
                    'protocol': protocol
                },

                success: function(data) {
                    if (data.status == "ok") {
                        let states = data.valid_states.split(',');
                        $('#move-to-next-state').empty();
                        states.forEach(function(currentValue, index, arr){
                            $('#move-to-next-state').append("<option value='"+currentValue+"'>"+currentValue+"</option>");
                        });
                        $('#move-to-next-state').selectpicker('refresh');
                        
                    } else {
                        console.error(data.status_desc);
                    }
                },
                error: function(request,error) {
                    console.error(error);
                }
            });
        }

        function moveToState() {
            let uuid = $('#select-participant-state').val();
            let state = $('#move-to-next-state').val();
            let protocol = $('#select-participant-protocol').find("option:selected").text();

            $.ajax({
                url : 'http://localhost:9000/api/next-state/', //change to this when developing locally
                type : 'GET',
                data: {
                    'participantUUID': uuid, 
                    'toState': state,
                    'protocol': protocol
                    },

                success : function(data) {
                    if (data.status == "ok") {
                        updateCurrentState();
                        updateNextStates();
                        $('#collection').DataTable().destroy();
                        updateDT();
                        loadGraphFile();
                    } else {
                        console.error(data.status_desc);
                    }
                },
                error : function(request,error) {
                    console.error(error);
                }
            });
        }

        // graphviz stuff
        function loadGraphFile(){
            let protocol = $('#select-participant-protocol').find("option:selected").text();
            $.ajax({
                url : '/participants/get-state-machine',
                type : 'POST',
                data: {'protocol': protocol},
                success : function(data) {
                    let content = data.content.replaceAll("\n", "");
                    if (data.success){
                        let currentState = $('#current-state').html();
                        
                        d3.select("#state-machine-content").graphviz().attributer(function(d){
                            if (d.id.includes(currentState)) { //polygons are arrow points, text and g are text inside bubbles and connecting bubbles,
                                if (d.tag == "path"){
                                    if (d.id.includes("->")){
                                        let drawArrows = d.id.split("->");
                                        if (drawArrows[0].includes(currentState)){
                                            d.attributes.stroke = "#ff2500";
                                        }
                                    } else {
                                        d.attributes.fill = "#62dfff";
                                    }
                                } else if (d.tag == "polygon") {
                                    let drawArrows = d.id.split("->");
                                    if (drawArrows[0].includes(currentState)){
                                        d.attributes.fill = "#ff2500";
                                        d.attributes.stroke = "#ff2500";
                                    }
                                }
                            }
                        }).renderDot(data.content);
                    }
                },
                error : function(request,error) {
                    console.error(error);
                }
            });
        }
    </script>
<?php
include_once __DIR__ . '/../_footer.php';