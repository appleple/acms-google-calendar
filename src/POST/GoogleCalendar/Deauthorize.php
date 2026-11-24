<?php

namespace Acms\Plugins\GoogleCalendar\POST\GoogleCalendar;

use ACMS_POST;
use Acms\Services\Facades\Logger;
use DB;
use SQL;
use Cache;
use Acms\Plugins\GoogleCalendar\Api;

class Deauthorize extends ACMS_POST
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
        $isRevoked = $client->revokeToken();

        if ($isRevoked === false) {
            return $this->Post;
        }

        $this->deleteAccessToken();
        $this->redirect(acmsLink(array(
            'bid' => BID,
            'admin' => 'app_google_calendar_index',
        )));
    }

    /**
     * コンフィグに保存されているアクセストークンを削除する
     */
    protected function deleteAccessToken()
    {
        $DB = DB::singleton(dsn());
        $RemoveSQL = SQL::newDelete('config');
        $RemoveSQL->addWhereOpr('config_blog_id', BID);
        $RemoveSQL->addWhereIn('config_key', ['google_calendar_accesstoken', 'google_calendar_refreshtoken']);
        $DB->query($RemoveSQL->get(dsn()), 'exec');

        if (class_exists('Cache')) {
            Cache::flush('config');
        }
    }
}
