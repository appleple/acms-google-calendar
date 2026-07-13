<?php

namespace Acms\Plugins\GoogleCalendar\GET\GoogleCalendar;

use ACMS_GET;
use Acms\Plugins\GoogleCalendar\Api;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;

class Callback extends ACMS_GET
{
    public function get()
    {
        try {
            $api = new Api();
            $client = $api->getClient();
            $base_uri = acmsLink(array(
                'bid' => BID,
                'admin' => 'app_google_calendar_index',
            ));
            $code = $this->Get->get('code');
            $client->fetchAccessTokenWithAuthCode($code);
            $accessToken = $client->getAccessToken();
            $refreshToken = $client->getRefreshToken();
            $api->updateAccessToken(json_encode($accessToken), json_encode($refreshToken));

            redirect($base_uri);
        } catch (\Exception $e) {
            Logger::error(
                '【Google Calendar】API の認証コールバック処理に失敗しました。',
                Common::exceptionArray($e)
            );
        }

        return '';
    }
}
