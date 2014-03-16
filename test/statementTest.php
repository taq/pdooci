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
require_once "../statement.php";

/**
 * Testing statement
 *
 * PHP version 5.3
 *
 * @category Connection
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdoci
 */
class StatementTest extends PHPUnit_Framework_TestCase
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
        self::$con = new PDOOCI\PDOOCI($str, $user, $pwd);
        self::$con->query("delete from people");
    }

    /**
     * Test an INSERT
     *
     * @return null
     */
    public function testInsert()
    {
        $stmt = $this->_insertValue();
        $this->assertEquals(1, $stmt->rowCount());
    }

    /**
     * Test a DELETE
     *
     * @return null
     */
    public function testDelete()
    {
        $this->_insertValue();
        $stmt = $this->_deleteValue();
        $this->assertEquals(1, $stmt->rowCount());
    }

    /**
     * Insert a row
     *
     * @return PDOOCIStatement statement
     */
    private function _insertValue()
    {
        return self::$con->query("insert into people (name,email) values ('eustaquio','eustaquiorangel@gmail.com')");
    }

    /**
     * Delete a row
     *
     * @return PDOOCIStatement statement
     */
    private function _deleteValue()
    {
        return self::$con->query("delete from people where name='eustaquio'");
    }
}
