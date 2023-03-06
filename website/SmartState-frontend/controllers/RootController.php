<?php

include_once MODELS_DIR . 'User.php';
include_once MODELS_DIR . 'UserSession.php';

class RootController {
    public static function index(UserSession $userSession) {
        require MESSAGES_VIEWS_DIR . 'index.php';
    }

    public static function login() {
        if (!is_null(UserSession::withSessionID(session_id())))
            header('Location: /');
        require VIEWS_DIR . 'login.php';
    }

    public static function do_login($linkblue, $password, $remember_me) {
        if (!is_null(UserSession::withSessionID(session_id())))
            header('/');
        try {
            $config = include CONFIG_FILE;
            if (self::check_ldap_password($linkblue, $password)){ // this returns false if user with linkblue is null
                $user = User::withLinkblue($linkblue);
                if (!is_null($user)){
                    UserSession::create(session_id(), $user, $remember_me);
                    self::post_login_redirect();
                } else {
                    throw new Exception("Invalid username/password");
                }
            }
            else if (self::check_non_ldap_password($linkblue, $password)){
                $user = User::withLinkblue($linkblue);
                if (!is_null($user)){
                    UserSession::create(session_id(), $user, $remember_me);
                    self::post_login_redirect();
                } else {
                    throw new Exception("Invalid username/password"); 
                }
            } else {
                throw new Exception("Invalid username/password");
            }
        } catch (Exception $e) {
            throw new Exception("Error authenticating, please try again: " . $e->getMessage());
        }
    }

    public static function logout() {
        UserSession::delete(session_id());
        session_destroy();
        session_start();
        session_regenerate_id();
        header('Location: /login');
    }

    private static function check_password_fake($linkblue, $password): bool {
        return !empty($linkblue) && !empty($password);
    }

    private static function check_ldap_password($linkblue, $password): bool {
        try {
            $config = include CONFIG_FILE;
            $ldapconn = ldap_connect($config['ldap']['host']);
            ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
            if ($ldapconn) {
                set_error_handler(function (int $errno, string $errstr) {
                    // don't throw exception, check if non-linkblue user
                });
                $ldapbind = ldap_bind(
                    $ldapconn,
                    "{$config['ldap']['prefix']}{$linkblue}{$config['ldap']['suffix']}",
                    $password
                );
                restore_error_handler();
                if ($ldapbind) {
                    if (sizeof(User::all()) == 0){
                        User::create($linkblue, 0, "", ""); // if first time logging in create admin user
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new Exception("Failed to initialize connection object with host " .
                    "[{$config['ldap']['host']}], prefix [{$config['ldap']['prefix']}]," .
                    ", and suffix [{$config['ldap']['suffix']}]");
            }
        } catch (Exception $e) {}
    }

    private static function check_non_ldap_password($linkblue, $password): bool {
        $user = User::withLinkblue($linkblue);
        if (is_null($user)){
            if (sizeof(User::all()) == 0){
                User::create($linkblue, 0, $password, ""); // if first login, then create an admin account
                return true;
            } else {
                return false;
            }
        }
        if(password_verify($password, $user->getHash())) {
            return true;
        } else {
            return false;
        }
    }

    private static function post_login_redirect() {
        if (!isset($_SESSION['redirect']) || is_null($_SESSION['redirect']))
            header('Location: /messages');
        else {
            $redirect = $_SESSION['redirect'];
            $_SESSION['redirect'] = null;
            header('Location: ' . $redirect);
        }
    }
}