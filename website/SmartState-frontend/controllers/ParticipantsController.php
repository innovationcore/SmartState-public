<?php

require_once __DIR__ . '/../utilities/db.php';
include_once MODELS_DIR . 'User.php';
include_once MODELS_DIR . 'Participant.php';

class ParticipantsController {
    public static function index(UserSession $userSession) {
        require PARTICIPANTS_VIEWS_DIR . 'index.php';
    }

    public static function stateIndex(UserSession $userSession) {
        require PARTICIPANTS_VIEWS_DIR . 'state-log.php';
    }
    
    public static function getAllParticipants(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        $participants = array();
        $results = null; 
        try {
            $results = Participant::all();
            $success = true;
            foreach ($results as $result) {
                $data_row = $result->jsonSerialize();
                array_push($participants, $data_row);
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
        
        $ret = array('success' => $success, 'error_message' => $error_message, 'participants' => $participants);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function listStateDT(UserSession $userSession) {
        header('Content-Type: application/json');

        if (empty($_POST['uuid']) || is_null($_POST['uuid'])) {
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
        $logs = array();
        $idx = $start;
        $results = Participant::listStateForDatatable($_POST['uuid'], $_POST['protocol'], $start, $length, $order_by, $order_dir, $filter);
        foreach ($results as $result) {
            $data_row = $result;
            $data_row['DT_RowId'] = $idx++;
            $participantTimeZone = Participant::getTimeZoneFromUUID($_POST['uuid']);

            try {
                $date = new DateTime($data_row['TS'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone($participantTimeZone));
                $data_row['TS'] = $date->format('m/d/Y h:i:s a');
                $data_row['TS'] .= ' ';
                $data_row['TS'] .= $participantTimeZone;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            array_push($logs, $data_row);
        }
        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => intval(Participant::countForStateDatatable($_POST['uuid'])),
                'recordsFiltered' => intval(Participant::countFilteredForStateDatatable($_POST['uuid'], $filter)),
                'data' => $logs,
            )
        );
    }

    public static function getCurrentState(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $state = null;

        if(empty($_POST['uuid']) || is_null($_POST['uuid']) && (empty($_POST['protocol']) || is_null($_POST{'protocol'}))) {
            $error_message = 'No participant UUID/protocol provided';
        } else {
            $state = Participant::getCurrentStateString($_POST['uuid'], $_POST['protocol']);
            if(!is_null($state)) {
                $success = true;
            } else {
                $error_message = 'No participant state found';
            }
        }
        
        $ret = array('success' => $success, 'error_message' => $error_message, 'state' => $state);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function getTimeZone(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $timezone = null;

        if(empty($_POST['uuid']) || is_null($_POST['uuid'])) {
            $error_message = 'No participant UUID provided';
        } else {
            $timezone = Participant::getTimeZone($_POST['uuid']);
            if(!is_null($timezone)) {
                $success = true;
            } else {
                $error_message = 'No participant time zone found';
            }
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'time_zone' => $timezone);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function fillGroupDropdown(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $protocols = [];

        try {
            $results = Participant::allProtocols();
            foreach ($results as $result) {
                $data_row = $result->jsonSerialize();
                array_push($protocols, $data_row);
            }
            $success = true;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'data' => $protocols);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function fillLocationDropdown(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $locations = [];

        try {
            $results = Participant::allLocations();
            foreach ($results as $result) {
                array_push($locations, $result['location']);
            }
            $success = true;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }


        $ret = array('success' => $success, 'error_message' => $error_message, 'data' => $locations);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function fillTimeZoneDropdown(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $timeZones = [];
        if (isset($_GET['location'])) {
            $location = $_GET['location'];
            $results = Participant::getZonesWithLocation($location);
            foreach ($results as $result) {
                array_push($timeZones, $result['name']);
            }
            $success = true;
        } else {
            $error_message = "No location specified";
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'data' => $timeZones);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function addParticipant(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        if (isset($_POST['info'])) {
            $info = $_POST['info'];
            $json = json_encode($info);
            $participant = null;

            try {
                $participant = Participant::create($json);
                $success = true;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = "Please provide info for the participant.";
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function getParticipant(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $json = null;
        $participants = [];

        if (isset($_GET['id'])) {
            $uuid = $_GET['id'];
            try {
                $participant = Participant::withID($uuid);
                if ($participant != null) {
                    $json = $participant->getJSON();
                    $success = true;
                } else {
                    $error_message = "Participant with ID [{$uuid}] not found";
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = "No ID specified";
        }
        $decoded = json_decode($json, true);
        $firstName = $decoded['first_name'];
        $lastName = $decoded['last_name'];
        $phoneNumber = substr($decoded['number'], 2);
        $devEUI = $decoded['devEUI'];
        $group = $decoded['group'];
        $timeZone = $decoded['time_zone'];
        $split = explode("/", $timeZone);
        $location = $split[0];
        array_push($participants, array('first_name' => $firstName, 'last_name' => $lastName, 'number' => $phoneNumber, 'devEUI'=> $devEUI, 'group' => $group, 'location' => $location, 'time_zone' => $timeZone));


        $ret = array('success' => $success, 'error_message' => $error_message, 'data' => $participants);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function updateParticipant(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        if (isset($_POST['id']) && isset($_POST['info'])) {
            $uuid = $_POST['id'];
            $info = $_POST['info'];
            $participant = null;

            try {
                $participant = Participant::update($uuid, $info);
                $success = true;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = "No ID or name specified";
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function deleteParticipant(UserSession $userSession) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        
        if (isset($_POST['id'])) {
            $uuid = $_POST['id'];
            try {
                $isDeleted = Participant::delete($uuid);
                if ($isDeleted) {
                    $success = true;
                } else {
                    $error_message = "Could not delete participant.";
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = "No ID specified";
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
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
        $participants = array();
        $idx = $start;
        $results = Participant::listForDatatable($start, $length, $order_by, $order_dir, $filter);
        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();
            $data_row['DT_RowId'] = $idx++;
            $json = $data_row['json'];
            $decoded = json_decode($json, true);
            $name = $decoded['first_name'] . ' ' . $decoded['last_name'];
            $group = $decoded['group'];
            array_push($participants, array('name' => $name, 'number' => $decoded['number'], 'group' => $group, 'id' => $data_row['uuid'], 'DT_RowId' => $data_row['DT_RowId']));
        }
        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => intval(Participant::countForDatatable()),
                'recordsFiltered' => intval(Participant::countFilteredForDatatable($filter)),
                'data' => $participants,
            )
        );
    }
}