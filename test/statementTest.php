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
 * Class for use with fetch and \PDO::FETCH_CLASS option
 *
 * PHP version 5.3
 *
 * @category Test
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdooci
 */
class User
{
    public $name;
    public $email;
}

/**
 * Print user name
 *
 * @param string $name user name
 *
 * @return null
 */
function user($name)
{
    echo "name: $name\n";
}

/**
 * Print user name and email
 *
 * @param string $name  user name
 * @param string $email user email
 *
 * @return null
 */
function useremail($name, $email)
{
    echo "name: $name email: $email\n";
}

/**
 * Testing statement
 *
 * PHP version 5.3
 *
 * @category Test
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdooci
 */
class StatementTest extends PHPUnit\Framework\TestCase
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
     * Tests for case tests
     *
     * @param int    $case  case to convert
     * @param string $name  user name
     * @param string $email user email
     *
     * @dataProvider fetchAssocWithCaseProvider
     *
     * @return null
     */
    public function testFetchAssocWithCase($case, $name, $email)
    {
        $this->_insertValueWithExec();
        self::$con->setAttribute(\PDO::ATTR_CASE, $case);
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertTrue($this->_checkKeys(array($name, $email), array_keys($data)));
        $this->assertEquals(2, sizeof($data));
        $this->assertEquals("eustaquio", $data[$name]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data[$email]);
    }

    /**
     * Data provider for key case tests
     *
     * @return mixed array with sample tests
     */
    public function fetchAssocWithCaseProvider()
    {
        return array(
            array(\PDO::CASE_LOWER, 'name', 'email'),
            array(\PDO::CASE_UPPER, 'NAME', 'EMAIL'),
            array(\PDO::CASE_NATURAL, 'NAME', 'EMAIL'),
        );
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
     * Test a fetch with PDO::FETCH_OBJ
     *
     * @return null
     */
    public function testFetchObj()
    {
        $this->_insertValueWithExec();
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetch(\PDO::FETCH_OBJ);
        $stmt->closeCursor();
        $this->assertEquals("eustaquio", $data->NAME);
        $this->assertEquals("eustaquiorangel@gmail.com", $data->EMAIL);
    }

    /**
     * Test fetch all
     *
     * @return null
     */
    public function testFetchAll()
    {
        $this->_insertValueWithExec();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetchAll();
        $stmt->closeCursor();
        $this->assertEquals(2, sizeof($data));
        $this->assertEquals("eustaquio", $data[0][0]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data[0][1]);
        $this->assertEquals("johndoe", $data[1][0]);
        $this->assertEquals("johndoe@gmail.com", $data[1][1]);
    }

    /**
     * Test fetch all assoc
     *
     * @return null
     */
    public function testFetchAllAssoc()
    {
        $this->_insertValueWithExec();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals(2, sizeof($data));
        $this->assertEquals("eustaquio", $data[0]["NAME"]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data[0]["EMAIL"]);
        $this->assertEquals("johndoe", $data[1]["NAME"]);
        $this->assertEquals("johndoe@gmail.com", $data[1]["EMAIL"]);
    }

    /**
     * Test fetch all both
     *
     * @return null
     */
    public function testFetchAllBoth()
    {
        $this->_insertValueWithExec();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetchAll(\PDO::FETCH_BOTH);
        $stmt->closeCursor();
        $this->assertEquals(2, sizeof($data));
        // $this->assertEquals("eustaquio", $data[0]["NAME"]);
        $this->assertEquals("eustaquio", $data[0][0]);
        // $this->assertEquals("eustaquiorangel@gmail.com", $data[0]["EMAIL"]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data[0][1]);
        // $this->assertEquals("johndoe", $data[1]["NAME"]);
        $this->assertEquals("johndoe", $data[1][0]);
        // $this->assertEquals("johndoe@gmail.com", $data[1]["EMAIL"]);
        $this->assertEquals("johndoe@gmail.com", $data[1][1]);
    }

    /**
     * Test fetch all with column
     *
     * @return null
     */
    public function testFetchAllWithColumn()
    {
        $this->_insertValueWithExec();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
        $this->assertEquals(2, sizeof($data));
        $this->assertEquals(1, sizeof($data[0]));
        $this->assertEquals("eustaquio", $data[0]);
        $this->assertEquals(1, sizeof($data[1]));
        $this->assertEquals("johndoe", $data[1]);
    }

    /**
     * Test fetch all with column and group
     *
     * @return null
     */
    public function testFetchAllWithColumnAndGroup()
    {
        $this->_insertValueWithExec();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@yahoo.com"));
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
        $stmt->closeCursor();

        $this->assertEquals(2, sizeof($data));
        $this->assertEquals(1, sizeof($data["eustaquio"]));
        $this->assertEquals("eustaquiorangel@gmail.com", $data["eustaquio"][0]);
        $this->assertEquals(2, sizeof($data["johndoe"]));
        $this->assertEquals("johndoe@yahoo.com", $data["johndoe"][0]);
        $this->assertEquals("johndoe@gmail.com", $data["johndoe"][1]);
    }

    /**
     * Test fetch all with class
     *
     * @return null
     */
    public function testFetchAllWithClass()
    {
        $this->_insertValueWithExec();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetchAll(\PDO::FETCH_CLASS, "User");
        $stmt->closeCursor();
        $this->assertEquals(2, sizeof($data));

        $cls = $data[0];
        $this->assertTrue(is_object($cls));
        $this->assertEquals("User", get_class($cls));
        $this->assertEquals("eustaquio", $cls->name);
        $this->assertEquals("eustaquiorangel@gmail.com", $cls->email);

        $cls = $data[1];
        $this->assertTrue(is_object($cls));
        $this->assertEquals("User", get_class($cls));
        $this->assertEquals("johndoe", $cls->name);
        $this->assertEquals("johndoe@gmail.com", $cls->email);
    }

    /**
     * Test fetch all with function
     *
     * @return null
     */
    public function testFetchAllWithFunc()
    {
        $this->_insertValueWithExec();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->query("select * from people");
        ob_start();
        $data = $stmt->fetchAll(\PDO::FETCH_FUNC, "user");
        $stmt->closeCursor();
        $rst = ob_get_clean();
        $this->assertEquals("name: eustaquio\nname: johndoe\n", $rst);
    }

    /**
     * Test fetch all with function with more than one parameter
     *
     * @return null
     */
    public function testFetchAllWithFuncWithMoreParameters()
    {
        $this->_insertValue();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->prepare("select * from people");
        $stmt->execute();
        ob_start();
        $data = $stmt->fetchAll(\PDO::FETCH_FUNC, "useremail");
        $stmt->closeCursor();
        $rst = ob_get_clean();
        $this->assertEquals("name: eustaquio email: eustaquiorangel@gmail.com\nname: johndoe email: johndoe@gmail.com\n", $rst);
    }

    /**
     * Fetch column
     *
     * @return null
     */
    public function testFetchColumn()
    {
        $this->_insertValue();
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->prepare("select * from people");
        $stmt->execute();
        $this->assertEquals("eustaquio", $stmt->fetchColumn());
        $this->assertEquals("johndoe@gmail.com", $stmt->fetchColumn(1));
    }

    /**
     * Test fetch object
     *
     * @return null
     */
    public function testFetchObject()
    {
        $this->_insertValueWithExec();
        $stmt = self::$con->query("select * from people");
        $obj  = $stmt->fetchObject("User");
        $stmt->closeCursor();

        $this->assertTrue(is_object($obj));
        $this->assertEquals("User", get_class($obj));
        $this->assertEquals("eustaquio", $obj->name);
        $this->assertEquals("eustaquiorangel@gmail.com", $obj->email);
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
        $this->assertEquals(0, $this->_getRowCount());
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
        $this->assertEquals(1, $this->_getRowCount());
    }

    /**
     * Test beginTransaction and rollback
     *
     * @return null
     */
    public function testBeginTransactionAndRollback()
    {
        self::$con->beginTransaction();
        $this->_insertValueWithExec();
        self::$con->rollBack();
        $this->assertEquals(0, $this->_getRowCount());
    }

    /**
     * Test beginTransaction and commit
     *
     * @return null
     */
    public function testBeginTransactionAndCommit()
    {
        self::$con->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
        $this->_insertValueWithExec();
        self::$con->commit();
        $this->assertEquals(1, $this->_getRowCount());
    }

    /**
     * Convert a query to use bind marks
     *
     * @return String query
     */
    public function testCreateMarks()
    {
        $sql = "insert into people (name,email) values (?,?)";
        $converted = "insert into people (name,email) values (:pdooci_m0,:pdooci_m1)";
        $this->assertEquals($converted, PDOOCI\Statement::insertMarks($sql));
    }

    /**
     * Convert a query to use bind marks, only when not inside quotes
     *
     * @return String query
     */
    public function testCreateMarksNoQuotes()
    {
        $sql = "insert into people (name,email,key) values (?,?,'abc?')";
        $converted = "insert into people (name,email,key) values (:pdooci_m0,:pdooci_m1,'abc?')";
        $this->assertEquals($converted, PDOOCI\Statement::insertMarks($sql));

        $sql = "insert into people (name,email,key) values (?,?,'?abc')";
        $converted = "insert into people (name,email,key) values (:pdooci_m0,:pdooci_m1,'?abc')";
        $this->assertEquals($converted, PDOOCI\Statement::insertMarks($sql));

        $sql = "insert into people (name,email,key) values (?,?,'?abc?')";
        $converted = "insert into people (name,email,key) values (:pdooci_m0,:pdooci_m1,'?abc?')";
        $this->assertEquals($converted, PDOOCI\Statement::insertMarks($sql));
    }

    /**
     * Don't change the query if is not needed
     *
     * @return String query
     */
    public function testDontCreateMarks()
    {
        $sql = "insert into people (name,email) values (:name,:email)";
        $this->assertEquals($sql, PDOOCI\Statement::insertMarks($sql));
    }

    /**
     * Prepare a statement without values
     *
     * @return Statement statement
     */
    public function testPreparedWithoutValues()
    {
        $sql  = "insert into people (name,email) values ('eustaquio','eustaquiorangel@gmail.com')";
        $stmt = self::$con->prepare($sql);
        $this->assertNotNull($stmt);
        $this->assertEquals($sql, $stmt->getStatement());
        $this->assertTrue($stmt->execute());
        $rows = $stmt->rowCount();
        $stmt->closeCursor();
        $this->assertEquals(1, $rows);

        $data = $this->_getValues();
        $this->assertEquals("eustaquio", $data["NAME"]);
        $this->assertEquals("eustaquiorangel@gmail.com", $data["EMAIL"]);
    }

    /**
     * Prepare a statement with numeric index based values
     *
     * @return Statement statement
     */
    public function testPreparedWithNumericIndexes()
    {
        $user = "u".mktime();
        $email= "$user@gmail.com";
        $sql  = "insert into people (name,email) values (?,?)";
        $exp  = "insert into people (name,email) values (:pdooci_m0,:pdooci_m1)";
        $stmt = self::$con->prepare($sql);
        $this->assertNotNull($stmt);
        $this->assertEquals($exp, $stmt->getStatement());
        $this->assertTrue($stmt->execute(array($user,$email)));
        $rows = $stmt->rowCount();
        $stmt->closeCursor();
        $this->assertEquals(1, $rows);

        $data = $this->_getValues();
        $this->assertEquals($user,  $data["NAME"]);
        $this->assertEquals($email, $data["EMAIL"]);
    }

    /**
     * Prepare a statement with named based values
     *
     * @return Statement statement
     */
    public function testPreparedWithNamedIndexes()
    {
        $user = "u".mktime();
        $email= "$user@gmail.com";
        $sql  = "insert into people (name,email) values (:name,:email)";
        $stmt = self::$con->prepare($sql);
        $this->assertEquals($sql, $stmt->getStatement());
        $this->assertNotNull($stmt);
        $this->assertTrue($stmt->execute(array(":name"=>$user,":email"=>$email)));
        $rows = $stmt->rowCount();
        $stmt->closeCursor();
        $this->assertEquals(1, $rows);

        $data = $this->_getValues();
        $this->assertEquals($user,  $data["NAME"]);
        $this->assertEquals($email, $data["EMAIL"]);
    }

    /**
     * Prepare a crazy statement with indexes and named based values
     * What stupid crazy maniac will make something like that?
     *
     * @return Statement statement
     */
    public function testPreparedWithCrazyIndexes()
    {
        $user = "u".mktime();
        $email= "$user@gmail.com";
        $sql  = "insert into people (name,email) values (?,:email)";
        $exp  = "insert into people (name,email) values (:pdooci_m0,:email)";
        $stmt = self::$con->prepare($sql);
        $this->assertEquals($exp, $stmt->getStatement());
        $this->assertNotNull($stmt);
        $this->assertTrue($stmt->execute(array(0=>$user,":email"=>$email)));
        $rows = $stmt->rowCount();
        $stmt->closeCursor();
        $this->assertEquals(1, $rows);

        $data = $this->_getValues();
        $this->assertEquals($user,  $data["NAME"]);
        $this->assertEquals($email, $data["EMAIL"]);
    }

    /** 
     * Return results on a foreach loop
     *
     * @return null
     */
    public function testForeach()
    {
        $this->_insertValue();
        $this->_insertValue();
        $rst = array();
        foreach (self::$con->query("select * from people") as $row) {
            array_push($rst, $row);
        }
        $this->assertEquals(2, sizeof($rst));
    }

    /**
     * Fetch mode
     *
     * @return null
     */
    public function testFetchMode()
    {
        $stmt = self::$con->prepare("select * from people");
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        $this->assertEquals(\PDO::FETCH_ASSOC, $stmt->getFetchMode());
        $stmt->closeCursor();
    }

    /**
     * Bind column
     *
     * @return null
     */
    public function testBindColumn()
    {
        $this->_insertValue();
        $stmt = self::$con->prepare("select * from people");
        $stmt->execute();
        $stmt->bindColumn(1, $name);
        $stmt->bindColumn("email", $email);
        $row = $stmt->fetch(\PDO::FETCH_BOUND);
        $this->assertEquals("eustaquio", $name);
        $this->assertEquals("eustaquiorangel@gmail.com", $email);
    }

    /**
     * Bind named value
     *
     * @return null
     */
    public function testBindNamedValue()
    {
        $name = "eustaquio";
        $this->_insertValue();
        $stmt = self::$con->prepare("select * from people where name=:name");
        $stmt->bindValue(":name", $name, \PDO::PARAM_STR);
        $name = "johndoe";
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals("eustaquio", $data["NAME"]);
    }

    /**
     * Bind numeric value
     *
     * @return null
     */
    public function testBindNumericValue()
    {
        $name = "eustaquio";
        $this->_insertValue();
        $stmt = self::$con->prepare("select * from people where name=?");
        $stmt->bindValue(1, $name, \PDO::PARAM_STR);
        $name = "johndoe";
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals("eustaquio", $data["NAME"]);
    }

    /**
     * Bind named param
     *
     * @return null
     */
    public function testBindNamedParam()
    {
        $name = "eustaquio";
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->prepare("select * from people where name=:name");
        $stmt->bindParam(":name", $name, \PDO::PARAM_STR);
        $name = "johndoe";
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals($name, $data["NAME"]);
    }

    /**
     * Bind named param with length
     *
     * @return null
     */
    public function testBindNamedParamWithLength()
    {
        $name = "eustaquio";
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->prepare("select * from people where name=:name");
        $stmt->bindParam(":name", $name, \PDO::PARAM_STR, 7);
        $name = "johndoe";
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals($name, $data["NAME"]);
    }

    /**
     * Bind numeric value
     *
     * @return null
     */
    public function testBindNumericParam()
    {
        $name = "eustaquio";
        $this->_insertValue(array("name"=>"johndoe","email"=>"johndoe@gmail.com"));
        $stmt = self::$con->prepare("select * from people where name=?");
        $stmt->bindParam(1, $name, \PDO::PARAM_STR);
        $name = "johndoe";
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals($name, $data["NAME"]);
    }

    /**
     * Column count
     *
     * @return null
     */
    public function testColumnCount()
    {
        $this->_insertValue();
        $stmt = self::$con->prepare("select * from people");
        $this->assertEquals(0, $stmt->columnCount());
        $stmt->execute();
        $this->assertEquals(2, $stmt->columnCount());
        $stmt->closeCursor();
    }

    /**
     * Debug dump params
     *
     * @return null
     */
    public function testDebugDumpParams()
    {
        $this->_insertValue();
        $name = "eustaquio";
        $email= "eustaquiorangel@gmail.com";
        $stmt = self::$con->prepare("select * from people where name=:name and email=:email");
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $str =<<<END
SQL: [54] select * from people where name=:name and email=:email
Params: 2
Key: Name: [5] :name
name=[5] ":name"
is_param=1
Key: Name: [6] :email
name=[6] ":email"
is_param=1
END;
        ob_start();
        $stmt->debugDumpParams();
        $contents = ob_get_clean();
        $this->assertEquals($str, $contents);
        $stmt->closeCursor();
    }

    /**
     * Test column meta data
     *
     * @return null
     */
    public function testMetaData()
    {
        $this->_insertValue();
        $stmt = self::$con->prepare("select * from people");
        $stmt->execute();

        $data = $stmt->getColumnMeta(0);
        $this->assertEquals("NAME", $data["name"]);
        $this->assertEquals(50, $data["len"]);
        $this->assertEquals(0, $data["precision"]);
        $this->assertEquals("VARCHAR2", $data["driver:decl_type"]);

        $data = $stmt->getColumnMeta(1);
        $this->assertEquals("EMAIL", $data["name"]);
        $this->assertEquals(30, $data["len"]);
        $this->assertEquals(0, $data["precision"]);
        $this->assertEquals("VARCHAR2", $data["driver:decl_type"]);
    }

    /**
     * Fetch null values
     *
     * @return null
     */
    public function testFetchNulls()
    {
        $this->_insertValue(array("name"=>"johndoe","email"=>null));
        $stmt = self::$con->prepare("select * from people");
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals(2, sizeof($data));
        $this->assertEquals("johndoe", $data["NAME"]);
        $this->assertNull($data["EMAIL"]);
    }

    /**
     * Fetch all null values
     *
     * @return null
     */
    public function testFetchAllNulls()
    {
        $this->_insertValue(array("name"=>"eustaquio" ,"email"=>null));
        $this->_insertValue(array("name"=>"johndoe"   ,"email"=>null));
        $stmt = self::$con->prepare("select * from people");
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $this->assertEquals(2, sizeof($data));

        $this->assertEquals("eustaquio", $data[0]["NAME"]);
        $this->assertNull($data[0]["EMAIL"]);

        $this->assertEquals("johndoe", $data[1]["NAME"]);
        $this->assertNull($data[1]["EMAIL"]);
    }

    /****************************************************************************
     *  Helper functions                                                        *
     ***************************************************************************/

    /**
     * Insert a row
     *
     * @param mixed $values optional values
     *
     * @return Statement statement
     */
    private function _insertValue($values=null)
    {
        $name  = "eustaquio";
        $email = "eustaquiorangel@gmail.com";

        if (!is_null($values)) {
            $name  = $values["name"];
            $email = $values["email"];
        }
        return self::$con->query("insert into people (name,email) values ('$name','$email')");
    }

    /**
     * Delete a row
     *
     * @return Statement statement
     */
    private function _deleteValue()
    {
        return self::$con->query("delete from people where name='eustaquio'");
    }

    /**
     * Insert a row with exec()
     *
     * @return Statement statement
     */
    private function _insertValueWithExec()
    {
        return self::$con->exec("insert into people (name,email) values ('eustaquio','eustaquiorangel@gmail.com')");
    }

    /**
     * Delete a row with exec()
     *
     * @return Statement statement
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

    /**
     * Get values from table
     *
     * @return array values
     */
    private function _getValues()
    {
        $stmt = self::$con->query("select * from people");
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * Get the number of rows on the table
     *
     * @return int number of rows
     */
    private function _getRowCount()
    {
        $stmt = self::$con->query("select count(*) as count from people");
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return intval($data["COUNT"]);
    }
}
