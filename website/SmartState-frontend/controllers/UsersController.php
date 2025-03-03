<?php

const USERS_VIEWS_DIR = __DIR__ . '/../views/users/';

require_once MODELS_DIR . 'User.php';

class UsersController {
    public static function index(User $user): void {
        require USERS_VIEWS_DIR . 'index.php';
    }

    public static function listForDatatable(User $user): void {
        header("Content-Type: application/json");
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
        $data = array();
        $idx = $start;
        $results = User::listForDatatable($start, $length, $order_by, $order_dir, $filter);
        foreach ($results as $result) {
            $data_row = $result->jsonSerialize();
            $roles = array();
            foreach ($data_row["roles"] as $role){
                $roles[$role] = User::getRoleNameFromId($role);
            }
            $data_row["roles"] = json_encode($roles, true);

            $data_row['DT_RowId'] = $idx++;
            $data[] = $data_row; // new syntax for array_push()
        }
        echo json_encode(
            array(
                'draw' => (isset($_GET['draw'])) ? intval($_GET['draw']) : 0,
                'recordsTotal' => User::countForDatatable(),
                'recordsFiltered' => User::countFilteredForDatatable($filter),
                'data' => $data,
            )
        );
    }

    public static function submit(User $user): void {
        header("Content-Type: application/json");
        $success = false;
        $error_message = null;
        $user = null;
        $adminPart = null;
        $action = "update";
        if (empty($_POST['id'])) {
            $action = "create";
        }
        if (!isset($_POST['email'])) {
            $error_message = "Please enter a valid email address.";
        } else if (!isset($_POST['number'])) {
            $error_message = "Please enter a valid phone number.";
        } else if (!isset($_POST['timezone'])) {
            $error_message = "Please enter a timezone for this user.";
        } else if (!isset($_POST['role'])) {
            $error_message = "Please select a role for this user.";
        } else {
            $id = $_POST['id'];
            $email = $_POST['email'];
            $number = $_POST['number'];
            $timezone = $_POST['timezone'];
            $role = $_POST['role'];
            try {
                if ($action == "create") {
                    $user = User::withEPPN($email);
                    if ($user != null) {
                        $error_message = "User with email [{$user->getEPPN()}] already exists";
                    } else {
                        $user = User::createBeforeLogin($email, $number, $timezone, $role);
                        $adminPart = Participant::createAdmin(json_encode(['first_name' => $user->getFirstName(), 'last_name' => $user->getLastName(), 'number' => $number, 'time_zone' => $timezone]), "ADMIN");
                        $success = true;
                    }
                } else {
                    $user = User::update($id, $email, $number, $timezone, $role);
                    Participant::updateAdmin(json_encode(['first_name' => $user->getFirstName(), 'last_name' => $user->getLastName(), 'number' => $number, 'time_zone' => $timezone]), 'ADMIN');
                    $success = true;
                }
            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        }
        $ret = array('success' => $success, 'action' => $action, 'error_message' => $error_message, 'user' => $user, 'adminPart' => $adminPart);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function getRoles(User $user): void {
        header("Content-Type: application/json");
        $success = false;
        $error_message = null;
        $roles = null;

        try {
            $roles = User::getAllRoles();
            $success = true;
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
        $ret = array('success' => $success, 'error_message' => $error_message, 'roles' => $roles);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }

    public static function deleteUser(User $user): void {
        header("Content-Type: application/json");
        $success = false;
        $error_message = null;

        if (empty($_POST['id'])) {
            $error_message = "User ID not found.";
        }
        else {
            $id = $_POST['id'];
            try {
                $user = User::withId($id);
                if ($user != null) {
                    $status = User::delete($id);

                    if (!is_null($user->getNumber())) {
                        Participant::deleteAdmin($user->getNumber());
                    }
                    if ($status) {
                        $success = true;
                    } else {
                        $error_message = "User could not be deleted.";
                    }
                } else {
                    $error_message = "User not found.";
                }

            } catch (Exception $e) {
                $error_message = $e->getMessage();
            }
        }
        $ret = array('success' => $success, 'error_message' => $error_message);
        echo json_encode((object) array_filter($ret, function($value) { return $value !== null; }));
    }
}