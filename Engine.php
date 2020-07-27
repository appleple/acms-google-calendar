<?php

namespace Acms\Plugins\GoogleCalendar;

use DB;
use SQL;
use Field;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Common;

class Engine
{
    /**
     * @var \Field
     */
    protected $formField;

    /**
     * @var \Field
     */
    protected $config;

    /**
     * Engine constructor.
     * @param string $code
     */
    public function __construct($code, $module)
    {
        $field = $this->loadFrom($code);
        if (empty($field)) {
            throw new \RuntimeException('Not Found Form.');
        }
        $this->formField = $field;
        $this->module = $module;
        $this->code = $code;
        $this->config = $field->getChild('mail');
    }

    /**
     * Update Google Calendar
     */
    public function send()
    {
        $field = $this->module->Post->getChild('field');

        // GoogleCalendarAPIに渡す情報を生成
        $values = $this->makeCalendarValues($field);

        $this->update($values);
    }

    /**
     * Send Google Calendar Api
     *
     * @param array $values
     */
    protected function update($values)
    {
        $client = (new Api())->getClient();
        if (!$client->getAccessToken()) {
            throw new \RuntimeException('Failed to get the access token.');
        }
        $service = new Google_Service_Calendar($client);
        $calendarId = $this->config->get('calendar_id');

        $event = new Google_Service_Calendar_Event($values);

        $response = $service->events->insert($calendarId, $event);

        if (!$response->valid()) {
            throw new \RuntimeException('Failed to update the calendar.');
        }
    }


    /**
     * @param string $code
     * @return bool|Field
     */
    protected function loadFrom($code)
    {
        $DB = DB::singleton(dsn());
        $SQL = SQL::newSelect('form');
        $SQL->addWhereOpr('form_code', $code);
        $row = $DB->query($SQL->get(dsn()), 'row');

        if (!$row) {
            return false;
        }
        $Form = new Field();
        $Form->set('code', $row['form_code']);
        $Form->set('name', $row['form_name']);
        $Form->set('scope', $row['form_scope']);
        $Form->set('log', $row['form_log']);
        $Form->overload(unserialize($row['form_data']), true);

        return $Form;
    }

    protected function makeCalendarValues($field){
        
        // 設定項目について、チェックボックスの真偽値の配列
        // ablog cms カスタムフィールドを使用：true
        // ablog cms カスタムフィールドを使用しない：false
        // $checkItems:bool[]
        $checkItems = array(
            'calendar_start_date' => $this->config->get('calendar_start_date_check'),
            'calendar_start_time' => $this->config->get('calendar_start_time_check'),
            'calendar_end_date' => $this->config->get('calendar_end_date_check'),
            'calendar_end_time' => $this->config->get('calendar_end_time_check'),
            'calendar_event_timeZone' => $this->config->get('calendar_event_timeZone_check'),
        );

        // 各設定項目について、記述されている値の配列
        // $formField:string[]
        $formItems = array(
            'calendar_event_title' => $this->config->get('calendar_event_title'),
            'calendar_event_location' => $this->config->get('calendar_event_location'),
            'calendar_event_description' => $this->config->get('calendar_event_description'),
            'calendar_event_attendees' => $this->config->get('calendar_event_attendees'),
            'calendar_start_date' => $this->config->get('calendar_start_date'),
            'calendar_start_time' => $this->config->get('calendar_start_time'),
            'calendar_end_date' => $this->config->get('calendar_end_date'),
            'calendar_end_time' => $this->config->get('calendar_end_time'),
            'calendar_event_timeZone' => $this->config->get('calendar_event_timeZone'),
        );

        // GoogleCalendarAPIに渡される値
        // $values:string[]
        $values = array(
            // 予定タイトル
            'summary' => Common::getMailTxtFromTxt($formItems["calendar_event_title"], $field),

            // 予定場所
            'location' => Common::getMailTxtFromTxt($formItems["calendar_event_location"], $field),

            // 予定説明
            'description' => Common::getMailTxtFromTxt($formItems["calendar_event_description"], $field),

            // 参加者
            'attendees' => $this->makeAttendeesValue(Common::getMailTxtFromTxt($formItems["calendar_event_attendees"], $field)),

            // リマインダー
            // 現在off
            // 通知設定機能の実装を検討中
            'reminders' => array(
                'useDefault' => FALSE,
            ),
        );
        $values = $this->makeDateValue($values, array(
            'startDateValue' => $checkItems["calendar_start_date"] ? $field->get($formItems["calendar_start_date"]): $formItems["calendar_start_date"],
            'startTimeValue' => $checkItems["calendar_start_time"] ? $field->get($formItems["calendar_start_time"]): $formItems["calendar_start_time"],
            'endDateValue' => $checkItems["calendar_end_date"] ? $field->get($formItems["calendar_end_date"]): $formItems["calendar_end_date"],
            'endTimeValue' => $checkItems["calendar_end_time"] ? $field->get($formItems["calendar_end_time"]): $formItems["calendar_end_time"],
            'timeZoneValue' => $formItems["calendar_event_timeZone"],
        ));
        return $values;
    }

    // GoogleCalendarに送信する attendees の array を作成する
    // example: 'example@hotmail.co.jp, example@gmail.com, example@yahoo.co.jp'
    /*  becomes: array(
        array('email' => 'example@hotmail.co.jp),
        array('email' => 'example@gmail.com'),
        array('email' => 'example@yahoo.co.jp'),
    )*/
    // @return string[]
    private function makeAttendeesValue($str) {
        
        // 空白文字(全角・半角)を削除
        $str = str_replace(array(" ", "　"), "", $str);

        $attendees = explode(',', $str);
        $array = array();
        foreach ($attendees as $attendee) {
            array_push($array,array('email' => $attendee));
        }
        return $array;
    }

    private function makeDateValue($value, $dateMixArray) {
        $startDate = $dateMixArray["startDateValue"];
        $startTime = $dateMixArray["startTimeValue"];

        // ここから終了日時を計算する
        if ($dateMixArray["endDateValue"]=="" || substr($dateMixArray["endDateValue"], 0, 1)=="+") {
            if ($dateMixArray["endDateValue"]=="") {
                $endDate = $startDate;
            } else {
                $endDate = str_replace(array("+"), "", $dateMixArray["endDateValue"]);
                $addedDate = $this->addDateTime($startDate." ".$startTime, $endDate." 0:0:0");
                $endDate = explode(':', $addedDate)[0];
            }
        } else {
            $endDate = $dateMixArray["endDateValue"];
        }

        if ($dateMixArray["endTimeValue"]=="" || substr($dateMixArray["endTimeValue"], 0, 1)=="+") {
            if (substr($dateMixArray["endTimeValue"], 0, 1)=="+") {
                $endTime = str_replace(array("+"), "", $dateMixArray["endTimeValue"]);
                $addedDate = $this->addDateTime($startDate." ".$startTime, "0-0-0 ".$endTime);
                $addedDates = explode(" ", $addedDate);
                $endDate = $addedDates[0];
                $endTime = $addedDates[1];
            } else {
                $addedDate = $this->addDateTime($startDate." ".$startTime, "0-0-0 1:0:0");
                $addedDates = explode(" ", $addedDate);
                $endDate = $addedDates[0];
                $endTime = $addedDates[1];
            }
        } else {
            $endTime = $dateMixArray["endTimeValue"];
        }

        // $value に日付情報を格納
        $value += array("start" => array(
            "dateTime" => $startDate."T".$startTime,
            "timeZone" => $dateMixArray["timeZoneValue"],
        ));

        $value += array("end" => array(
            "dateTime" => $endDate."T".$endTime,
            "timeZone" => $dateMixArray["timeZoneValue"],
        ));
        return $value;
    }

    // y-m-d h:i:s 形式
    private function addDateTime($date, $dateTime) {
        $dateSplit = str_replace([" ", ":"], "-", $date);
        $dateSplit = explode("-", $dateSplit);
        $dateTimeSplit = str_replace([" ", ":"], "-", $dateTime);
        $dateTimeSplit = explode("-", $dateTimeSplit);
        $hour = $dateSplit[3]+$dateTimeSplit[3];
        $min = $dateSplit[4]+$dateTimeSplit[4];
        $sec = $dateSplit[5]+$dateTimeSplit[5];
        $month = $dateSplit[1]+$dateTimeSplit[1];
        $day = $dateSplit[2]+$dateTimeSplit[2];
        $year = $dateSplit[0]+$dateTimeSplit[0];
        return date("Y-m-d H:i:s", mktime($hour, $min, $sec, $month, $day, $year));
    }
}
