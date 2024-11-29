<?php

namespace App\Src\Amo;

class Oauth2Credential
{
    public function __construct(
        public string $domain,
        public string $clientId,
        public string $redirectUri,
        public string $secret,
        public string $authCode
    )
    {
    }
}
