<?php

require_once __DIR__ . '/../utilities/db.php';
include_once MODELS_DIR . 'User.php';
include_once MODELS_DIR . 'Message.php';
include_once MODELS_DIR . 'Participant.php';
include_once MODELS_DIR . 'ProtocolType.php';

class MessagesController {
    public static function index(User $user) {
        require MESSAGES_VIEWS_DIR . 'index.php';
    }

    public static function logIndex(User $user) {
        require MESSAGES_VIEWS_DIR . 'logs.php';
    }

    public static function listDT(User $user) {
        header('Content-Type: application/json');

        $start = 0;
        if (!empty($_GET['study']))
            $study = $_GET['study'];
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
        $results = Message::listForDatatable($start, $length, $order_by, $order_dir, $filter, $study);
        $error_message = '';
        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();

            $concatName = Participant::getConcatNameFromID($result->getParticipant());

            if ($result->getStudy() === "ADMIN" && $concatName === "") {
                $data_row['participant_name'] = "Study Admin";
            } else {
                $data_row['participant_name'] = Participant::getConcatNameFromID($result->getParticipant());
            }

            $data_row['DT_RowId'] = $idx++;
            $participantTimeZone = Participant::getTimeZoneFromUUID($data_row['participant_uuid']);
            try {
                $date = new DateTime($data_row['ts'], new DateTimeZone('UTC'));
                $date = $date->setTimezone(new DateTimeZone($participantTimeZone));
                $data_row['ts'] = $date->format('m/d/Y h:i:s.v a');
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            $data_row['time_zone'] = $participantTimeZone;
            $messages[] = $data_row;
        }

        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => Message::countForDatatable($study),
                'recordsFiltered' => Message::countFilteredForDatatable($filter, $study),
                'data' => $messages,
                'error_message' => $error_message,
            )
        );
    }

    public static function listIndividualMessagesDT(User $user) {
        header('Content-Type: application/json');

        if (empty($_GET['uuid'])){
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
        $error_message = "";
        $idx = $start;
        $results = Message::listIndividualForDatatable($_GET['uuid'], $start, $length, $order_by, $order_dir, $filter);

        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();
            $data_row['DT_RowId'] = $idx++;
            $participantTimeZone = Participant::getTimeZoneFromUUID($data_row['participant_uuid']);

            try {
                $date = new DateTime($data_row['ts'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone($participantTimeZone));
                $data_row['ts'] = $date->format('m/d/Y h:i:s a');
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            $messages[] = $data_row;
        }
        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => Message::countIndividualForDatatable($_GET['uuid']),
                'recordsFiltered' => Message::countIndividualFilteredForDatatable($_GET['uuid'], $filter),
                'data' => $messages,
                'error_message' => $error_message,
            )
        );
    }

    public static function sendMessage(User $user) {
        header('Content-Type: application/json');
        global $CONFIG;
        $success = false;
        $error_message = null;
        if (empty($_POST['participant_uuid'])){
            $error_message = "1 or more participants must be selected.";
        } else if (empty($_POST['body'])) {
            $error_message = "Your message must not be empty.";
        } else if(empty($_POST['study'])) {
            $error_message = "Study parameter was not set. Please refresh and try again.";
        } else if (empty($_POST['time_to_send'])) {
            $error_message = "Scheduled message time is not formatted properly.";
        } else {
            $participant_uuid = $_POST['participant_uuid'];
            $message_json = $_POST['body'];
            $message_json = str_replace(array("\n", "\r", "\t", "\\t", "\\n", "\\r"), " ", $message_json);
            $message_json = json_encode($message_json);
            $message_json = '{"Body":'.$message_json.'}';
            $study = $_POST['study'];
            $scheduledTime = $_POST['time_to_send'];

            try {
                foreach($participant_uuid as $uuid) {
                    // normal message to send now
                    if ($scheduledTime == -1){
                        $date = new DateTime();
                        $date = $date->format('Y-m-d H:i:s');
                        $message = Message::create($uuid, $date, Message::OUTGOING, $message_json, $study);
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
                    } else {
                        // scheduled message
                        $scheduledFor = new DateTime($scheduledTime, new DateTimeZone(Participant::getTimeZone($uuid)));
                        $scheduledFor->setTimezone(new DateTimeZone('UTC'));
                        $scheduledFor = $scheduledFor->format('Y-m-d H:i:s');
                        $toNumber = Participant::getPhoneNumber($uuid);
                        $fromNumber = null;
                        if ($study == "Default") {
                            $fromNumber = $CONFIG['twilio']['from_number'];
                        }

                        $message = ScheduledMessage::create($uuid, $scheduledFor, $message_json, $toNumber, $fromNumber, $study);

                        if (is_null($message)) {
                            throw new Exception('Failed to schedule message.');
                        } else {
                            $success = true;
                        }

                    }
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        }
        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function exportAsCSV(User $user) {
        if (empty($_GET['study'])){
            header('Content-Type: application/json');
            $ret = array('success' => false, 'error_message' => "You must provide a study.");
            echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
        } else {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="messages_export_'.date('m-d-Y_H.i.s').'.csv";');
            $success = false;
            $error_message = null;
            $data = null;

            $messageData = Message::allForCSV($_GET['study']);

            $delimiter = ",";
            // Create a file pointer
            $f = fopen('php://memory', 'w');

            // Output each row of the data, format line as csv and write to file pointer
            foreach ($messageData as $row) {
                $lineData = array();
                foreach($row as $key) {
                    $lineData[] = $key;
                }
                fputcsv($f, $lineData, $delimiter);
            }

            // Move back to beginning of file
            fseek($f, 0);

            //output all remaining data on a file pointer
            fpassthru($f);

            fclose($f);
        }
    }
}