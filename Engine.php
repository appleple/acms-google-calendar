<?php

namespace Acms\Plugins\GoogleCalendar;

use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Field;
use Common;
use Exception;

class Engine
{
    /**
     * @var Field
     */
    protected $config;

    /**
     * @var \ACMS_POST
     */
    protected $module;

    /**
     * Engine constructor.
     * @param string $code
     * @param \ACMS_POST $module
     */
    public function __construct($code, $module)
    {
        $info = $module->loadForm($code);
        if (empty($info)) {
            throw new \RuntimeException('Not Found Form.');
        }
        $this->config = $info['data']->getChild('mail');
        $this->module = $module;
    }

    /**
     * Update Google Calendar
     */
    public function send()
    {
        $field = $this->module->Post->getChild('field');

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
        $service = new Calendar($client);
        $calendarId = $this->config->get('calendar_id');

        if (!empty($calendarId)) {
            try {
                $event = new Event($values);
                $service->events->insert($calendarId, $event);
            } catch (Exception $e) {
                throw new \RuntimeException('Failed to update the calendar.');
            }
        }
    }

    /**
     * @param Field
     * @return array
     */
    protected function makeCalendarValues($field)
    {
        $checkItems = array(
            'calendar_start_date' => $this->config->get('calendar_start_date_check'),
            'calendar_start_time' => $this->config->get('calendar_start_time_check'),
            'calendar_end_date' => $this->config->get('calendar_end_date_check'),
            'calendar_end_time' => $this->config->get('calendar_end_time_check'),
            'calendar_event_timeZone' => $this->config->get('calendar_event_timeZone_check'),
        );

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

        $values = array(
            // event title
            'summary' => Common::getMailTxtFromTxt($formItems["calendar_event_title"], $field),

            // event location
            'location' => Common::getMailTxtFromTxt($formItems["calendar_event_location"], $field),

            // event description
            'description' => Common::getMailTxtFromTxt($formItems["calendar_event_description"], $field),
        );

        // event date_time
        $values = $this->makeDateValue($values, array(
            'startDateValue' => $checkItems["calendar_start_date"] ? $field->get($formItems["calendar_start_date"]) : $formItems["calendar_start_date"],
            'startTimeValue' => $checkItems["calendar_start_time"] ? $field->get($formItems["calendar_start_time"]) : $formItems["calendar_start_time"],
            'endDateValue' => $checkItems["calendar_end_date"] ? $field->get($formItems["calendar_end_date"]) : $formItems["calendar_end_date"],
            'endTimeValue' => $checkItems["calendar_end_time"] ? $field->get($formItems["calendar_end_time"]) : $formItems["calendar_end_time"],
            'timeZoneValue' => $formItems["calendar_event_timeZone"],
        ));

        // event attendees
        $values = $this->makeAttendeesValue($values, Common::getMailTxtFromTxt($formItems["calendar_event_attendees"], $field));
        return $values;
    }

    /**
     * @param array $value
     * @param string $attendeesStr
     *
     * @return array
     */
    private function makeAttendeesValue($value, $attendeesStr)
    {
        // delete space character
        $attendeesStr = str_replace(array(" ", "　"), "", $attendeesStr);

        $attendees = explode(',', $attendeesStr);
        $attendeesArray = array();

        // regular expression of email
        $reg_str = "/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/";

        foreach ($attendees as $attendee) {
            if (preg_match($reg_str, $attendee)) {
                array_push($attendeesArray, array('email' => $attendee));
            }
        }

        if ($attendeesArray !== []) {
            $value += array("attendees" => $attendeesArray);
        }
        return $value;
    }

    /**
     * @param array $value
     * @param array $dateMixArray
     *
     * @return array
     */
    private function makeDateValue($value, $dateMixArray)
    {
        if ($dateMixArray["startTimeValue"] == "" && $dateMixArray["endTimeValue"] == "") {
            $startDate = $dateMixArray["startDateValue"];
            if ($dateMixArray["endDateValue"] == "") {
                $endDate = $startDate;
            } else if (substr($dateMixArray["endDateValue"], 0, 1) == "+") {
                $endDate = str_replace(array("+"), "", $dateMixArray["endDateValue"]);
                $addedDate = $this->addDateTime($startDate . " 0:0:0", $endDate . " 0:0:0");
                $addedDates = explode(" ", $addedDate);
                $endDate = $addedDates[0];
            } else {
                $endDate = $dateMixArray["endDateValue"];
            }
            $value += array("start" => array(
                "date" => $startDate,
                "timeZone" => $dateMixArray["timeZoneValue"],
            ));

            $value += array("end" => array(
                "date" => $endDate,
                "timeZone" => $dateMixArray["timeZoneValue"],
            ));
        } else {
            $startDate = $dateMixArray["startDateValue"];
            $startTime = $dateMixArray["startTimeValue"];

            if ($dateMixArray["endDateValue"] == "") {
                $endDate = $startDate;
            } else if (substr($dateMixArray["endDateValue"], 0, 1) == "+") {
                $endDate = str_replace(array("+"), "", $dateMixArray["endDateValue"]);
                $addedDate = $this->addDateTime($startDate . " " . $startTime, $endDate . " 0:0:0");
                $addedDates = explode(" ", $addedDate);
                $endDate = $addedDates[0];
            } else {
                $endDate = $dateMixArray["endDateValue"];
            }

            if ($dateMixArray["endTimeValue"] == "") {
                $endTime = $startTime;
            } else if (substr($dateMixArray["endTimeValue"], 0, 1) == "+") {
                $endTime = str_replace(array("+"), "", $dateMixArray["endTimeValue"]);
                $addedDate = $this->addDateTime($endDate . " " . $startTime, "0-0-0 " . $endTime);
                $addedDates = explode(" ", $addedDate);
                $endDate = $addedDates[0];
                $endTime = $addedDates[1];
            } else {
                $endTime = $dateMixArray["endTimeValue"];
            }

            $value += array("start" => array(
                "dateTime" => $startDate . "T" . $startTime,
                "timeZone" => $dateMixArray["timeZoneValue"],
            ));

            $value += array("end" => array(
                "dateTime" => $endDate . "T" . $endTime,
                "timeZone" => $dateMixArray["timeZoneValue"],
            ));
        }
        return $value;
    }

    /**
     * @param $date
     * @param $dateTime
     * @return string
     */
    private function addDateTime($date, $dateTime)
    {
        $dateSplit = str_replace([" ", ":"], "-", $date);
        $dateSplit = explode("-", $dateSplit);
        $dateTimeSplit = str_replace([" ", ":"], "-", $dateTime);
        $dateTimeSplit = explode("-", $dateTimeSplit);
        $hour = $dateSplit[3] + $dateTimeSplit[3];
        $min = $dateSplit[4] + $dateTimeSplit[4];
        $sec = $dateSplit[5] + $dateTimeSplit[5];
        $month = $dateSplit[1] + $dateTimeSplit[1];
        $day = $dateSplit[2] + $dateTimeSplit[2];
        $year = $dateSplit[0] + $dateTimeSplit[0];
        return date("Y-m-d H:i:s", mktime($hour, $min, $sec, $month, $day, $year));
    }
}
