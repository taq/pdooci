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
 * @link     http://github.com/taq/pdooci
 */
namespace PDOOCI;
require_once dirname(__FILE__)."/Statement.php";
require_once dirname(__FILE__)."/StatementIterator.php";

/**
 * Main class of PDOOCI
 *
 * PHP version 5.3
 *
 * @category Connection
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdooci
 */
class PDO extends \PDO
{
    private $_con = null;
    private $_autocommit = true;
    private $_last_error = null;
    private $_charset    = null;
    private $_case       = \PDO::CASE_NATURAL;

    /**
     * Class constructor
     *
     * @param string $data     the connection string
     * @param string $username user name
     * @param string $password password
     * @param array $options  options to send to the connection
     *
     * @return \PDO object
     * @throws \PDOException
     */
    public function __construct($data, $username, $password, array $options=null)
    {
        if (!function_exists("\oci_parse")) {
            throw new \PDOException("No support for Oracle, please install the OCI driver");
        }

        // find charset
        $charset = null;
        $data    = preg_replace('/^oci:/', '', $data);
        $tokens  = preg_split('/;/', $data);
        $data    = str_replace(array('dbname=//', 'dbname='), '', $tokens[0]);
        $charset = $this->_getCharset($tokens);

        try {
            if (!empty($options) && array_key_exists(\PDO::ATTR_PERSISTENT, $options)) {
                $this->_con = \oci_pconnect($username, $password, $data, $charset);
                $this->setError();
            } else {
                $this->_con = \oci_connect($username, $password, $data, $charset);
                $this->setError();
            }
            if (!$this->_con) {
                $error = oci_error();
                throw new \Exception($error['code'].': '.$error['message']);
            }
        } catch (\Exception $exception) {
            throw new \PDOException($exception->getMessage());
        }
        return $this;
    }

    /**
     * Return the charset
     *
     * @return string charset
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * Find the charset
     *
     * @param string $charset charset
     *
     * @return charset
     */
    private function _getCharset($charset=null)
    {
        if (!$charset) {
            $langs = array_filter(array(getenv("NLS_LANG")), "strlen");
            return array_shift($langs);
        }

        $expr   = '/^(charset=)(\w+)$/';
        $tokens = array_filter(
            $charset, function ($token) use ($expr) {
                return preg_match($expr, $token, $matches);
            }
        );
        if (sizeof($tokens)>0) {
            preg_match($expr, array_shift($tokens), $matches);
            $this->_charset = $matches[2];
        } else {
            $this->_charset = null;
        }
        return $this->_charset;
    }

    /**
     * Return the connection
     *
     * @return resource handle
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
     * @return Statement
     * @throws \PDOException
     */
    public function query($statement, $mode=null, $p1=null, $p2=null)
    {
        // TODO: use mode and parameters
        $stmt = null;
        try {
            $stmt = new Statement($this, $statement);
            $stmt->execute();
            $this->setError();
            return $stmt;
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $stmt;
    }

    /**
     * Execute query
     *
     * @param string $sql query
     *
     * @return number of affected rows
     * @throws \PDOException
     */
    public function exec($sql)
    {
        try {
            $stmt = $this->query($sql);
            $rows = $stmt->rowCount();
            $stmt->closeCursor();
            return $rows;
        } catch (\Exception $e) {
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
            $this->_autocommit = (is_bool($value) && $value) || in_array(strtolower($value), array("on", "true"));
            return;
        case \PDO::ATTR_CASE:
            $this->_case = $value;
            return;
        }
    }

    /**
     * Return an attribute
     *
     * @param int $attr attribute
     *
     * @return mixed attribute value
     */
    public function getAttribute($attr)
    {
        switch($attr)
        {
        case \PDO::ATTR_AUTOCOMMIT:
            return $this->_autocommit;
        case \PDO::ATTR_DRIVER_NAME:
            return 'oci';
        case \PDO::ATTR_CASE:
            return $this->_case;
        }
        return null;
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
        $this->setError();
    }

    /**
     * Rollback connection
     *
     * @return boolean if rollback was executed
     */
    public function rollBack()
    {
        \oci_rollback($this->_con);
        $this->setError();
    }

    /**
     * Start a transaction, setting auto commit to off
     *
     * @return null
     */
    public function beginTransaction()
    {
        $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
    }

    /**
     * Prepare a statement
     *
     * @param string $query   for statement
     * @param mixed  $options for driver
     *
     * @return Statement
     * @throws \PDOException
     */
    public function prepare($query, $options=null)
    {
        $stmt = null;
        try {
            $stmt = new Statement($this, $query);
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $stmt;
    }

    /**
     * Sets the last error found
     *
     * @param mixed $obj optional object to extract error from
     *
     * @return null
     */
    public function setError($obj=null)
    {
        $obj = $obj ? $obj : $this->_con;
        if (!is_resource($obj)) {
            return;
        }
        $error = \oci_error($obj);
        if (!$error) {
            return null;
        }
        $this->_last_error = $error;
    }

    /**
     * Returns the last error found
     *
     * @return int error code
     */
    public function errorCode()
    {
        if (!$this->_last_error) {
            return null;
        }
        return intval($this->_last_error["code"]);
    }

    /**
     * Returns the last error info
     *
     * @return array error info
     */
    public function errorInfo()
    {
        if (!$this->_last_error) {
            return null;
        }
        return array($this->_last_error["code"],
                     $this->_last_error["code"],
                     $this->_last_error["message"]);
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

    /**
     * Trigger stupid errors who should be exceptions
     *
     * @param int    $errno   error number
     * @param string $errstr  error message
     * @param mixed  $errfile error file
     * @param int    $errline error line
     *
     * @return null
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        preg_match('/(ORA-)(\d+)/', $errstr, $ora_error);
        if ($ora_error) {
            $this->_last_error = intval($ora_error[2]);
        } else {
            $this->setError($this->_con);
        }
    }

    /**
     * Return available drivers
     * Will insert the OCI driver on the list, if not exist
     *
     * @return array with drivers
     */
    public static function getAvailableDrivers()
    {
        $drivers = \PDO::getAvailableDrivers();
        if (!in_array("oci", $drivers)) {
            array_push($drivers, "oci");
        }
        return $drivers;
    }

    /**
     * Return if is on a transaction
     *
     * @return boolean on a transaction
     */
    public function inTransaction()
    {
        return !$this->_autocommit;
    }

    /**
     * Quote a string
     *
     * @param string $string to be quoted
     * @param int    $type   parameter type
     *
     * @return string quoted
     */
    public function quote($string, $type=null)
    {
        $string = preg_replace('/\'/', "''", $string);
        $string = "'$string'";
        return $string;
    }

    /**
     * Return the last inserted id
     * If the sequence name is not sent, throws an exception
     *
     * @param string $sequence name
     *
     * @return mixed last id
     * @throws \PDOException
     */
    public function lastInsertId($sequence=null)
    {
        if (!$sequence) {
            throw new \PDOException("SQLSTATE[IM001]: Driver does not support this function: driver does not support getting attributes in system_requirements");
        }
        $id = 0;
        try {
            $stmt = $this->query("select $sequence.currval from dual");
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            $id   = intval($data["CURRVAL"]);
        } catch (\PDOException $e) {
            $id   = -1;
        }
        return $id;
    }
}
?>
