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
class PDOOCIStatement extends \PDOStatement
{
    /* var PDO $_pdooci */
    private $_pdooci    = null;
    private $_con       = null;
    private $_statement = null;
    private $_stmt      = null;
    private $_fetch_sty = null;
    private $_current   = null;
    private $_pos       = 0;
    private $_binds     = array();
    protected $_queryString = '';
    /**
     * Constructor
     *
     * @param PDO $pdooci    PDOOCI connection
     * @param string   $statement sql statement
     *
     * @return PDOOCIStatement $statement created
     * @throws \PDOException
     */
    public function __construct(PDO $pdooci, $statement)
    {
        try {
            $this->_pdooci    = $pdooci;
            $this->_con       = $pdooci->getConnection();
            $this->_statement = PDOOCIStatement::insertMarks($statement);
            $this->_stmt      = \oci_parse($this->_con, $this->_statement);
            $this->_fetch_sty = \PDO::FETCH_BOTH;

            $this->_queryString = $this->_statement;
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
     * @throws \PDOException
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
     * @param mixed $paramno
     * @param mixed $param
     * @param null $type
     * @param null $maxlen
     * @param null $driverdata
     * @return bool
     */
    public function bindParam($paramno, &$param, $type=null, $maxlen=null, $driverdata=null)
    {
        try {
            $paramno    = $this->_getBindVar($paramno);
            $ok         = \oci_bind_by_name($this->_stmt, $paramno, $param);
            $this->_binds[$paramno] = $param;
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
     * @return string correct name for binding
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
     * @return boolean
     * @throws \PDOException
     */
    public function execute($values=null)
    {
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
     * @throws \PDOException
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
     * @param null $how
     * @param null $orientation
     * @param null $offset
     *
     * @return array|mixed|null|object
     */
    public function fetch($how = NULL, $orientation = NULL, $offset = NULL)
    {
        set_error_handler(array($this->_pdooci,"errorHandler"));
        try {
            $style = !$how ? $this->_fetch_sty : $how;
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
     * @param null $how
     * @param null $class_name
     * @param null $ctor_args
     *
     * @return array|null
     */
    public function fetchAll($how=null, $class_name=null, $ctor_args=null)
    {
        $style = is_null($how) ? \PDO::FETCH_BOTH : $how;
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
                        function ($vals) use ($class_name) {
                            return $vals[intval($class_name)];
                        }, $rst
                    );
                    break;

                case \PDO::FETCH_COLUMN|\PDO::FETCH_GROUP:
                    \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_NUM);
                    $temp = array();
                    foreach ($rst as $value) {
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
                    foreach ($rst as $data) {
                        array_push($temp, $this->_createObjectFromData($class_name, $data));
                    }
                    $rst  = $temp;
                    break;

                case \PDO::FETCH_FUNC:
                    if (!function_exists($class_name)) {
                        throw new \PDOException("Function $class_name does not exists");
                    }
                    $ref  = new \ReflectionFunction($class_name);
                    $args = $ref->getNumberOfParameters();
                    if ($args<1) {
                        throw new \PDOException("Function $class_name can't receive parameters");
                    }
                    \oci_fetch_all($this->_stmt, $rst, 0, -1, \OCI_FETCHSTATEMENT_BY_ROW + \OCI_NUM);
                    foreach ($rst as $value) {
                        $temp = array();
                        foreach ($value as $key => $data) {
                            array_push($temp, $data);
                        }
                        call_user_func_array($class_name, $temp);
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
     * @param string $class_name
     * @param null   $ctor_args
     *
     * @return mixed|null|\stdClass
     */
    public function fetchObject($class_name='stdClass', $ctor_args=null)
    {
        try {
            $data = $this->fetch(\PDO::FETCH_ASSOC);
            return $this->_createObjectFromData($class_name, $data);
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
     * @return \stdClass
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
     * Return the current value
     *
     * @return null
     */
    public function current()
    {
        if (!$this->_current) {
            $this->next();
            if (!$this->_current) {
                $this->_pos = -1;
                $this->closeCursor();
            }
        }
        return $this->_current;
    }

    /**
     * Return the current key/position
     *
     * @return null
     */
    public function key()
    {
        return $this->_pos;
    }

    /**
     * Return the next value
     *
     * @return null
     */
    public function next()
    {
        $this->_current = $this->fetch(\PDO::FETCH_ASSOC);
        if (!$this->_current) {
            $this->_pos = -1;
        }
        $this->_checkBinds();
    }

    /**
     * Rewind
     *
     * @return null
     */
    public function rewind()
    {
        $this->_pos = 0;
    }

    /**
     * Check if the current value is valid
     *
     * @return null
     */
    public function valid()
    {
        $valid = $this->_pos >= 0;
        return $valid;
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
     * @param mixed &$param variable
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
     * @throws \PDOException
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
