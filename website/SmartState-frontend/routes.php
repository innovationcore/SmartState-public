<?php
    define('CONFIG_FILE', __DIR__ . '/config.php');
    define('MODELS_DIR', __DIR__ . '/models/');
    define('UTILITIES_DIR', __DIR__ . '/utilities/');
    define('VIEWS_DIR', __DIR__ . '/views/');
    define('MESSAGES_VIEWS_DIR', __DIR__ . '/views/messages/');
    define('PARTICIPANTS_VIEWS_DIR', __DIR__ . '/views/participants/');
    define('PROTOCOL_TYPES_VIEWS_DIR', __DIR__ . '/views/protocol_types/');
    define('SURVEY_VIEWS_DIR', __DIR__ . '/views/survey/');

    include_once __DIR__ . '/controllers/RootController.php';
    include_once __DIR__ . '/controllers/MessagesController.php';
    include_once __DIR__ . '/controllers/UsersController.php';
    include_once __DIR__ . '/controllers/ParticipantsController.php';
    include_once __DIR__ . '/controllers/ProtocolTypesController.php';
    include_once __DIR__ . '/controllers/SurveyController.php';

    /**
     * @param string $redirect
     * @param false $role_access
     * @param string $admin_redirect
     * @return UserSession
     */
    function get_session($redirect = '/login', $role_access = [], $admin_redirect = '/'): UserSession {
        $session = UserSession::withSessionID(session_id());
        if (is_null($session))
            header('Location: ' . $redirect);
        else {
            if (is_null($session->getUser()))
                header('Location: /logout');
            else if (!in_array($session->getUser()->getRole(), $role_access)){
                if(!empty($role_access)) {// if role_access is empty all roles are allowed
                    header('Location: ' . $admin_redirect);
                }
            }
        }
        return $session;
    }

    $router = new AltoRouter();
    // custom match regex
    $router->addMatchTypes(array('uuid' => '([A-Za-z0-9-]+)'));

    /* RootController routes */
    try {
        $router->map('GET', '/', function() {
            RootController::index(get_session());
        }, 'dashboard');
        $router->map('GET', '/login', function() {
            RootController::login();
        }, 'login');
        $router->map('POST', '/login', function() {
            try {
                RootController::do_login(strtolower($_POST['user']), $_POST['password'], 0);
            } catch (Exception $e) {
                $_SESSION['LOGIN_ERROR'] = $e->getMessage();
                header('Location: /login');
            }
        });
        $router->map('GET', '/logout', function() {
            RootController::logout();
        }, 'logout');
    } catch (Exception $e) {
        die("Failed to create route(s) from RootController section: " . $e->getMessage());
    }

    /* MessagesController routes */
    try {
        $router->map('GET', '/messages', function() {
            MessagesController::index(get_session("/", [0,1]));
        }, 'messages-index');
        $router->map('GET', '/messages/log', function() {
            MessagesController::logIndex(get_session("/", [0,1]));
        }, 'messages-log');
        $router->map('POST', '/messages/get-message-log', function() {
            MessagesController::listIndividualMessagesDT(get_session("/", [0,1]));
        }, 'messages-getlogs');
        $router->map('GET', '/messages/list', function() {
            MessagesController::listDT(get_session("/", [0,1]));
        }, 'messages-list');
        $router->map('POST', '/messages/send', function() {
            MessagesController::sendMessage(get_session("/", [0,1]));
        }, 'messages-send');
        $router->map('GET', '/messages/export', function() {
            MessagesController::exportAsCSV(get_session("/", [0,1]));
        }, 'messages-export');
    } catch (Exception $e) {
        die("Failed to create route(s) from MessageController section: " . $e->getMessage());
    }

    /* ParticipantsController routes */
    try {
        $router->map('GET', '/participants', function() {
            ParticipantsController::index(get_session(get_session("/", [0,1])));
        }, 'participants-index');
        $router->map('GET', '/participants/create', function() {
            ParticipantsController::createParticipant(get_session("/", [0,1]));
        }, 'participants-create');
        $router->map('GET', '/participants/state-log', function() {
            ParticipantsController::stateIndex(get_session("/", [0,1]));
        }, 'participants-statelog');
        $router->map('GET', '/participants/all', function() {
            ParticipantsController::getAllParticipants(get_session("/", [0,1]));
        }, 'participants-all');
        $router->map('POST', '/participants/get-state-log', function() {
            ParticipantsController::listStateDT(get_session("/", [0,1]));
        }, 'participants-liststatedt');
        $router->map('POST', '/participants/get-current-state', function() {
            ParticipantsController::getCurrentState(get_session("/", [0,1]));
        }, 'participants-getcurrstate');
        $router->map('GET', '/participants/fill-group-dropdown', function() {
            ParticipantsController::fillGroupDropdown(get_session("/", [0,1]));
        }, 'participants-fillgroupdropdown');
        $router->map('GET', '/participants/fill-location-dropdown', function() {
            ParticipantsController::fillLocationDropdown(get_session("/", [0,1]));
        }, 'participants-filllocationdropdown');
        $router->map('GET', '/participants/fill-timezone-dropdown', function() {
            ParticipantsController::fillTimeZoneDropdown(get_session("/", [0,1]));
        }, 'participants-filltimezonedropdown');
        $router->map('POST', '/participants/add-participant', function() {
            ParticipantsController::addParticipant(get_session("/", [0,1]));
        }, 'participants-addparticipant');
        $router->map('GET', '/participants/list', function() {
            ParticipantsController::listDT(get_session("/", [0,1]));
        }, 'participants-list');
        $router->map('GET', '/participants/get-participant', function() {
            ParticipantsController::getParticipant(get_session("/", [0,1]));
        }, 'participants-getparticipant');
        $router->map('POST', '/participants/update-participant', function() {
            ParticipantsController::updateParticipant(get_session("/", [0,1]));
        }, 'participants-updateparticipant');
        $router->map('POST', '/participants/delete-participant', function() {
            ParticipantsController::deleteParticipant(get_session("/", [0,1]));
        }, 'participants-deleteparticipant');
        $router->map('POST', '/participants/get-time-zone', function() {
            ParticipantsController::getTimeZone(get_session("/", [0,1]));
        }, 'participants-gettimezone');
        $router->map('POST', '/participants/get-state-machine', function() {
            ParticipantsController::getStateMachine(get_session("/", [0,1]));
        }, 'participants-getstatemachine');
    } catch (Exception $e) {
        die("Failed to create route(s) from ParticipantsController section: " . $e->getMessage());
    }

    /* ProtocolTypesController routes */
    try {
        $router->map('GET', '/protocol-types', function() {
            ProtocolTypesController::index(get_session("/", [0,1]));
        }, 'protocoltypes-index');
        $router->map('POST', '/protocol-types/create', function() {
            ProtocolTypesController::createProtocol(get_session("/", [0,1]));
        }, 'protocoltypes-create');
        $router->map('GET', '/protocol-types/list', function() {
            ProtocolTypesController::listDT(get_session("/", [0,1]));
        }, 'protocoltypes-list');
        $router->map('GET', '/protocol-types/all', function() {
            ProtocolTypesController::getAll(get_session("/", [0,1]));
        }, 'protocoltypes-all');
        $router->map('POST', '/protocol-types/get-name', function() {
            ProtocolTypesController::getNameFromID(get_session("/", [0,1]));
        }, 'protocoltypes-getname');
        $router->map('POST', '/protocol-types/update', function() {
            ProtocolTypesController::updateProtocol(get_session("/", [0,1]));
        }, 'protocoltypes-update');
        $router->map('POST', '/protocol-types/delete', function() {
            ProtocolTypesController::deleteProtocol(get_session("/", [0,1]));
        }, 'protocoltypes-delete');
    } catch (Exception $e) {
        die("Failed to create route(s) from ParticipantTypesController section: " . $e->getMessage());
    }

    try {
        /* UsersController routes */
        $router->map('GET', '/users', function() {
            UsersController::index(get_session("/", [0,1]));
        }, 'users-index');
        $router->map('GET', '/users/list', function() {
            UsersController::listForDatatable(get_session("/", [0,1]));
        }, 'users-for-datatable');
        $router->map('POST', '/users/submit', function() {
            UsersController::submit(get_session("/", [0,1]));
        }, 'users-update');
        $router->map('GET', '/users/getUser', function() {
            UsersController::getUser(get_session("/", [0,1]));
        }, 'users-get');
        $router->map('GET', '/users/getRoles', function() {
            UsersController::getRoles(get_session("/", [0,1]));
        }, 'users-roles');
        $router->map('POST', '/users/deleteUser', function() {
            UsersController::deleteUser(get_session("/", [0,1]));
        }, 'users-delete');
    } catch (Exception $e) {
        die("Failed to create route(s) from UsersController section: " . $e->getMessage());
    }

    try {
        /* SurveyController routes */
        $router->map('GET', '/survey/take-survey/[*:token]/[*:partUUID]', function($token, $partUUID) {
            SurveyController::index($token, $partUUID);
        }, 'survey-index');
        $router->map('POST', '/survey/done', function() {
            SurveyController::saveSurvey();
        }, 'survey-done');
        $router->map('GET', '/survey/thank-you', function() {
            SurveyController::thankYou();
        }, 'survey-thankyou');
        $router->map('GET', '/survey/view', function() {
            SurveyController::view(get_session("/", [0,1]));
        }, 'survey-view');
        $router->map('GET', '/survey/list', function() {
            SurveyController::listDT(get_session("/", [0,1]));
        }, 'survey-dt');
    } catch (Exception $e) {
        die("Failed to create route(s) from UsersController section: " . $e->getMessage());
    }

    $match = $router->match();

    // Call closure or throw 404 status
    if ($match && is_callable($match['target'])) {
        call_user_func_array($match['target'], $match['params']);
    } else {
        // No route was matched
        $from = $_SERVER['REQUEST_URI'];
        if (strlen($from) > 20)
            $from = substr($from, 0, 20) . '...';
        $_SESSION['FLASH_ERROR'] = "No page exists at {$from}";
        if(isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: /');
        }
    }