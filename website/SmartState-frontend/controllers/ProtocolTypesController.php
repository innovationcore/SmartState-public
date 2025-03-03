<?php
include_once MODELS_DIR . 'User.php';
include_once MODELS_DIR . 'ProtocolType.php';

class ProtocolTypesController {
    public static function index(User $user) {
        require PROTOCOL_TYPES_VIEWS_DIR . 'index.php';
    }

    public static function createProtocol(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        if (!empty($_POST['name']) && !empty($_POST['study'])) {
            $name = $_POST['name'];
            $study = $_POST['study'];
            $protocol = null;
            $code = null;

            try {
                $protocol = ProtocolType::create($name, $study);
            } catch (PDOException $e) {
                $code = $e->getCode();
            }
            if ($protocol != null && $code != 23000) { // 23000: unique constraint violation
                $success = true;
            } else {
                $error_message = "Could not create protocol [{$name}]. Name must be unique.";
            }
        } else {
            $error_message = "Please provide a name for the protocol.";
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
        $protocols = array();
        $idx = $start;
        $results = ProtocolType::listForDatatable($start, $length, $order_by, $order_dir, $filter, $study);
        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();
            $data_row['DT_RowId'] = $idx++;
            array_push($protocols, $data_row);
        }
        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => intval(ProtocolType::countForDatatable($study)),
                'recordsFiltered' => intval(ProtocolType::countFilteredForDatatable($filter, $study)),
                'data' => $protocols,
            )
        );
    }

    public static function getNameFromID(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;
        $name = null;

        if (isset($_POST['id'])) {
            $id = $_POST['id'];
            $protocol = ProtocolType::withID($id);
            if ($protocol != null) {
                $name = $protocol->getName();
                $success = true;
            } else {
                $error_message = "Protocol type with ID [{$id}] not found";
            }
        } else {
            $error_message = "No ID specified";
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'name' => $name);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function updateProtocol(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        if (isset($_POST['id']) && isset($_POST['name'])) {
            $id = $_POST['id'];
            $name = $_POST['name'];
            $protocol = null;
            $code = null;

            try {
                $protocol = ProtocolType::update($id, $name);
            } catch (PDOException $e) {
                $code = $e->getCode();
                $message = $e->getMessage();
            }
            if ($protocol != null && $code != 23000) { // 23000: unique constraint violation
                $new_name = $protocol->getName();
                if ($new_name == $name) {
                    $success = true;
                } else {
                    $error_message = "Could not update protocol type.";
                }
            } else {
                $error_message = "Could not update protocol [{$message}]. Name must be unique.";
            }
        } else {
            $error_message = "No ID or name specified";
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function deleteProtocol(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = null;

        if (isset($_POST['id'])) {
            $id = $_POST['id'];
            $isDeleted = ProtocolType::delete($id);
            if ($isDeleted) {
                $success = true;
            } else {
                $error_message = "Could not delete protocol type.";
            }
        } else {
            $error_message = "No ID specified";
        }

        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function getAll(User $user) {
        header('Content-Type: application/json');
        $success = false;
        $error_message = "";
        $protocols = array();
        $actives = "";

        if (isset($_GET['uuid']) && !empty($_GET['uuid']) && isset($_GET['study']) && !empty($_GET['study'])){
            $part_uuid = $_GET['uuid'];
            $study = $_GET['study'];
            try {
                $results = ProtocolType::all($study);
                $actives = ProtocolType::getActives($part_uuid);
                foreach ($results as $result) {
                    $data_row = $result->jsonSerialize();
                    $protocols[] = $data_row;
                }
                $success = true;
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        } else {
            $error_message = "No participant ID or study name provided.";
        }

        $ret = array('success' => $success, 'error_message' => $error_message, 'protocols' => $protocols, 'actives' => $actives);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }
}