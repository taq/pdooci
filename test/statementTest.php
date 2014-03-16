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
     * Test an INSERT with exec()
     *
     * @return null
     */
    public function testInsertWithExec()
    {
        $rows = $this->_insertValueWithExec();
        $this->assertEquals(1, $rows);
    }

    /**
     * Test a DELETE with exec()
     *
     * @return null
     */
    public function testDeleteWithExec()
    {
        $this->_insertValueWithExec();
        $rows = $this->_deleteValueWithExec();
        $this->assertEquals(1, $rows);
    }

    /**
     * Test a fetch with PDO::FETCH_BOTH
     *
     * @return null
     */
    public function testFetchBoth()
    {
        $this->_insertValueWithExec();
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetch();
        $stmt->closeCursor();
        $this->assertTrue($this->_checkKeys(array(0,"NAME",1,"EMAIL"), array_keys($data)));
        $this->assertEquals(4, sizeof($data));
        $this->assertEquals("eustaquio", $data[0]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data[1]);
        $this->assertEquals("eustaquio", $data["NAME"]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data["EMAIL"]);
    }

    /**
     * Test a fetch with PDO::FETCH_ASSOC
     *
     * @return null
     */
    public function testFetchAssoc()
    {
        $this->_insertValueWithExec();
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertTrue($this->_checkKeys(array("NAME","EMAIL"), array_keys($data)));
        $this->assertEquals(2, sizeof($data));
        $this->assertEquals("eustaquio", $data["NAME"]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data["EMAIL"]);
    }

    /**
     * Test a fetch with PDO::FETCH_NUM
     *
     * @return null
     */
    public function testFetchNum()
    {
        $this->_insertValueWithExec();
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetch(\PDO::FETCH_NUM);
        $stmt->closeCursor();
        $this->assertTrue($this->_checkKeys(array(0,1), array_keys($data)));
        $this->assertEquals(2, sizeof($data));
        $this->assertEquals("eustaquio", $data[0]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data[1]);
    }

    /**
     * Test autocommit off and rollback
     *
     * @return null
     */
    public function testAutocommitOff()
    {
        self::$con->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        $this->_insertValueWithExec();
        self::$con->rollBack();
        $stmt = self::$con->query("select count(*) as count from people");
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals(0, intval($data["COUNT"]));
    }

    /**
     * Test autocommit off and commit
     *
     * @return null
     */
    public function testAutocommitOn()
    {
        self::$con->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        $this->_insertValueWithExec();
        self::$con->commit();
        $stmt = self::$con->query("select count(*) as count from people");
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals(1, intval($data["COUNT"]));
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

    /**
     * Insert a row with exec()
     *
     * @return PDOOCIStatement statement
     */
    private function _insertValueWithExec()
    {
        return self::$con->exec("insert into people (name,email) values ('eustaquio','eustaquiorangel@gmail.com')");
    }

    /**
     * Delete a row with exec()
     *
     * @return PDOOCIStatement statement
     */
    private function _deleteValueWithExec()
    {
        return self::$con->exec("delete from people where name='eustaquio'");
    }

    /**
     * Check the array keys
     *
     * @param array $expected keys expected
     * @param array $found    keys found
     *
     * @return all keys ok
     */
    private function _checkKeys($expected, $found)
    {
        $size = sizeof($expected);
        for ($i=0; $i<$size; $i++) {
            if ($expected[$i] != $found[$i]) {
                return false;
            }
        }
        return true;
    }
}
