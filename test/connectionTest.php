<?php
/**
 * PDOCI
 *
 * PHP version 5.3
 *
 * @category Tests
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdoci
 */
require_once "../pdoci.php";

/**
 * Testing connection
 *
 * PHP version 5.3
 *
 * @category Connection
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdoci
 */
class ConnectionTest extends PHPUnit_Framework_TestCase
{
    protected static $con = null;

    /**
     * Set up a new object
     *
     * @return null
     */
    public function setUp() 
    {
        $user = getenv("PDOOCI_user");
        $pwd  = getenv("PDOOCI_pwd");
        $str  = getenv("PDOOCI_str");
        self::$con = new PDOOCI\PDO($str, $user, $pwd);
    }

    /**
     * Test if it is a valid object
     *
     * @return null
     */
    public function testObject() 
    {
        $this->assertNotNull(self::$con);
    }

    /**
     * Test if can connect
     *
     * @return null
     */
    public function testConnection()
    {
        $this->assertNotNull(self::$con->getConnection());
    }

    /**
     * Test if can connect using persistent connections
     *
     * @return null
     */
    public function testPersistentConnection()
    {
        $user = getenv("PDOOCI_user");
        $pwd  = getenv("PDOOCI_pwd");
        $str  = getenv("PDOOCI_str");
        $con  = new PDOOCI\PDO($str, $user, $pwd, array(\PDO::ATTR_PERSISTENT => true));
        $this->assertNotNull($con->getConnection());
    }

    /**
     * Test if throws an exception when failing to open connection
     *
     * @expectedException PDOException
     *
     * @return null
     */
    public function testInvalidConnection()
    {
        $user = "pdooci";
        $pwd  = "pdooci";
        $str  = "yaddayaddayadda";
        $con  = new PDOOCI\PDO($str, $user, $pwd, array(\PDO::ATTR_PERSISTENT => true));
        $this->assertNull($con->getConnection());
    }

    /**
     * Test if the connection is closed
     *
     * @return null
     */
    public function testClosed()
    {
        self::$con->close();
        $this->assertNull(self::$con->getConnection());
    }

    /**
     * Set and get an attribute
     *
     * @return null
     */
    public function testAttributes()
    {
        self::$con->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
        $this->assertTrue(self::$con->getAttribute(\PDO::ATTR_AUTOCOMMIT));
    }

    /**
     * Test the error code
     *
     * @return null
     */
    public function testErrorCode()
    {
        try {
            self::$con->exec("insert into bones (skull) values ('lucy')");
        } catch(PDOException $e) {
            $this->assertEquals(942, self::$con->errorCode());
        }
    }
}
?>
