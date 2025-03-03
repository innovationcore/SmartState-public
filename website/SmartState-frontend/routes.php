<?php
    const CONFIG_FILE = __DIR__ . '/config.php';
    const MODELS_DIR = __DIR__ . '/models/';
    const UTILITIES_DIR = __DIR__ . '/utilities/';
    const VIEWS_DIR = __DIR__ . '/views/';
    const MESSAGES_VIEWS_DIR = __DIR__ . '/views/messages/';
    const PARTICIPANTS_VIEWS_DIR = __DIR__ . '/views/participants/';
    const PROTOCOL_TYPES_VIEWS_DIR = __DIR__ . '/views/protocol_types/';
    const SURVEY_VIEWS_DIR = __DIR__ . '/views/survey/';

    include_once __DIR__ . '/controllers/RootController.php';
    include_once __DIR__ . '/controllers/MessagesController.php';
    include_once __DIR__ . '/controllers/UsersController.php';
    include_once __DIR__ . '/controllers/ParticipantsController.php';
    include_once __DIR__ . '/controllers/ProtocolTypesController.php';
    include_once __DIR__ . '/controllers/SurveyController.php';
    include_once __DIR__ . '/controllers/MetricsController.php';

    // global definition
    $CONFIG = include(CONFIG_FILE);
    $rootURL = $CONFIG['rootURL'];

    /**
     * Determines if the current request accepts JSON response
     * @return bool
     */
    function isJsonRequest(): bool {
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Handles JSON and other requests with unauthorized users
     * @return bool
     */
    function unauthorized(int $responseCode=401, string $error="Unauthorized", string $unauthorized_redirect="/no-access"): bool {
        global $rootURL;
        if (isJsonRequest()) {
            http_response_code($responseCode);
            header('Content-Type: application/json');
            $response = ['error' => $error];
            echo json_encode($response);
            die();
        } else {
            http_response_code($responseCode);
            header('Location: ' . $rootURL.$unauthorized_redirect);
            die();
        }
    }

    /**
     * @param array $requiredRoles
     * @param bool $allowAPI
     * @param string $login_redirect
     * @param string $unauthorized_redirect
     * @return User
     * @throws Exception
     */
    function get_session(array $requiredRoles=[], bool $allowAPI=false, string $login_redirect = '/login', string $unauthorized_redirect = '/no-access'): ?User {
        global $rootURL;

        // Set the redirect if accepts HTML
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $acceptHeader = $_SERVER['HTTP_ACCEPT'];
            if (strpos($acceptHeader, 'text/html') !== false) {
                if ($_SERVER['REQUEST_URI'] != $rootURL."/"){
                    $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
                }
            }
        }

        if (!isset($_SESSION['caai_session_id']) && count($requiredRoles) > 0) {
            // session not set and role access required
            // log the user in
            $apiKeysActive = false; //Plugin::isPluginActiveByName("api_keys"); // this is in development and will be added at a later time
            $authorizationHeaderSet = isset($_SERVER["HTTP_AUTHORIZATION"]) && $_SERVER["HTTP_AUTHORIZATION"];
            if (!$apiKeysActive || !$authorizationHeaderSet) {
                // Session not set and API Key not set
                header('Location: ' . $rootURL.$login_redirect);
                die();
            } else {
                // API Key is set and Session is not
                if (strpos($_SERVER["HTTP_AUTHORIZATION"], "Bearer ") === 0) {
                    // Check if the Authorization header is present and starts with "Bearer "
                    $apiKeyVal = substr($_SERVER["HTTP_AUTHORIZATION"], 7);
                    if ($apiKeyVal) {
                        $apiKey = false; //APIKey::withId($apiKeyVal); //under development
                        if ($apiKey){
                            $user = $apiKey->getUser();
                            if ($user) {
                                return $user;
                            } else {
                                error_log("Invalid API Key");
                                unauthorized();
                            }
                        } else {
                            error_log("Invalid token");
                            unauthorized();
                        }
                    } else {
                        error_log("Invalid token");
                        unauthorized();
                    }

                }
            }
        } else if (!isset($_SESSION['caai_session_id']) && count($requiredRoles) == 0) {
            // session not set and does not require role access
            $newSession = UserSession::createEmptyUserSession();
            return $newSession->getUser();
        } else {
            $session_id = $_SESSION['caai_session_id'];
            $session = UserSession::withSessionID($session_id);
            if ($session === null) {
                unset($_SESSION['caai_session_id']);
                header('Location: ' . $rootURL.$login_redirect);
                die();
            }
            $expiration_time = new DateTime($session->getExpires(), new DateTimeZone('UTC'));
            $current_time = new DateTime('now', new DateTimeZone('UTC'));

            // Check if the session has expired
            if ($current_time > $expiration_time) {
                UserSession::delete($session_id);
                //log the user back in
                header('Location: ' . $rootURL.$login_redirect);
                die();
            } else {
                $config = include(CONFIG_FILE);
                $current_time->add(new DateInterval('PT' . $config['sessions']['max-age'] . 'S'));
                $expireTime = $current_time->format('Y-m-d H:i:s');
                $session->setExpires($expireTime);
                $session->save();
            }

            // check if route requires role access and user has access
            $routeRoleAccess = count($requiredRoles) == 0;
            foreach ($requiredRoles as $role){
                $routeRoleAccess = $routeRoleAccess || $session->getUser()->hasRole($role);
            }
            if (!$routeRoleAccess) {
                unauthorized();
            }
            return $session->getUser();
        }
        return null;
    }

    $router = new AltoRouter();
    // custom match regex
    $router->addMatchTypes(array('uuid' => '([A-Za-z0-9-]+)'));

/* RootController routes */
try {
    $router->map('GET', $rootURL.'/', function() {
        RootController::index(get_session(["User", "Study Admin", "Super Admin"]));
    }, 'dashboard');
    $router->map('GET', $rootURL.'/login', function() {
        RootController::login();
    }, 'login');
    $router->map('GET', $rootURL.'/callback', function() {
        RootController::callback();
    }, 'callback');
    $router->map('GET', $rootURL.'/logout', function() {
        RootController::logout();
    }, 'logout');
    $router->map('GET', $rootURL.'/no-access', function() {
        RootController::no_access(get_session());
    }, 'no-access');
} catch (Exception $e) {
    die("Failed to create route(s) from RootController section: " . $e->getMessage());
}

    /* MessagesController routes */
    try {
        $router->map('GET', '/messages', function() {
            MessagesController::index(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'messages-index');
        $router->map('GET', '/messages/log', function() {
            MessagesController::logIndex(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'messages-log');
        $router->map('GET', '/messages/get-message-log', function() {
            MessagesController::listIndividualMessagesDT(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'messages-getlogs');
        $router->map('GET', '/messages/list', function() {
            MessagesController::listDT(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'messages-list');
        $router->map('POST', '/messages/send', function() {
            MessagesController::sendMessage(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'messages-send');
        $router->map('GET', '/messages/export', function() {
            MessagesController::exportAsCSV(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'messages-export');
    } catch (Exception $e) {
        die("Failed to create route(s) from MessageController section: " . $e->getMessage());
    }

    /* ParticipantsController routes */
    try {
        $router->map('GET', '/participants', function() {
            ParticipantsController::index(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-index');
        $router->map('GET', '/participants/state-log', function() {
            ParticipantsController::stateIndex(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-statelog');
        $router->map('GET', '/participants/all', function() {
            ParticipantsController::getAllParticipants(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-all');
        $router->map('GET', '/participants/get-state-log', function() {
            ParticipantsController::listStateDT(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-liststatedt');
        $router->map('GET', '/participants/get-current-state', function() {
            ParticipantsController::getCurrentState(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-getcurrstate');
        $router->map('GET', '/participants/fill-group-dropdown', function() {
            ParticipantsController::fillGroupDropdown(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-fillgroupdropdown');
        $router->map('GET', '/participants/fill-location-dropdown', function() {
            ParticipantsController::fillLocationDropdown(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-filllocationdropdown');
        $router->map('GET', '/participants/fill-timezone-dropdown', function() {
            ParticipantsController::fillTimeZoneDropdown(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-filltimezonedropdown');
        $router->map('POST', '/participants/add-participant', function() {
            ParticipantsController::addParticipant(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-addparticipant');
        $router->map('GET', '/participants/list', function() {
            ParticipantsController::listDT(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-list');
        $router->map('GET', '/participants/get-participant', function() {
            ParticipantsController::getParticipant(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-getparticipant');
        $router->map('POST', '/participants/update-participant', function() {
            ParticipantsController::updateParticipant(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-updateparticipant');
        $router->map('POST', '/participants/delete-participant', function() {
            ParticipantsController::deleteParticipant(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-deleteparticipant');
        $router->map('GET', '/participants/get-time-zone', function() {
            ParticipantsController::getTimeZone(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-gettimezone');
        $router->map('POST', '/participants/get-state-machine', function() {
            ParticipantsController::getStateMachine(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-getstatemachine');
        $router->map('GET', $rootURL.'/participants/home-stats', function() {
            ParticipantsController::getHomeStats(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'participants-gethomestats');
    } catch (Exception $e) {
        die("Failed to create route(s) from ParticipantsController section: " . $e->getMessage());
    }

    /* ProtocolTypesController routes */
    try {
        $router->map('GET', '/protocol-types', function() {
            ProtocolTypesController::index(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'protocoltypes-index');
        $router->map('POST', '/protocol-types/create', function() {
            ProtocolTypesController::createProtocol(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'protocoltypes-create');
        $router->map('GET', '/protocol-types/list', function() {
            ProtocolTypesController::listDT(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'protocoltypes-list');
        $router->map('GET', '/protocol-types/all', function() {
            ProtocolTypesController::getAll(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'protocoltypes-all');
        $router->map('POST', '/protocol-types/get-name', function() {
            ProtocolTypesController::getNameFromID(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'protocoltypes-getname');
        $router->map('POST', '/protocol-types/update', function() {
            ProtocolTypesController::updateProtocol(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'protocoltypes-update');
        $router->map('POST', '/protocol-types/delete', function() {
            ProtocolTypesController::deleteProtocol(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'protocoltypes-delete');
    } catch (Exception $e) {
        die("Failed to create route(s) from ParticipantTypesController section: " . $e->getMessage());
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
            SurveyController::view(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'survey-view');
        $router->map('GET', '/survey/list', function() {
            SurveyController::listDT(get_session(["User", "Study Admin", "Super Admin"]));
        }, 'survey-dt');
    } catch (Exception $e) {
        die("Failed to create route(s) from UsersController section: " . $e->getMessage());
    }

    try {
        /* UsersController routes */
        $router->map('GET', $rootURL.'/users', function() {
            UsersController::index(get_session(["Study Admin", "Super Admin"]));
        }, 'users-index');
        $router->map('GET', $rootURL.'/users/list', function() {
            UsersController::listForDatatable(get_session(["Study Admin", "Super Admin"]));
        }, 'users-for-datatable');
        $router->map('POST', $rootURL.'/users/submit', function() {
            UsersController::submit(get_session(["Study Admin", "Super Admin"]));
        }, 'users-submit');
        $router->map('GET', $rootURL.'/users/get-roles', function() {
            UsersController::getRoles(get_session(["Study Admin", "Super Admin"]));
        }, 'users-get-roles');
        $router->map('POST', $rootURL.'/users/delete', function() {
            UsersController::deleteUser(get_session(["Study Admin", "Super Admin"]));
        }, 'users-delete');

    } catch (Exception $e) {
        die("Failed to create route(s) from UsersController section: " . $e->getMessage());
    }

try {
    /* MetricsController routes */
    $router->map('GET', $rootURL.'/metrics/compliance', function() {
        MetricsController::getCompliance(get_session(["User", "Study Admin", "Super Admin"]));
    }, 'metrics-compliance');
} catch (Exception $e) {
    die("Failed to create route(s) from MetricsController section: " . $e->getMessage());
}

    $match = $router->match();

    // Call closure or throw 404 status
    if ($match && is_callable($match['target'])) {
        call_user_func_array($match['target'], $match['params']);
    } else {
        // No route was matched
        $from = $_SERVER['REQUEST_URI'];
        error_log("ROUTE NOT FOUND: " . $from);

        if (isJsonRequest()){
            http_response_code(404);
            header('Content-Type: application/json');
            $response = [
                'success' => false,
                'error' => 'Not Found',
                'error_message' => 'The requested resource was not found on this server.'
            ];
            echo json_encode($response);
        } else {
            if (strlen($from) > 20)
                $from = substr($from, 0, 20) . '...';
            $_SESSION['FLASH_ERROR'] = "No page exists at $from";
            if(isset($_SERVER['HTTP_REFERER'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
            } else {
                header('Location: ' . $rootURL . '/');
            }
        }
    }