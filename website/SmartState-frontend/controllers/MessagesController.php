<?php

require_once __DIR__ . '/../utilities/db.php';
include_once MODELS_DIR . 'User.php';
include_once MODELS_DIR . 'Message.php';
include_once MODELS_DIR . 'Participant.php';
include_once MODELS_DIR . 'ProtocolType.php';

class MessagesController {
    public static function index(UserSession $userSession) {
        require MESSAGES_VIEWS_DIR . 'index.php';
    }

    public static function logIndex(UserSession $userSession) {
        require MESSAGES_VIEWS_DIR . 'logs.php';
    }

    public static function listDT(UserSession $userSession) {
        header('Content-Type: application/json');

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
        $messages = array();
        $idx = $start;
        $results = Message::listForDatatable($start, $length, $order_by, $order_dir, $filter);
        $error_message = '';
        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();

            $data_row['participant_name'] = Participant::getConcatNameFromID($result->getParticipant());
            $data_row['DT_RowId'] = $idx++;
            $participantTimeZone = Participant::getTimeZoneFromUUID($data_row['participant_uuid']);
            try {
                $date = new DateTime($data_row['TS'], new DateTimeZone('UTC'));
                $date = $date->setTimezone(new DateTimeZone($participantTimeZone));
                $data_row['TS'] = $date->format('m/d/Y h:i:s.v a');
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            $data_row['time_zone'] = $participantTimeZone;
            array_push($messages, $data_row);
        }

        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => intval(Message::countForDatatable()),
                'recordsFiltered' => intval(Message::countFilteredForDatatable($filter)),
                'data' => $messages,
            )
        );
    }

    public static function listIndividualMessagesDT(UserSession $userSession) {
        header('Content-Type: application/json');

        if (empty($_POST['uuid']) || is_null($_POST['uuid'])){
            echo json_encode(
                array(
                    'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => array(),
                )
            );
            return;
        }

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
        $messages = array();
        $idx = $start;
        $error_message = '';
        $results = Message::listIndividualForDatatable($_POST['uuid'], $start, $length, $order_by, $order_dir, $filter);
        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();
            $data_row['DT_RowId'] = $idx++;
            $participantTimeZone = Participant::getTimeZoneFromUUID($data_row['participant_uuid']);

            try {
                $date = new DateTime($data_row['TS'], new DateTimeZone('UTC'));
                $date = $date->setTimezone(new DateTimeZone($participantTimeZone));
                $data_row['TS'] = $date->format('m/d/Y h:i:s.v a');
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            array_push($messages, $data_row);
        }
        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => intval(Message::countIndividualForDatatable($_POST['uuid'])),
                'recordsFiltered' => intval(Message::countIndividualFilteredForDatatable($_POST['uuid'], $filter)),
                'data' => $messages,
            )
        );
    }

    public static function sendMessage(UserSession $userSession) {
        header('Content-Type: application/json');
        date_default_timezone_set('UTC');
        $success = false;
        $error_message = null;

        $participant_uuid = $_POST['participant_uuid'];
        $message_json = $_POST['body'];
        $message_json = '{"Body":"'.$message_json.'"}';
        
        try {
            foreach($participant_uuid as $uuid) {
                $date = new DateTime();
                $date = $date->format('Y-m-d H:i:s');
                $group = Participant::getGroupFromUUID($uuid);
                $message = Message::create($uuid, $date, Message::OUTGOING, $message_json);
                if (is_null($message)) {
                    throw new Exception('Failed to send message.');
                }
                $participantNumber = Participant::getPhoneNumber($uuid);
                $result = Message::send($message, $participantNumber);
                if ($result) {
                    $success = true;
                } else {
                    throw new Exception('Failed to send message.');
                }
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function exportAsCSV(UserSession $userSession) {
        // Set headers to download file rather than displayed 
        $date = 
        header('Content-Type: text/csv'); 
        header('Content-Disposition: attachment; filename="messages_export_'.date('m-d-Y_H-i-s').'";'); 
        $success = false;
        $error_message = null;
        $data = null;

        $messageData = Message::allForCSV($userSession->getUser()->getRole());

        $delimiter = ","; 
        $filename = "members-data_" . date('Y-m-d') . ".csv"; 
        // Create a file pointer 
        $f = fopen('php://memory', 'w'); 

        // Output each row of the data, format line as csv and write to file pointer 
        foreach ($messageData as $row) {
            $lineData = array();
            foreach($row as $key) {
                array_push($lineData, $key);
            }
            fputcsv($f, $lineData, $delimiter); 
        } 
        
        // Move back to beginning of file 
        fseek($f, 0); 
    
        //output all remaining data on a file pointer 
        fpassthru($f); 
    }
}