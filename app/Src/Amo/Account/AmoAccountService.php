<?php

namespace App\Src\Amo\Account;

use AmoCRM\AmoAPI;
use App\Src\Amo\EloquentStorage;
use App\Src\Amo\Oauth2Credential;

class AmoAccountService
{
    private EloquentStorage $tokenStorage;
    private string $domain;
    public function __construct()
    {
        $this->tokenStorage = new EloquentStorage();
        AmoAPI::$tokenStorage =  $this->tokenStorage;
    }

    public function getDomain() : string
    {
        return $this->domain;
    }

    public function oauthByDomain(string $domain)
    {
        $this->domain = $domain;
        AmoAPI::oAuth2($domain);
    }

    public function getAccessToken()
    {
       return $this->tokenStorage->load("$this->domain.amocrm.ru")['access_token'];
    }


    public function oauth2(Oauth2Credential $credential)
    {
        AmoAPI::oAuth2($credential->domain,$credential->clientId,$credential->secret,$credential->redirectUri,$credential->authCode);
    }

    public function getAccount()
    {
        return AmoAPI::getAccount();
    }

    public function addLeadWebhook()
    {
        $url = env('WEBHOOK_URL');
        AmoAPI::addWebhooks([
            'url'    => $url,
            'events' => [ 'add_lead' ]
        ]);
    }

    public function addContactWebhook(string $url)
    {
        AmoAPI::addWebhooks([
            'url'    => $url,
            'events' => [ 'add_contact' ]
        ]);
    }
}
