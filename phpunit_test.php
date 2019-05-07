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
            $duration = $entry['duration'];
            array_push($durationArray, $duration);
        }
        
        return $durationArray;
    }
    
    public function testID(){
        $idArray = $this->getIDArray();
        if ($var1 == $var2){
            echo 'not equal';
        }
        else if ($var2 == $var1){
            echo 'still not equal';
        }
        $this->assertSame('1', $idArray[0]);
        $this->assertSame('2', $idArray[1]);
    }
    
    public function testDescription(){
        $descArray = $this->getDescArray();
        if ($var1 == $var2){
            echo 'not equal';
        }
        else if ($var2 == $var1){
            echo 'still not equal';
        }
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
        $var1 = 1;
        $var2 = 2;
        if ($var1 == $var2){
            echo 'not equal';
        }
        else if ($var2 == $var1){
            echo 'still not equal';
        }
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

class mod_bookking_generator extends testing_module_generator {

    private function set_default($record, $property, $value) {
        if (!isset($record->$property)) {
            $record->$property = $value;
        }
    }

    /**
     * Create new bookking module instance
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/mod/bookking/lib.php");

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }
        self::set_default($record, 'name', get_string('pluginname', 'bookking').' '.$i);
        self::set_default($record, 'intro', 'Test bookking '.$i);
        self::set_default($record, 'introformat', FORMAT_MOODLE);
        self::set_default($record, 'bookkingmode', 'onetime');
        self::set_default($record, 'guardtime', 0);
        self::set_default($record, 'defaultslotduration', 15);
        self::set_default($record, 'staffrolename', '');
        self::set_default($record, 'scale', 0);
        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        } else {
            $record->cmidnumber = '';
        }

        $record->coursemodule = $this->precreate_course_module($record->course, $options);
        $id = bookking_add_instance($record);
        $modinst = $this->post_add_instance($id, $record->coursemodule);

        if (isset($options['slottimes'])) {
            $slottimes = (array) $options['slottimes'];
            foreach ($slottimes as $slotkey => $time) {
                $slot = new stdClass();
                $slot->bookkingid = $id;
                $slot->starttime = $time;
                $slot->duration = 10;
                $slot->teacherid = 2; // Admin user - for the moment.
                $slot->appointmentlocation = 'Test Loc';
                $slot->timemodified = time();
                $slot->notes = '';
                $slot->slotnote = '';
                $slot->exclusivity = isset($options['slotexclusivity'][$slotkey]) ? $options['slotexclusivity'][$slotkey] : 0;
                $slot->emaildate = 0;
                $slot->hideuntil = 0;
                $slotid = $DB->insert_record('bookking_slots', $slot);

                if (isset($options['slotstudents'][$slotkey])) {
                    $students = (array)$options['slotstudents'][$slotkey];
                    foreach ($students as $studentkey => $userid) {
                        $appointment = new stdClass();
                        $appointment->slotid = $slotid;
                        $appointment->studentid = $userid;
                        $appointment->attended = isset($options['slotattended'][$slotkey]) && $options['slotattended'][$slotkey];
                        $appointment->grade = 0;
                        $appointment->appointmentnote = '';
                        $appointment->teachernote = '';
                        $appointment->timecreated = time();
                        $appointment->timemodified = time();
                        $appointmentid = $DB->insert_record('bookking_appointment', $appointment);
                    }
                }
            }
        }

        return $modinst;
    }
}
