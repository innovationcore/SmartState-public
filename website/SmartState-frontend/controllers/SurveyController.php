<?php

require_once __DIR__ . '/../utilities/db.php';
include_once MODELS_DIR . 'Survey.php';
include_once MODELS_DIR . 'Participant.php';


class SurveyController {
    public static function index(string $token = null, string $participantUUID = null) {
        if (is_null($token) || is_null($participantUUID)) {
            require SURVEY_VIEWS_DIR . 'invalid.php';
        } else {
            $isValid = (Survey::checkValidToken($token) && (Participant::withID($participantUUID) ? (true) : (false)) && !Survey::isSurveyFinished($token));
            if($isValid){
                require SURVEY_VIEWS_DIR . 'index.php';
            } else {
                require SURVEY_VIEWS_DIR . 'invalid.php';
            }
        }
    }

    public static function view(User $user) {
        require SURVEY_VIEWS_DIR . 'view.php';
    }

    public static function thankYou() {
        require SURVEY_VIEWS_DIR . 'thanks.php';
    }

    public static function listDT(User $user) {
        header('Content-Type: application/json');

        try{
            $start = 0;
            if (isset($_GET['start']))
                $start = intval($_GET['start']);
            $length = 0;
            if (isset($_GET['length']))
                $length = intval($_GET['length']);
            $filter = '';
            if (isset($_GET['search']['value']))
                $filter = $_GET['search']['value'];
            $order_by = '';
            if (isset($_GET['order'][0]['column']))
                $order_by = $_GET['order'][0]['column'];
            $order_dir = 'desc';
            if (isset($_GET['order'][0]['dir']))
                $order_dir = $_GET['order'][0]['dir'];
            $surveys = array();
            $idx = $start;
            $results = Survey::listForDatatable($start, $length, $order_by, $order_dir, $filter);
            $error_message = '';
            foreach ($results as $result) {
                $data_row = $result->jsonSerialize();
                $data_row['participant_name'] = Participant::getConcatNameFromID($result->getParticipant());
                $data_row['DT_RowId'] = $idx++;
                $participantTimeZone = Participant::getTimeZoneFromUUID($data_row['participant_uuid']);
                
                $date_created = new DateTime($data_row['created_at'], new DateTimeZone('UTC'));
                $date_created = $date_created->setTimezone(new DateTimeZone($participantTimeZone));
                $data_row['created_at'] = $date_created->format('m/d/Y h:i:s a');
                if (!is_null($data_row['finished_at'])) {
                    $date_finished = new DateTime($data_row['finished_at'], new DateTimeZone('UTC'));
                    $date_finished = $date_finished->setTimezone(new DateTimeZone($participantTimeZone));
                    $data_row['finished_at'] = $date_finished->format('m/d/Y h:i:s a');
                }
               
                $data_row['time_zone'] = $participantTimeZone;
                array_push($surveys, $data_row);
            }

            echo json_encode(
                array(
                    'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                    'recordsTotal' => intval(Survey::countForDatatable()),
                    'recordsFiltered' => intval(Survey::countFilteredForDatatable($filter)),
                    'data' => $surveys
                )
            );
        } catch (Exception $e) {
            echo json_encode(
                array(
                    'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => array(),
                    'error_message' => $e->getMessage()
                )
            );
        }
    }

    public static function saveSurvey() {
        header('Content-Type: application/json');
        $success = false;
        $error_message = "";

        if(empty($_POST['answers'])){
            $error_message = "Please continue to take the survey. Click \"Submit\", when you're finished.";
        } else if (empty($_POST['token'])){
            $error_message = "Survey is invalid or has expired, please check you link or refresh the page.";
        } else if (empty($_POST['participantUUID'])){
            $error_message = "Survey is invalid, please check your link or refresh the page.";
        } else {
            $surveyAnswers = $_POST['answers'];
            $token = $_POST['token'];
            $participantUUID = $_POST['participantUUID'];
            try {
                Survey::saveSurveyContent($surveyAnswers, $token, $participantUUID);
                $success = true;
            } catch (Exception $ex){
                $error_message = $ex->getMessage();
            }
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

}