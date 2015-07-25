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

/**
 * Statement class of PDOOCI
 *
 * PHP version 5.3
 *
 * @category Statement
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdooci
 */
class Statement extends \PDOStatement implements \IteratorAggregate
{
    private $_pdooci    = null;
    private $_con       = null;
    private $_statement = null;
    private $_stmt      = null;
    private $_fetch_sty = null;
    private $_current   = null;
    private $_pos       = 0;
    private $_binds     = array();

    /**
     * Constructor
     *
     * @param resource $pdooci    PDOOCI connection
     * @param string   $statement sql statement
     *
     * @return Statement $statement created
     */
    public function __construct($pdooci, $statement)
    {
        try {
            $this->_pdooci    = $pdooci;
            $this->_con       = $pdooci->getConnection();
            $this->_statement = Statement::insertMarks($statement);
            $this->_stmt      = \oci_parse($this->_con, $this->_statement);
            $this->_fetch_sty = \PDO::FETCH_BOTH;
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * Binds a value
     *
     * @param mixed $param param (column)
     * @param mixed $value value for param
     * @param mixed $type  optional data type
     *
     * @return bool bound
     */
    public function bindValue($param, $value, $type=null)
    {
        $ok = false;
        try {
            $param = $this->_getBindVar($param);
            $ok    = \oci_bind_by_name($this->_stmt, $param, $value);
            $this->_binds[$param] = $value;
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $ok;
    }

    /**
     * Binds a param
     *
     * @param mixed $param param (column)
     * @param mixed $value value for param
     * @param mixed $type  optional data type
     * @param mixed $leng  optional length
     *
     * @return bool bound
     */
    public function bindParam($param, &$value, $type=null, $leng=null)
    {
        $ok = false;
        try {
            $param = $this->_getBindVar($param);
            $ok    = \oci_bind_by_name($this->_stmt, $param, $value);
            $this->_binds[$param] = $value;
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $ok;
    }

    /**
     * Get the variable name for binding
     *
     * @param mixed $val variable value
     *
     * @return string current name for binding
     */
    private function _getBindVar($val)
    {
        if (preg_match('/^\d+$/', $val)) {
            $val = ":pdooci_m".(intval($val)-1);
        }
        return $val;
    }

    /**
     * Execute statement
     *
     * @param mixed $values optional values
     *
     * @return this object
     * @throws \PDOException
     */
    public function execute($values=null)
    {
        $ok = false;
        set_error_handler(array($this->_pdooci,"errorHandler"));
        try {
            $this->_pdooci->getAutoCommit();
            $auto = $this->_pdooci->getAutoCommit() ? \OCI_COMMIT_ON_SUCCESS : \OCI_NO_AUTO_COMMIT;

            if ($values && sizeof($values)>0) {
                foreach ($values as $key => $val) {
                    $parm = $key;
                    if (preg_match('/^\d+$/', $key)) {
                        $parm ++;
                    }
                    $this->bindValue($parm, $values[$key]);
                    $this->_pdooci->setError();
                }
            }
            $ok = \oci_execute($this->_stmt, $auto);
            if (!$ok) {
                $this->_pdooci->setError($this->_stmt);
                $error = $this->_pdooci->errorInfo();
                throw new \PDOException($error[2]);
            }
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        restore_error_handler();
        return $ok;
    }

    /**
     * Get the number of affected rows
     *
     * @return int number of rows
     */
    public function rowCount()
    {
        set_error_handler(array($this->_pdooci,"errorHandler"));
        $rows = null;
        try {
            $rows = \oci_num_rows($this->_stmt);
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        restore_error_handler();
        return $rows;
    }

    /**
     * Close the current cursor
     *
     * @return null
     * @throws \PDOException
     */
    public function closeCursor()
    {
        set_error_handler(array($this->_pdooci,"errorHandler"));
        try {
            \oci_free_statement($this->_stmt);
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        restore_error_handler();
        $this->_stmt = null;
    }

    /**
     * Fetch a value
     *
     * @param int $style to fetch values
     *
     * @return mixed
     * @throws \PDOException
     */
    public function fetch($style=null)
    {
        set_error_handler(array($this->_pdooci,"errorHandler"));
        try {
            $style = !$style ? $this->_fetch_sty : $style;
            $this->_fetch_sty = $style;
            $rst   = null;

            switch ($style)
            {
            case \PDO::FETCH_BOTH:
            case \PDO::FETCH_BOUND:
                $rst = \oci_fetch_array($this->_stmt, \OCI_BOTH + \OCI_RETURN_NULLS);
                break;
            case \PDO::FETCH_ASSOC:
                $rst = \oci_fetch_array($this->_stmt, \OCI_ASSOC + \OCI_RETURN_NULLS);
                break;
            case \PDO::FETCH_NUM:
                $rst = \oci_fetch_array($this->_stmt, \OCI_NUM + OCI_RETURN_NULLS);
                break;
            case \PDO::FETCH_OBJ:
                $rst = \oci_fetch_object($this->_stmt);
                break;
            }
            $this->_current = $rst;
            $this->_checkBinds();
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        restore_error_handler();
        return $rst;
    }

    /**
     * Fetch all
     *
     * @param mixed $style    fetch style
     * @param mixed $argument fetch argument
     *
     * @return mixed results
     */
    public function fetchAll($style=null, $argument=null)
    {
        $style = is_null($style) ? \PDO::FETCH_BOTH : $style;
        $rst   = null;
        try {
            switch ($style)
            {
            case \PDO::FETCH_ASSOC:
                \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_ASSOC);
                break;

            case \PDO::FETCH_BOTH:
                \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_NUM + \OCI_ASSOC);
                break;

            case \PDO::FETCH_COLUMN:
                \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_NUM);
                $rst = array_map(
                    function ($vals) use ($argument) {
                        return $vals[intval($argument)];
                    }, $rst
                );
                break;

            case \PDO::FETCH_COLUMN|\PDO::FETCH_GROUP:
                \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_NUM);
                $temp = array();
                foreach ($rst as $key => $value) {
                    if (!array_key_exists($value[0], $temp)) {
                        $temp[$value[0]] = array();
                    }
                    array_push($temp[$value[0]], $value[1]);
                }
                $rst = $temp;
                break;

            case \PDO::FETCH_CLASS:
                \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_ASSOC);
                $temp = array();
                foreach ($rst as $idx => $data) {
                    array_push($temp, $this->_createObjectFromData($argument, $data));
                }
                $rst  = $temp;
                break;

            case \PDO::FETCH_FUNC:
                if (!function_exists($argument)) {
                    throw new \PDOException("Function $argument does not exists");
                }
                $ref  = new \ReflectionFunction($argument);
                $args = $ref->getNumberOfParameters();
                if ($args<1) {
                    throw new \PDOException("Function $argument can't receive parameters");
                }
                \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_NUM);
                foreach ($rst as $idx => $value) {
                    $temp = array();
                    foreach ($value as $key => $data) {
                        array_push($temp, $data);
                    }
                    call_user_func_array($argument, $temp);
                }
                break;
            }
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $rst;
    }

    /**
     * Fetch column
     *
     * @param int $colnum optional column number
     *
     * @return mixed column value
     */
    public function fetchColumn($colnum=0)
    {
        $rst = $this->fetch(\PDO::FETCH_NUM);
        return $rst[$colnum];
    }

    /**
     * Fetch data and create an object
     *
     * @param string $name class name
     *
     * @return mixed object
     */
    public function fetchObject($name='stdClass')
    {
        try {
            $data = $this->fetch(\PDO::FETCH_ASSOC);
            return $this->_createObjectFromData($name, $data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create a new object from data
     *
     * @param string $name class name
     * @param mixed  $data data to use on class
     *
     * @return new object
     */
    private function _createObjectFromData($name, $data)
    {
        try {
            $cls = new $name();
            foreach ($data as $key => $value) {
                if ($name !== 'stdClass' && !array_key_exists(strtolower($key), get_object_vars($cls))) {
                    var_dump(get_object_vars($cls));
                    continue;
                }
                $key = strtolower($key);
                $cls->$key = $value;
            }
            return $cls;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert a query to use bind marks
     *
     * @param string $query to insert bind marks
     *
     * @return string query with bind marks
     */
    public static function insertMarks($query)
    {
        preg_match_all('/\?/', $query, $marks);
        if (sizeof($marks[0])<1) {
            return $query;
        }
        foreach ($marks[0] as $idx => $mark) {
            $query = preg_replace("/\?/", ":pdooci_m$idx", $query, 1);
        }
        return $query;
    }

    /**
     * Return the current statement
     *
     * @return string statement
     */
    public function getStatement()
    {
        return $this->_statement;
    }
    
    /**
     * Return the iterator
     *
     * @return mixed iterator
     */
    public function getIterator()
    {
        return new StatementIterator($this);
    }

    /**
     * Set the fetch mode
     *
     * @param int   $mode fetch mode
     * @param mixed $p1   first optional parameter
     * @param mixed $p2   second optional parameter
     *
     * @return null
     */
    public function setFetchMode($mode, $p1=null, $p2=null)
    {
        $this->_fetch_sty = $mode;
    }

    /**
     * Return the fetch mode
     *
     * @return int mode
     */
    public function getFetchMode()
    {
        return $this->_fetch_sty;
    }

    /**
     * Bind column
     *
     * @param mixed $column as index (1-based) or name
     * @param mixed $param  variable
     * @param int   $type   type
     * @param int   $maxlen max length
     * @param mixed $driver data
     *
     * @return bool if was bound
     */
    public function bindColumn($column, &$param, $type=null, $maxlen=null, $driver=null)
    {
        $column = is_numeric($column) ? $column : strtoupper($column);
        $this->_binds[$column] = &$param;
    }

    /**
     * Check what binds are needed
     *
     * @return null
     */
    private function _checkBinds()
    {
        if ($this->_fetch_sty!=\PDO::FETCH_BOUND) {
            return;
        }
        foreach ($this->_binds as $key => &$value) {
            if (is_numeric($key)) {
                $key --;
            } else {
                $key = strtoupper($key);
            }
            if (!array_key_exists($key, $this->_current)) {
                continue;
            }
            $value = $this->_current[$key];
        }
    }

    /**
     * Column count
     *
     * @return int column count or zero if not executed
     */
    public function columnCount()
    {
        if (!$this->_stmt) {
            return 0;
        }
        try {
            return \oci_num_fields($this->_stmt);
        } catch (\Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return 0;
    }

    /**
     * Debug dump params
     *
     * @return string params
     */
    public function debugDumpParams()
    {
        $str  = "SQL: [".strlen($this->_statement)."] ".$this->_statement."\n";
        $str .= "Params: ".sizeof($this->_binds)."\n";
        foreach ($this->_binds as $key => $value) {
            $str .= "Key: Name: [".strlen($key)."] $key\n";
            $str .= "name=[".strlen($key)."] \"$key\"\n";
            $str .= "is_param=1\n";
        }
        echo substr($str, 0, strlen($str)-1);
    }

    /**
     * Return error code
     *
     * @return mixed error code
     */
    public function errorCode()
    {
        return $this->_pdooci->errorCode();
    }

    /**
     * Return error info
     *
     * @return mixed error info
     */
    public function errorInfo()
    {
        return $this->_pdooci->errorInfo();
    }

    /**
     * Set an attribute
     *
     * @param int   $attr  attribute
     * @param mixed $value value
     *
     * @return true if setted
     */
    public function setAttribute($attr, $value)
    {
        // nothing to see here
    }

    /**
     * Get an attribute
     *
     * @param int $attr attribute
     *
     * @return mixed value
     */
    public function getAttribute($attr)
    {
        // nothing to see here
    }

    /**
     * Get column meta data
     *
     * @param int $colnum column number
     *
     * @return mixed column meta data
     */
    public function getColumnMeta($colnum=0)
    {
        if (!$this->_stmt) {
            return null;
        }
        $name = \oci_field_name($this->_stmt, $colnum+1);
        $len  = \oci_field_size($this->_stmt, $colnum+1);
        $prec = \oci_field_scale($this->_stmt, $colnum+1);
        $type = \oci_field_type($this->_stmt, $colnum+1);
        return array("name"=>$name, "len"=>$len, "precision"=>$prec, "driver:decl_type"=>$type);
    }

    /**
     * Dummy method for nextRowSet
     *
     * @return bool
     */
    public function nextRowSet()
    {
        // TODO: insert some code here if needed
    }
}
?>
