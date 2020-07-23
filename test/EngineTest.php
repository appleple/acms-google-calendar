<?php

use PHPUnit\Framework\TestCase;

class EnginTest extends TestCase {

    /**
     * create Engin instance without constructor
     */
    public function setUp() {
        require_once dirname(__FILE__)."/../Engine.php";
        // ReflectionClassをテスト対象のクラスをもとに作る.
        $reflection = new ReflectionClass("Acms\Plugins\GoogleCalendar\Engine");
        $targetInstance = $reflection->newInstanceWithoutConstructor();
        $this->$engin = new MethodTester($targetInstance);
    }

    public function testAddDateTime() {
        $response = $this->$engin->addDateTime("2020-7-8 10:00:00", "0-0-0 1:0:0");
        $this->assertEquals("2020-07-08 11:00:00", $response);

        $response = $this->$engin->addDateTime("2020-7-8 10:00:00", "00-00-00 01:00:00");
        $this->assertEquals("2020-07-08 11:00:00", $response);

        $response = $this->$engin->addDateTime("2020-7-8 10:00:00", "00-00-00 01:20:40");
        $this->assertEquals("2020-07-08 11:20:40", $response);

        $response = $this->$engin->addDateTime("2020-7-8 10:00:00", "10-10-10 01:00:00");
        $this->assertEquals("2031-05-18 11:00:00", $response);

        $response = $this->$engin->addDateTime("2020-7-8 10:00:00", "00-00-00 20:00:00");
        $this->assertEquals("2020-07-09 06:00:00", $response);
    }

    public function testmakeAttendeesValue() {
        $response = $this->$engin->makeAttendeesValue('example@hotmail.co.jp, example@gmail.com, example@yahoo.co.jp');
        $this->assertEquals(array(
            array('email' => 'example@hotmail.co.jp'),
            array('email' => 'example@gmail.com'),
            array('email' => 'example@yahoo.co.jp'),
        ), $response);

        $response = $this->$engin->makeAttendeesValue('example@hotmail.co.jp');
        $this->assertEquals(array(
            array('email' => 'example@hotmail.co.jp'),
        ), $response);

        $response = $this->$engin->makeAttendeesValue('');
        $this->assertEquals(array(array('email' => '')), $response);

        $response = $this->$engin->makeAttendeesValue('example@hotmail.co.jp, あいう, example@yahoo.co.jp');
        $this->assertEquals(array(
            array('email' => 'example@hotmail.co.jp'),
            array('email' => 'あいう'),
            array('email' => 'example@yahoo.co.jp'),
        ), $response);
    }

    public function testMakeDateValue() {
        $response = $this->$engin->makeDateValue([],array(
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

        $response = $this->$engin->makeDateValue([],array(
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
                "dateTime" => "2020-07-01T13:00:00",
                "timeZone" => "Asia/Tokyo",
            ),
        ), $response);

        $response = $this->$engin->makeDateValue([],array(
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

        $response = $this->$engin->makeDateValue([],array(
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

        $response = $this->$engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '+1',
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

        $response = $this->$engin->makeDateValue([],array(
            'startDateValue' => '2020-07-01',
            'startTimeValue' => '12:00:00',
            'endDateValue' => '+1',
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
}

/**
 * https://www.agent-grow.com/self20percent/2018/05/09/phpunit_testing_private_and_protected_methods/
 * より引用
 */
class MethodTester
{
    /** @var object テスト処理を実行するクラスのインスタンス */
    protected $___target_instance;

    /** @var object ReflectionClassのオブジェクト */
    protected $___reflect_obj;

    /**
     * MethodTester constructor.
     *
     * @param object $target_instance private, protectedメソッドを呼べるようにするインスタンス
     * @throws \ReflectionException
     */
    public function __construct($target_instance)
    {
        $this->___target_instance = $target_instance;
        $this->___reflect_obj     = new \ReflectionClass($target_instance);
    }

    public function __get($name)
    {
        $property = $this->___reflect_obj->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($this->___target_instance);
    }

    public function __set($name, $value)
    {
        $property = $this->___reflect_obj->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this->___target_instance, $value);
    }

    public function __call($name, $arguments)
    {
        $method = $this->___reflect_obj->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->___target_instance, $arguments);
    }
}