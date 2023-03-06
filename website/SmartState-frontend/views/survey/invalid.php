<?php
/** @var UserSession $userSession */
$page = 'invalid-survey';
include_once __DIR__ . '/../_header_no_sidebar.php';
?>
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h4 mr-auto p-2">Survey - <span class="text-muted">Invalid Token</span></h1>
    </div>
    <div class="row">
        <div class="col-md-12">
            <h1 class="text-center">This survey link is invalid or has expired.</h1>
            <br>
            <h4 class="text-center">Please contact the study administrator if you believe this is an error.</h4>
            <br>
            <p class="text-center"><a href="tel:18005555555" class="btn btn-primary"><i class="fa fa-phone"> 1-800-555-5555</i></a></p>
            
        </div>
    </div>
   
   
<?php
include_once __DIR__ . '/../_footer.php';
