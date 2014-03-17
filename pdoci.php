<?php
/**
 * PDOCI
 *
 * PHP version 5.3
 *
 * @category PDOOCI
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdoci
 */
namespace PDOOCI;
require_once "statement.php";

/**
 * Main class of PDOCI
 *
 * PHP version 5.3
 *
 * @category Connection
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdoci
 */
class PDO
{
    private $_con = null;
    private $_autocommit = true;

    /** 
     * Class constructor
     *
     * @param string $data     the connection string
     * @param string $username user name
     * @param string $password password
     * @param string $options  options to send to the connection
     *
     * @return PDO object
     */
    public function __construct($data, $username, $password, $options=null)
    {
        try {
            if (!is_null($options) && array_key_exists(\PDO::ATTR_PERSISTENT, $options)) {
                $this->_con = \oci_pconnect($username, $password, $data);
            } else {
                $this->_con = \oci_connect($username, $password, $data);
            }
        } catch (\Exception $exception) {
            throw new \PDOException($exception->getMessage());
        }
        return $this;
    }

    /**
     * Return the connection
     *
     * @return connection handle
     */
    public function getConnection() 
    {
        return $this->_con;
    }

    /**
     * Execute a query
     *
     * @param string $statement sql query
     * @param int    $mode      PDO query() mode
     * @param int    $p1        PDO query() first parameter
     * @param int    $p2        PDO query() second parameter
     *
     * @return PDOOCIStatement
     */
    public function query($statement, $mode=null, $p1=null, $p2=null)
    {
        // TODO: use mode and parameters
        try {
            $stmt = new PDOOCIStatement($this, $statement);
            $stmt->execute();
            return $stmt;
        } catch (Exception $e) {
            throw new \PDOException($exception->getMessage());
        }
    }

    /**
     * Execute query
     *
     * @param string $sql query
     *
     * @return number of affected rows
     */
    public function exec($sql)
    {
        try {
            $stmt = $this->query($sql);
            $rows = $stmt->rowCount();
            $stmt->closeCursor();
            return $rows;
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $this;
    }

    /**
     * Set an attribute
     *
     * @param int   $attr  attribute
     * @param mixed $value value
     *
     * @return boolean if set was ok
     */
    public function setAttribute($attr, $value)
    {
        switch($attr)
        {
        case \PDO::ATTR_AUTOCOMMIT:
            $this->_autocommit = $value || in_array(strtolower($value), array("on", "true"));
            return;
        }
    }

    /**
     * Return the auto commit flag
     *
     * @return boolean auto commit flag
     */
    public function getAutoCommit()
    {
        return $this->_autocommit;
    }

    /**
     * Commit connection
     *
     * @return boolean if commit was executed
     */
    public function commit()
    {
        \oci_commit($this->_con);
    }

    /**
     * Rollback connection
     *
     * @return boolean if rollback was executed
     */
    public function rollBack()
    {
        \oci_rollback($this->_con);
    }

    /**
     * Prepare a statement
     *
     * @param string $query   for statement
     * @param mixed  $options for driver
     *
     * @return PDOOCIStatement
     */
    public function prepare($query, $options=null)
    {
        $stmt = null;
        try {
            $stmt  = new PDOOCIStatement($this, $query);
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $stmt;
    }

    /**
     * Close connection
     *
     * @return null
     */
    public function close()
    {
        if (is_null($this->_con)) {
            return;
        }
        \oci_close($this->_con);
        $this->_con = null;
    }
}
?>
