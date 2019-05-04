<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;

class BookKingTest extends TestCase
{
    
  public function getIDArray(){
        $readjson = file_get_contents('data.json');

        $data = json_decode($readjson, true);

        $idArray = array();
        
        foreach($data as $entry){
            $id = $entry['ID'];
            array_push($idArray, $id);
        }
        
        return $idArray;
    }
  
    public function getDescArray(){
        $readjson = file_get_contents('data.json');

        $data = json_decode($readjson, true);

        $descArray = array();

        foreach($data as $entry){
            $desc = $entry['DESCRIPTION'];
            array_push($descArray, $desc);
        }
        
        return $descArray;
    }
    
    public function getCourseArray(){
        $readjson = file_get_contents('mdl_bookking.json');

        $data = json_decode($readjson, true);

        $courseArray = array();

        foreach($data as $entry){
            $course = $entry['course'];
            array_push($courseArray, $course);
        }
        
        return $courseArray;
    }
    
    public function getCreatedArray(){
        $readjson = file_get_contents('mdl_bookking_appointment.json');

        $data = json_decode($readjson, true);

        $createdArray = array();

        foreach($data as $entry){
            $time = $entry['timecreated'];
            array_push($createdArray, $time);
        }
        
        return $createdArray;
    }
    
    public function getDurationArray(){
        $readjson = file_get_contents('mdl_bookking_slots.json');

        $data = json_decode($readjson, true);

        $durationArray = array();

        foreach($data as $entry){
            $duration = $entry['timecreated'];
            array_push($durationArray, $duration);
        }
        
        return $durationArray;
    }
    
    public function testID(){
        $idArray = $this->getIDArray();
        $this->assertSame('1', $idArray[0]);
        $this->assertSame('2', $idArray[1]);
    }
    
    public function testDescription(){
        $descArray = $this->getDescArray();
        $this->assertSame("Testing", $descArray[0]);
        $this->assertSame("Testing2", $descArray[1]);
    }
  
    public function testCourse(){
        $courseArray = $this->getCourseArray();
        $this->assertSame("2", $courseArray[0]);
    }
  
    public function testTime(){
        $createdArray = $this->getCreatedArray();
        $this->assertSame("1556980898", $createdArray[0]);
    }
    
    public function testDuration(){
        $durationArray = $this->getDurationArray();
        $this->assertSame("15", $durationArray[0]);
    }
    
    public function testRecordCount(){
        $stack = [];
        $this->assertSame(0, count($stack));

        array_push($stack, 'foo');
        $this->assertSame('foo', $stack[count($stack)-1]);
        $this->assertSame(1, count($stack));

        $this->assertSame('foo', array_pop($stack));
        $this->assertSame(0, count($stack));
    }
    
    public function testPushAndPop(){
        $arr = [];

        array_push($arr, 'Bob');
        array_push($arr, 'Andy');
        $this->assertSame('Bob', $arr[0]);
    }
    
}