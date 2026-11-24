<?php

namespace Acms\Plugins\GoogleCalendar\POST\GoogleCalendar;

use ACMS_POST;
use Acms\Services\Facades\Logger;
use Acms\Plugins\GoogleCalendar\Api;

class Auth extends ACMS_POST
{
    public function post()
    {
        if (!sessionWithAdministration()) {
            Logger::notice('【Google Calendar plugin】管理者以外からの要求のため、処理を中断しました。', ['module' => static::class]);
            http_response_code(403);
            die();
        }

        $api = new Api();
        $client = $api->getClient();
        $this->redirect($client->createAuthUrl());
    }
}
