<?php
use PHPUnit\Framework\TestCase;

class BookKingTest extends TestCase
{
    
    public function getConnection(){
        $mysql_host = getenv('MYSQL_HOST') ?: 'mysql';
        $mysql_user = getenv('MYSQL_USER') ?: 'root';
        $mysql_password = getenv('MYSQL_PASSWORD') ?: 'mysql';
        $connection_string = "mysql:host={$mysql_host};dbname=hello_world_test";
        $db = new PDO($connection_string, $mysql_user, $mysql_password);
        return $db;
    }
  
    public function testDescription(){
        $username = "root";
        $password = "mysql";
        $database = "hello_world_test";
        $link = mysqli_connect("mysql", $username, $password, $database);
        $expected="";
        /* Select queries return a resultset */
        if ($result = mysqli_query($link, "SELECT DESCRIPTION FROM TEST WHERE ID=1")) {
            $row=$result->fetch_assoc();
            $output=$expected;
        }
        mysqli_close($link);

        #$db=$this->getConnection();
        #$stmt = $db->prepare("SELECT DESCRIPTION FROM TEST WHERE ID=1");
        #$stmt->execute();
        #$expected = $stmt->fetchObject();
        $this->assertSame('Testing', $expected);
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