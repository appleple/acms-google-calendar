<?php
namespace Acms\Plugins\GoogleCalendar;

use ACMS_App;
use Acms\Services\Common\HookFactory;
use Acms\Services\Common\InjectTemplate;

class ServiceProvider extends ACMS_App
{
    /**
     * @var string
     */
    public $version = '3.0.1';

    /**
     * @var string
     */
    public $name = 'Google Calendar';

    /**
     * @var string
     */
    public $author = 'com.appleple';

    /**
     * @var bool
     */
    public $module = false;

    /**
     * @var string
     */
    public $menu = 'google_calendar_index';

    /**
     * @var string
     */
    public $desc = 'フォームの内容を Google Calendarに登録するためのアプリです。';

    /**
     * サービスの起動処理
     */
    public function init()
    {
        // GoogleCalendarAPI使用準備
        require_once dirname(__FILE__).'/vendor/autoload.php';

        $hook = HookFactory::singleton();
        $hook->attach('GoogleCalendar', new Hook);
        $inject = InjectTemplate::singleton();

        if (ADMIN === 'app_google_calendar_index') {
            $inject->add('admin-topicpath', PLUGIN_DIR . 'GoogleCalendar/template/admin/topicpath.html');
            $inject->add('admin-main', PLUGIN_DIR . 'GoogleCalendar/template/admin/main.html');
        } elseif (ADMIN === 'app_google_calendar_callback') {
            $inject->add('admin-topicpath', PLUGIN_DIR . 'GoogleCalendar/template/admin/topicpath.html');
            $inject->add('admin-main', PLUGIN_DIR . 'GoogleCalendar/template/admin/callback.html');
        }
        $inject->add('admin-form', PLUGIN_DIR . 'GoogleCalendar/template/admin/form.html');
    }
    /**
     * インストールする前の環境チェック処理
     *
     * @return bool
     */
    public function checkRequirements()
    {
        return true;
    }

    /**
     * インストールするときの処理
     * データベーステーブルの初期化など
     *
     * @return void
     */
    public function install()
    {

    }

    /**
     * アンインストールするときの処理
     * データベーステーブルの始末など
     *
     * @return void
     */
    public function uninstall()
    {
    }

    /**
     * アップデートするときの処理
     *
     * @return bool
     */
    public function update()
    {
        return true;
    }

    /**
     * 有効化するときの処理
     *
     * @return bool
     */
    public function activate()
    {
        return true;
    }

    /**
     * 無効化するときの処理
     *
     * @return bool
     */
    public function deactivate()
    {
        return true;
    }
}
