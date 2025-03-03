<?php

use CILogon\OAuth2\Client\Provider\CILogon;

require 'vendor/autoload.php';


class CiLogonProvider
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $provider;
    private $accessToken;
    private $state;

    public function __construct()
    {
        // Initialize the OAuth2 provider with CILogon configuration
        $config = include CONFIG_FILE;
        $this->clientId = $config['oauth2']['clientId'];
        $this->clientSecret = $config['oauth2']['clientSecret'];
        $this->redirectUri = $config['oauth2']['redirectUri'];
        $this->provider = new CILogon([
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'redirectUri'  => $this->redirectUri,
            'urlAuthorize'            => 'https://cilogon.org/authorize',
            'urlAccessToken'          => 'https://cilogon.org/oauth2/token ',
            'urlResourceOwnerDetails' => 'https://cilogon.org/oauth2/userinfo '
        ]);
    }

    public static function getProvider()
    {
        return new self();
    }

    public function getAuthorizationUrl()
    {
        // Generate and store state
        $this->state = bin2hex(random_bytes(16)); // Generate a random state
        $_SESSION['OAuth2.state'] = $this->state;

        // Set the required scopes (e.g., 'openid email')
        $config = require CONFIG_FILE;
        $scopes = $config['oauth2']['scopes'];
        $idphint = $config['oauth2']['idphint'] ?? null;
        $initialidp = $config['oauth2']['initialidp'] ?? null;

        // Set the URL Parameters
        $urlParams = [
            'state' => $this->state,
            'scope' => implode(' ', $scopes),
        ];
        if ($idphint){
            $urlParams['idphint'] = implode(',', $idphint);
        }
        if ($initialidp){
            $urlParams['initialidp'] = $initialidp;
        }
        
        $authorizationUrl = $this->provider->getAuthorizationUrl($urlParams);

        return $authorizationUrl;
    }

    public function getAccessToken($authorizationCode)
    {
        $this->accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $authorizationCode
        ]);
        return $this->accessToken;
    }

    public function getUserInfo()
    {
        if (!$this->accessToken) {
            throw new Exception('Access token is not set.');
        }

        try {
            $resourceOwner = $this->provider->getResourceOwner($this->accessToken);
            $info = array();

            $parsedUrl = parse_url($resourceOwner->getId());
            $path = $parsedUrl['path'];
            $numbers = basename($path);
            $info['id'] = $numbers;
            $info['email'] = $resourceOwner->getEmail(); // changed email like sam.armstrong@uky.edu
            $info['firstName'] = $resourceOwner->getFirstName();
            $info['lastName'] = $resourceOwner->getLastName();
            $info['email'] = $resourceOwner->getEmail();
            $info['name'] = $resourceOwner->getName();
            $info['eppn'] = $resourceOwner->getEppn(); // org email like sear234@uky.edu
            $info['idp'] = $resourceOwner->getIdp();
            $info['idpName'] = $resourceOwner->getIdpName();
            $info['affiliation'] = $resourceOwner->getAffiliation();
            return $info;
        } catch (Exception $e) {
            throw new Exception('Failed to get resource owner: ' . $e->getMessage());
        }
    }

    public function validateState($state)
    {
        return isset($_SESSION['OAuth2.state']) && $state === $_SESSION['OAuth2.state'];
    }

    public function getLogoutUrl() {
        $params = [
            'client_id' => $this->clientId,
            'return' => 'https://caai.ai.uky.edu/'
        ];
        $logoutUrl = 'https://cilogon.org/logout?' . http_build_query($params);
        return $logoutUrl;
    }
}