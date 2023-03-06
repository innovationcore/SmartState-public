<?php
/** @var UserSession $userSession */
$page = 'survey-view';
include_once __DIR__ . '/../_header.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4 mr-auto p-2">Survey - <span class="text-muted">View All</span></h1>
    </div>
    <div class="row">
        <div class="col">
            <table id="collection" class="table table-bordered dt-responsive responsive-text" style="width:100%">
                <thead>
                <tr>
                    <th>Token</th>
                    <th style="text-align: center;">Participant Name</th>
                    <th style="text-align: center;">Survey Response</th>
                    <th style="text-align: center;">Created At</th>
                    <th style="text-align: center;">Finished At</th>
                    <th style="text-align: center;">Time Zone</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                <tr>
                    <th>Token</th>
                    <th style="text-align: center;">Participant Name</th>
                    <th style="text-align: center;">Survey Response</th>
                    <th style="text-align: center;">Created At</th>
                    <th style="text-align: center;">Finished At</th>
                    <th style="text-align: center;">Time Zone</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
   
    <script type="text/javascript">
        var collection = {};
        var collectionTable = $('#collection');
        var collectionDataTable = null;

        $(function() {
            collectionDataTable = collectionTable.DataTable({
                serverSide: false,
                ajax: {
                    url: "/survey/list"
                },
                order: [[ 4, "desc" ]],
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'pageLength', 'colvis'
                ],
                columnDefs: [
                    {
                        className: "dt-center",
                        targets: [0,1,2,3,4,5]
                    },
                    {
                        orderable: true,
                        targets: [0,1,2,3,4,5]
                    },
                    {
                        type: "date",
                        targets: [3, 4]
                    }
                ],
                language: {
                    emptyTable: "No surveys have been sent/received yet."
                },
                pagingType: "full_numbers",
                columns: [
                    {
                        data: 'token'
                    },
                    {
                        data: 'participant_name'
                    },
                    {
                        data: null,
                        render: function(data){
                            if(data.survey_json == null){
                                return "";
                            } else {
                                return JSON.stringify(JSON.parse(data.survey_json));
                            }
                        }
                    },
                    {
                        data: 'created_at',
                    },
                    {
                        data: 'finished_at',
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
                surveys = {};
                $.each(data.data, function(i, v) {
                    surveys[v.uuid] = v;
                });
            });
        });
    </script>
<?php
include_once __DIR__ . '/../_footer.php';
