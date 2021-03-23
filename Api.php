<?php

namespace Acms\Plugins\GoogleCalendar;

use Acms\Services\Facades\Storage;
use DB;
use SQL;
use Config;
use Google_Client;
use Google_Service_Calendar;
use Google_Exception;

class Api
{
    /**
     * Api constructor.
     */
    public function __construct()
    {
        // 書き込み権限を指定
        $scopes = implode(' ', array(Google_Service_Calendar::CALENDAR_EVENTS));

        $client = new Google_Client();

        $this->config = Config::loadDefaultField();
        $this->config->overload(Config::loadBlogConfig(BID));

        $idJsonPath = $this->config->get('calendar_clientid_json');
        $client->setApplicationName('ACMS');
        $client->setScopes($scopes);
        $this->client = $client;
        $this->setAuthConfig($idJsonPath);
        $client->setAccessType('offline');
        $client->setApprovalPrompt("force");
        $redirect_uri = acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_calendar_callback',
        ));
        $client->setRedirectUri($redirect_uri);
        $accessToken = json_decode($this->config->get('google_calendar_accesstoken'), true);
        if ($accessToken) {
            $client->setAccessToken($accessToken);
            if ($client->isAccessTokenExpired()) {
                $refreshToken = $client->getRefreshToken();
                $client->refreshToken($refreshToken);
                $accessToken = $client->getAccessToken();
                $this->updateAccessToken($accessToken);
            }
        }
    }

    /**
     * @param string $json
     * @throws Google_Exception
     */
    public function setAuthConfig($json)
    {
        if (!Storage::exists($json)) {
            throw new \RuntimeException('Failed to open ' . $json);
        }
        $json = file_get_contents($json);
        $data = json_decode($json);
        $key = isset($data->installed) ? 'installed' : 'web';
        if (!isset($data->$key)) {
            throw new Google_Exception("Invalid client secret JSON file.");
        }
        $this->client->setClientId($data->$key->client_id);
        $this->client->setClientSecret($data->$key->client_secret);
        if (isset($data->$key->redirect_uris)) {
            $this->client->setRedirectUri($data->$key->redirect_uris[0]);
        }
    }

    /**
     * @return Google_Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        $accessToken = json_decode($this->config->get('google_calendar_accesstoken'), true);
        return $accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function updateAccessToken($accessToken)
    {
        $DB = DB::singleton(dsn());
        $RemoveSQL = SQL::newDelete('config');
        $RemoveSQL->addWhereOpr('config_blog_id', BID);
        $RemoveSQL->addWhereOpr('config_key', 'google_calendar_accesstoken');
        $DB->query($RemoveSQL->get(dsn()), 'exec');

        $InsertSQL = SQL::newInsert('config');
        $InsertSQL->addInsert('config_key', 'google_calendar_accesstoken');
        $InsertSQL->addInsert('config_value', json_encode($accessToken));
        $InsertSQL->addInsert('config_blog_id', BID);
        $DB->query($InsertSQL->get(dsn()), 'exec');
    }
}
