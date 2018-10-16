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
 * @link     http://github.com/taq/pdooci
 */
require_once "../vendor/autoload.php";

/**
 * Testing connection
 *
 * PHP version 5.3
 *
 * @category Connection
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdooci
 */
class ConnectionTest extends PHPUnit\Framework\TestCase
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
        $this->assertNull($con->getCharset());
    }

    /**
     * Test if can connect, using parameters
     *
     * @return null
     */
    public function testConnectionWithParameters()
    {
        $user = getenv("PDOOCI_user");
        $pwd  = getenv("PDOOCI_pwd");
        $str  = getenv("PDOOCI_str");
        $con  = new PDOOCI\PDO("$str;charset=utf8", $user, $pwd);
        $this->assertNotNull($con->getConnection());
        $this->assertEquals("utf8", $con->getCharset());
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

    /** 
     * Test if OCI is present on the available drivers
     *
     * @return null
     */
    public function testDrivers()
    {
        $this->assertTrue(in_array("oci", self::$con->getAvailableDrivers()));
    }

    /**
     * Test if is on a transaction
     *
     * @return null
     */
    public function testInTransaction()
    {
        self::$con->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        $this->assertTrue(self::$con->inTransaction());
        self::$con->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
        $this->assertFalse(self::$con->inTransaction());
    }

    /**
     * Test quotes
     *
     * @return null
     */
    public function testQuote()
    {
        $this->assertEquals("'Nice'", self::$con->quote('Nice'));
        $this->assertEquals("'Naughty '' string'", self::$con->quote('Naughty \' string'));
    }

    /**
     * Test if fails if requiring the last inserted id without a sequence
     *
     * @expectedException PDOException
     * @expectedExceptionMessage SQLSTATE[IM001]: Driver does not support this function: driver does not support getting attributes in system_requirements
     *
     * @return null
     */
    public function testLastIdWithoutSequence()
    {
        $id = self::$con->lastInsertId();
    }

    /**
     * Test if returns the last inserted id with a sequence
     *
     * @return null
     */
    public function testLastIdWithSequence()
    {
        $id = self::$con->lastInsertId("people_sequence");
        $this->assertTrue(is_numeric($id));
    }

    public function testCaseDefaultValue()
    {
        $case = self::$con->getAttribute(\PDO::ATTR_CASE);
        $this->assertEquals(\PDO::CASE_NATURAL, $case);
    }

    /**
     * Test setting case
     * @param int $case
     * @dataProvider caseProvider
     */
    public function testSettingCase($case)
    {
        self::$con->setAttribute(\PDO::ATTR_CASE, $case);
        $this->assertEquals($case, self::$con->getAttribute(\PDO::ATTR_CASE));
    }

    public function caseProvider()
    {
        return array(
            array(\PDO::CASE_LOWER),
            array(\PDO::CASE_UPPER),
        );
    }
}
?>
