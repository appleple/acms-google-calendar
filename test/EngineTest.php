<?php

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class EnginTest extends TestCase {
    public function testAddDateTime() {
        $engin = $this->mkInstanceWithoutConstructior();
        $response = $engin->addDateTime("2020-7-8 10:00:00", "0-0-0 1:0:0");
        $this->assertEquals("2020-07-08 11:00:00", $response);

        $response = $engin->addDateTime("2020-7-8 10:00:00", "00-00-00 01:00:00");
        $this->assertEquals("2020-07-08 11:00:00", $response);

        $response = $engin->addDateTime("2020-7-8 10:00:00", "00-00-00 01:20:40");
        $this->assertEquals("2020-07-08 11:20:40", $response);

        $response = $engin->addDateTime("2020-7-8 10:00:00", "10-10-10 01:00:00");
        $this->assertEquals("2031-05-18 11:00:00", $response);

        $response = $engin->addDateTime("2020-7-8 10:00:00", "00-00-00 20:00:00");
        $this->assertEquals("2020-07-09 06:00:00", $response);
    }

    /**
     * create Engin instance with out constructor
     */
    public function mkInstanceWithoutConstructior()
    {
        require dirname(__FILE__)."/../Engine.php";
        // ReflectionClassをテスト対象のクラスをもとに作る.
        $reflection = new ReflectionClass("Acms\Plugins\GoogleCalendar\Engine");
        $targetInstance = $reflection->newInstanceWithoutConstructor();
        $testInstance = new MethodTester($targetInstance);
        return $testInstance;
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