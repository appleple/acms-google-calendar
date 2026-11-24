<?php

namespace Acms\Plugins\GoogleCalendar\GET\GoogleCalendar;

use ACMS_GET;
use Acms\Services\Facades\Session;
use Acms\Plugins\GoogleCalendar\Api;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;

class Callback extends ACMS_GET
{
    public function get()
    {
        if (!sessionWithAdministration()) {
            Logger::notice('【Google Calendar plugin】管理者以外からの要求のため、処理を中断しました。', ['module' => static::class]);
            http_response_code(403);
            die();
        }

        // 使い切りにするため、検証の成否にかかわらず先に破棄する
        $session = Session::handle();
        $expectedState = $session->get('google_calendar_oauth_state');
        $session->delete('google_calendar_oauth_state');
        $session->save();
        $state = $this->Get->get('state');
        if (
            !is_string($expectedState)
            || $expectedState === ''
            || !hash_equals($expectedState, $state)
        ) {
            Logger::notice('【Google Calendar plugin】OAuth の state が一致しないため、処理を中断しました。', ['module' => static::class]);
            http_response_code(403);
            die();
        }

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
