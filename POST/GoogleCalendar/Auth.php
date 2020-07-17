<?php
namespace Acms\Plugins\GoogleCalendar\POST\GoogleCalendar;

use ACMS_POST;
use Acms\Plugins\GoogleCalendar\Api;

class Auth extends ACMS_POST
{
    public function post()
    {
        $api = new Api();
        $client = $api->getClient();
        $this->redirect($client->createAuthUrl());
    }
}
