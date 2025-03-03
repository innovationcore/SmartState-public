<?php

require_once __DIR__ . '/../utilities/db.php';
include_once MODELS_DIR . 'User.php';
include_once MODELS_DIR . 'Participant.php';

class ParticipantsController {
    public static function index(User $user) {
        require PARTICIPANTS_VIEWS_DIR . 'index.php';
    }

    public static function stateIndex(User $user) {
        require PARTICIPANTS_VIEWS_DIR . 'state-log.php';
    }
    
    public static function getAllParticipants(User $user) {
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

    public static function listStateDT(User $user) {
        header('Content-Type: application/json');

        if (empty($_GET['uuid']) || empty($_GET['protocol'])) {
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
        $error_message = "";
        $idx = $start;
        $results = Participant::listStateForDatatable($_GET['uuid'], $_GET['protocol'], $start, $length, $order_by, $order_dir, $filter);

        foreach ($results as $result) {
            $data_row = $result;
            $data_row['DT_RowId'] = $idx++;
            $participantTimeZone = Participant::getTimeZoneFromUUID($_GET['uuid']);

            try {
                $date = new DateTime($data_row['ts'], new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone($participantTimeZone));
                $data_row['ts'] = $date->format('m/d/Y h:i:s a');
                $data_row['ts'] .= ' ';
                $data_row['ts'] .= $participantTimeZone;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
            $logs[] = $data_row;
        }
        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => Participant::countForStateDatatable($_GET['uuid']),
                'recordsFiltered' => Participant::countFilteredForStateDatatable($_GET['uuid'], $filter),
                'data' => $logs,
                'error_message' => $error_message
            )
        );
    }

    public static function getCurrentState(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $state = null;

        if(empty($_GET['uuid']) || empty($_GET['protocol'])) {
            $error_message = 'No participant UUID/protocol provided';
        } else {
            $state = Participant::getCurrentStateString($_GET['uuid'], $_GET['protocol']);
            if(!is_null($state)) {
                $success = true;
            } else {
                $error_message = 'No participant state found';
            }
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'state' => $state);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function getTimeZone(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $timezone = null;

        if(empty($_GET['uuid'])) {
            $error_message = 'No participant UUID provided';
        } else {
            $timezone = Participant::getTimeZone($_GET['uuid']);
            if(!is_null($timezone)) {
                $success = true;
            } else {
                $error_message = 'No participant time zone found';
            }
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'time_zone' => $timezone);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function fillGroupDropdown(User $user) {
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

    public static function fillLocationDropdown(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $locations = [];

        try {
            $results = Participant::allLocations();
            foreach ($results as $result) {
                $locations[] = $result['location'];
            }
            $success = true;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }


        $ret = array('success' => $success, 'error_message' => $error_message, 'data' => $locations);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function fillTimeZoneDropdown(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $timeZones = [];
        if (isset($_GET['location']) && !empty($_GET['location'])) {
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

    public static function addParticipant(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        if (isset($_POST['info']) && isset($_POST['study'])) {
            $info = $_POST['info'];
            $study = $_POST['study'];
            $json = json_encode($info);

            try {
                Participant::create($json, $study);
                $success = true;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = "Please provide info and study for the participant.";
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function getParticipant(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = "";
        $json = null;
        $participants = [];

        if (!empty($_GET['id'])) {
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
        // Decode JSON to an associative array
        $decoded = json_decode($json, true);

        // Extract values directly into the participant array
        $participants[] = array(
            'first_name'    => $decoded['first_name'] ?? null,
            'last_name'     => $decoded['last_name'] ?? null,
            'number'        => isset($decoded['number']) ? substr($decoded['number'], 2) : null,
            'email'         => $decoded['email'] ?? null,
            'group'         => $decoded['group'] ?? null,
            'devEUI'        => $decoded['devEUI'] ?? null,
            'time_zone'     => $decoded['time_zone'] ?? null,
            'location'      => isset($decoded['time_zone']) ? explode("/", $decoded['time_zone'])[0] : null
        );

        $ret = array('success' => $success, 'error_message' => $error_message, 'data' => $participants);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function updateParticipant(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        if (!empty($_POST['id']) && !empty($_POST['info']) && !empty($_POST['study'])) {
            $uuid = $_POST['id'];
            $info = $_POST['info'];
            $study = $_POST['study'];
            $participant = null;

            try {
                $participant = Participant::update($uuid, $info, $study);
                $success = true;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = "No ID or info specified";
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function deleteParticipant(User $user) {
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

    public static function listDT(User $user) {
        header('Content-Type: application/json');

        $study = "Default";
        if (!empty($_GET['study']))
            $study = $_GET['study'];
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

        // Initialize participants array and start index
        $participants = [];
        $idx = $start;

        // Fetch results from Participant class
        $results = Participant::listForDatatable($start, $length, $order_by, $order_dir, $filter, $study);

        // Iterate through results and build the participant list
        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();
            $decoded = json_decode($data_row['json'], true);

            // Combine first and last name
            $name = "{$decoded['first_name']} {$decoded['last_name']}";

            // Add participant details to the participants array
            $participants[] = [
                'name'      => $name,
                'number'    => $decoded['number'],
                'email'     => $decoded['email'],
                'group'     => $decoded['group'],
                'dev_eui'   => $decoded['devEUI'],
                'id'        => $data_row['uuid'],
                'DT_RowId'  => $idx++
            ];
        }

        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => Participant::countForDatatable(),
                'recordsFiltered' => Participant::countFilteredForDatatable($filter),
                'data' => $participants,
            )
        );
    }

    public static function getStateMachine(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = "";
        $content = "";

        if(empty($_POST['protocol'])) {
            $error_message = 'You must provide a protocol.';
        } else {
            $protocol = $_POST['protocol'];
            $content = file_get_contents("./img/".$protocol.".gv");
            $success = true;
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'content' => $content);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function getHomeStats(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = "";
        $participantStats = array();
        $surveyStats = array();

        try {
            $participantStats = Participant::getParticipantStats();
            $surveyStats = Survey::getSurveyStats();
            $success = true;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }

        $ret = array(
            'success' => $success,
            'error_message' => $error_message,
            'patientStats' => $participantStats,
            'surveyStats' => $surveyStats
        );
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }
}