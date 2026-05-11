<?php

namespace Acms\Plugins\GoogleCalendar;

use Google\Client;
use Google\Service\Calendar;
use Google\Exception as GoogleException;
use Acms\Services\Facades\Storage;
use Acms\Services\Facades\Database as DB;
use Acms\Services\Facades\Config;
use Acms\Services\Facades\Cache;
use Acms\Services\Facades\Logger;
use Acms\Services\Facades\Common;
use SQL;

class Api
{
    /**
     * @var \Field
     */
    public $config;

    /**
     * @var \Google_Client
     */
    public $client;

    /**
     * Api constructor.
     */
    public function __construct()
    {
        // 書き込み権限を指定
        $scopes = implode(' ', array(Calendar::CALENDAR_EVENTS));

        $this->client = new Client();

        $this->config = Config::loadDefaultField();
        $this->config->overload(Config::loadBlogConfig(BID));

        $idJsonPath = $this->config->get('calendar_clientid_json');
        $this->client->setApplicationName('ACMS');
        $this->client->setScopes($scopes);
        $this->setAuthConfig($idJsonPath);
        $this->client->setAccessType('offline');
        $this->client->setPrompt("consent");
        $redirect_uri = acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_calendar_callback',
        ));
        $this->client->setRedirectUri($redirect_uri);

        $accessTokenJson = $this->config->get('google_calendar_accesstoken');
        $accessToken = $accessTokenJson ? json_decode($accessTokenJson, true) : null;
        if ($accessTokenJson && json_last_error() !== JSON_ERROR_NONE) {
            $accessToken = null;
        }
        $refreshTokenJson = $this->config->get('google_calendar_refreshtoken');
        $refreshToken = $refreshTokenJson ? json_decode($refreshTokenJson, true) : null;
        if ($refreshTokenJson && json_last_error() !== JSON_ERROR_NONE) {
            $refreshToken = null;
        }
        if ($accessToken) {
            $this->client->setAccessToken($accessToken);
            if ($this->client->isAccessTokenExpired()) {
                try {
                    $this->client->refreshToken($refreshToken);
                    $accessToken = $this->client->getAccessToken();
                    $refreshToken = $this->client->getRefreshToken();
                    $this->updateAccessToken(json_encode($accessToken), json_encode($refreshToken));
                } catch (\Exception $e) {
                    Logger::error(
                        '【Google Calendar】アクセストークンの更新に失敗しました。',
                        Common::exceptionArray($e)
                    );
                }
            }
        }
    }

    /**
     * @param string $json
     * @throws Google_Exception
     */
    public function setAuthConfig($json)
    {
        if (!Storage::isFile($json)) {
            throw new \RuntimeException('Failed to open ' . $json);
        }
        $json = file_get_contents($json);
        $data = json_decode($json);
        $key = isset($data->installed) ? 'installed' : 'web';
        if (!isset($data->$key)) {
            throw new GoogleException("Invalid client secret JSON file.");
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
     * @return array|null
     */
    public function getAccessToken(): ?array
    {
        $accessTokenJson = $this->config->get('google_calendar_accesstoken');
        $accessToken = $accessTokenJson ? json_decode($accessTokenJson, true) : null;
        if ($accessTokenJson && json_last_error() !== JSON_ERROR_NONE) {
            $accessToken = null;
        }
        return $accessToken;
    }

    /**
     * @param string|null $accessToken
     * @param string|null $refreshToken
     * @return void
     */
    public function updateAccessToken(?string $accessToken, ?string $refreshToken): void
    {
        if (!!$accessToken) {
            $DB = DB::singleton(dsn());
            $RemoveSQL = SQL::newDelete('config');
            $RemoveSQL->addWhereOpr('config_blog_id', BID);
            $RemoveSQL->addWhereOpr('config_key', 'google_calendar_accesstoken');
            $DB->query($RemoveSQL->get(dsn()), 'exec');

            $InsertSQL = SQL::newInsert('config');
            $InsertSQL->addInsert('config_key', 'google_calendar_accesstoken');
            $InsertSQL->addInsert('config_value', $accessToken);
            $InsertSQL->addInsert('config_blog_id', BID);
            $DB->query($InsertSQL->get(dsn()), 'exec');
        }
        if (!!$refreshToken) {
            $DB = DB::singleton(dsn());
            $RemoveSQL = SQL::newDelete('config');
            $RemoveSQL->addWhereOpr('config_blog_id', BID);
            $RemoveSQL->addWhereOpr('config_key', 'google_calendar_refreshtoken');
            $DB->query($RemoveSQL->get(dsn()), 'exec');

            $InsertSQL = SQL::newInsert('config');
            $InsertSQL->addInsert('config_key', 'google_calendar_refreshtoken');
            $InsertSQL->addInsert('config_value', $refreshToken);
            $InsertSQL->addInsert('config_blog_id', BID);
            $DB->query($InsertSQL->get(dsn()), 'exec');
        }
        if (class_exists('Cache')) {
            Cache::flush('config');
        }
    }
}
