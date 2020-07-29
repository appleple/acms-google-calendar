<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__)."/../Engine.php";

class AddDateTimeTest extends TestCase {

    /**
     * create Engin instance without constructor
     */
    public function setUp() {
        $this->engin = new DummyClass("Acms\Plugins\GoogleCalendar\Engine");
    }

    public function testAddHour() {
        $response = $this->engin->addDateTime("2020-7-8 10:00:00", "0-0-0 1:0:0");
        $this->assertEquals("2020-07-08 11:00:00", $response);

        $response = $this->engin->addDateTime("2020-7-8 10:00:00", "00-00-00 01:00:00");
        $this->assertEquals("2020-07-08 11:00:00", $response);
    }

    public function testAddMin() {
        $response = $this->engin->addDateTime("2020-7-8 10:00:00", "00-00-00 01:20:40");
        $this->assertEquals("2020-07-08 11:20:40", $response);
    }

    public function testAddYear() {
        $response = $this->engin->addDateTime("2020-7-8 10:00:00", "10-10-10 01:00:00");
        $this->assertEquals("2031-05-18 11:00:00", $response);
    }

    public function testAddedDateSpreadsAcrossTwodays() {
        $response = $this->engin->addDateTime("2020-7-8 10:00:00", "00-00-00 20:00:00");
        $this->assertEquals("2020-07-09 06:00:00", $response);
    }
}

class MakeAttendeesValueTest extends TestCase {
    public function setUp() {
        $this->engin = new DummyClass("Acms\Plugins\GoogleCalendar\Engine");
    }

    /**
     * Common::getMailTxtFromTxt($formItems["calendar_event_attendees"], $field)
     * によってemailが埋め込まれた文字列が渡される
     */
    public function testThreeAttendees() {
        $response = $this->engin->makeAttendeesValue([], 'example@hotmail.co.jp, example@gmail.com, example@yahoo.co.jp');
        $this->assertEquals(array(
            'attendees' => array(
                array('email' => 'example@hotmail.co.jp'),
                array('email' => 'example@gmail.com'),
                array('email' => 'example@yahoo.co.jp'),
            )), $response);
    }

    public function testAnAttendees() {
        $response = $this->engin->makeAttendeesValue([], 'example@hotmail.co.jp');
        $this->assertEquals(array(
            'attendees' => array(
                array('email' => 'example@hotmail.co.jp'),
            )), $response);
    }

    public function testNoAttendees() {
        $response = $this->engin->makeAttendeesValue([], '');
        $this->assertEquals(array(), $response);
    }

    public function testStrHasInvalidEmail() {
        $response = $this->engin->makeAttendeesValue([], 'example@hotmail.co.jp, あいう, example@yahoo.co.jp');
        $this->assertEquals(array(
            'attendees' => array(
                array('email' => 'example@hotmail.co.jp'),
                array('email' => 'example@yahoo.co.jp'),
            )), $response);
    }
}

class MakeDateValueTest extends TestCase {
    public function setUp() {
        $this->engin = new DummyClass("Acms\Plugins\GoogleCalendar\Engine");
    }

    public function testAddHour() {
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '',
            'endTimeValue' => '+01:00:00',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-01T13:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);

        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '',
            'endTimeValue' => '+02:20:20',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-01T14:20:20",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
    }

    public function testBlankDateTime() {
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '',
            'endTimeValue' => '',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
    }
    
    public function testFillDateTime() {
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '2020-07-02',
            'endTimeValue' => '10:00:00',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-02T10:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
    }

    public function testFillDateAddTime() {
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '2020-07-02',
            'endTimeValue' => '+01:00:00',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-02T13:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
    }

    public function testAddDate() {
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '+00-00-01',
            'endTimeValue' => '',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-02T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);

        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '+01-00-01',
            'endTimeValue' => '',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2021-07-02T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
        
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '+0-0-1',
            'endTimeValue' => '',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-02T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
    }

    public function testAddDateAddTime() {
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '+00-00-01',
            'endTimeValue' => '+01:00:00',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "dateTime" => "2020-07-01T12:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "dateTime" => "2020-07-02T13:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
    }

    public function testAllDayEvent() {
        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '',
            'endDateValue' => '+00-00-01',
            'endTimeValue' => '',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "date" => "2020-07-01",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "date" => "2020-07-02",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);

        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '',
            'endDateValue' => '',
            'endTimeValue' => '',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "date" => "2020-07-01",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "date" => "2020-07-01",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);

        $response = $this->engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '',
            'endDateValue' => '2020-07-03',
            'endTimeValue' => '',
            'timeZoneValue' => 'Asia/Tokyo',
        ));
        $this->assertEquals(array(
            "start" => array(
                "date" => "2020-07-01",
                "timeZone" => "Asia/Tokyo",
            ),
            "end" => array(
                "date" => "2020-07-03",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);
    }
}

/**
 * 引数に指定したクラス名のダミークラスを作成する
 */
class DummyClass {
    public function __construct($className) {
        $this->reflection = new ReflectionClass($className);
        // コンストラクタなしで ReflectionClass をインスタンス化
        $this->instance = $this->reflection->newInstanceWithoutConstructor();
    }

    public function __call($name, $arguments) {
        $method = $this->reflection->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->instance, $arguments);
    }
}
