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

class BookKingDbTest extends TestCase{
    
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
    
    public function testCourse(){
        $courseArray = $this->getCourseArray();
        $this->assertSame("2", $courseArray[0]);
    }
    
    public function getNameArray(){
        $readjson = file_get_contents('mdl_bookking.json');

        $data = json_decode($readjson, true);

        $nameArray = array();

        foreach($data as $entry){
            $name = $entry['name'];
            array_push($nameArray, $name);
        }
        
        return $nameArray;
    }
    
    public function testName(){
        $nameArray = $this->getNameArray();
        $this->assertSame("This should work now", $nameArray[0]);
    }
    
    public function getIntroArray(){
        $readjson = file_get_contents('mdl_bookking.json');

        $data = json_decode($readjson, true);

        $introArray = array();

        foreach($data as $entry){
            $intro = $entry['intro'];
            array_push($introArray, $intro);
        }
        
        return $introArray;
    }
    
    public function testIntro(){
        $introArray = $this->getIntroArray();
        $this->assertSame("This is definitely gonna work", $introArray[0]);
    }
    
    public function getModeArray(){
        $readjson = file_get_contents('mdl_bookking.json');

        $data = json_decode($readjson, true);

        $modeArray = array();

        foreach($data as $entry){
            $mode = $entry['bookkingmode'];
            array_push($modeArray, $mode);
        }
        
        return $modeArray;
    }
    
    public function testMode(){
        $modeArray = $this->getModeArray();
        $this->assertSame("onetime", $modeArray[0]);
    }
    
    public function getMaxArray(){
        $readjson = file_get_contents('mdl_bookking.json');

        $data = json_decode($readjson, true);

        $maxArray = array();

        foreach($data as $entry){
            $max = $entry['maxbookings'];
            array_push($maxArray, $max);
        }
        
        return $maxArray;
    }
    
    public function testMax(){
        $maxArray = $this->getMaxArray();
        $this->assertSame("2", $maxArray[0]);
    }
    
    public function getGuardArray(){
        $readjson = file_get_contents('mdl_bookking.json');

        $data = json_decode($readjson, true);

        $guardArray = array();

        foreach($data as $entry){
            $guard = $entry['guardtime'];
            array_push($guardArray, $guard);
        }
        
        return $guardArray;
    }
    
    public function testGuard(){
        $guardArray = $this->getGuardArray();
        $this->assertSame("600", $guardArray[0]);
    }
    
    public function getSDArray(){
        $readjson = file_get_contents('mdl_bookking.json');

        $data = json_decode($readjson, true);

        $sdArray = array();

        foreach($data as $entry){
            $sd = $entry['defaultslotduration'];
            array_push($sdArray, $sd);
        }
        
        return $sdArray;
    }
    
    public function testSD(){
        $sdArray = $this->getSDArray();
        $this->assertSame("30", $sdArray[0]);
    }
    
}

class appointmentTest extends TestCase{
    
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
    
    public function testCreated(){
        $createdArray = $this->getCreatedArray();
        $this->assertSame("1556980898", $createdArray[0]);
    }
    
    public function getAttendedArray(){
        $readjson = file_get_contents('mdl_bookking_appointment.json');

        $data = json_decode($readjson, true);

        $attendedArray = array();

        foreach($data as $entry){
            $att = $entry['attended'];
            array_push($attendedArray, $att);
        }
        
        return $attendedArray;
    }
    
    public function testAttended(){
        $attendedArray = $this->getAttendedArray();
        $this->assertSame("0", $attendedArray[0]);
    }
    
    public function getIDArray(){
        $readjson = file_get_contents('mdl_bookking_appointment.json');

        $data = json_decode($readjson, true);

        $idArray = array();

        foreach($data as $entry){
            $id = $entry['slotid'];
            array_push($idArray, $id);
        }
        
        return $idArray;
    }
    
    public function testID(){
        $idArray = $this->getIDArray();
        $this->assertSame("4", $idArray[0]);
    }
    
    public function getStudentArray(){
        $readjson = file_get_contents('mdl_bookking_appointment.json');

        $data = json_decode($readjson, true);

        $studentArray = array();

        foreach($data as $entry){
            $student = $entry['slotid'];
            array_push($studentArray, $student);
        }
        
        return $studentArray;
    }
    
    public function testStudent(){
        $studentArray = $this->getStudentArray();
        $this->assertSame("2", $studentArray[0]);
    }
    
    public function getModifiedArray(){
        $readjson = file_get_contents('mdl_bookking_appointment.json');

        $data = json_decode($readjson, true);

        $modifiedArray = array();

        foreach($data as $entry){
            $modified = $entry['timemodified'];
            array_push($modifiedArray, $modified);
        }
        
        return $modifiedArray;
    }
    
    public function testModified(){
        $modifiedArray = $this->getModifiedArray();
        $this->assertSame("1556980898", $modifiedArray[0]);
    }
}

class slotsTest extends TestCase{
    
    public function getStartArray(){
        $readjson = file_get_contents('mdl_bookking_slots.json');

        $data = json_decode($readjson, true);

        $startArray = array();

        foreach($data as $entry){
            $start = $entry['starttime'];
            array_push($durationArray, $start);
        }
        
        return $startArray;
    }
    
    public function testStart(){
        $startArray = $this->getStartArray();
        $this->assertSame("1557219600", $startArray[0]);
    }
    
    public function getDurationArray(){
        $readjson = file_get_contents('mdl_bookking_slots.json');

        $data = json_decode($readjson, true);

        $durationArray = array();

        foreach($data as $entry){
            $duration = $entry['duration'];
            array_push($durationArray, $duration);
        }
        
        return $durationArray;
    }
    
    public function testDuration(){
        $durationArray = $this->getDurationArray();
        $this->assertSame("15", $durationArray[0]);
    }
    
    public function getHideArray(){
        $readjson = file_get_contents('mdl_bookking_slots.json');

        $data = json_decode($readjson, true);

        $hideArray = array();

        foreach($data as $entry){
            $hide = $entry['hideuntil'];
            array_push($hideArray, $hide);
        }
        
        return $hideArray;
    }
    
    public function testHide(){
        $hideArray = $this->getHideArray();
        $this->assertSame("1556980826", $hideArray[0]);
    }
}