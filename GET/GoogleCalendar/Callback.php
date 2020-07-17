<?php

namespace Acms\Plugins\GoogleCalendar\GET\GoogleCalendar;

use ACMS_GET;
use Template;
use ACMS_Corrector;
use Acms\Plugins\GoogleCalendar\Api;

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
            $client->authenticate($code);
            $accessToken = $client->getAccessToken();
            $api->updateAccessToken($accessToken);

            redirect($base_uri);
        } catch (\Exception $e) {}

        return '';
    }
}
