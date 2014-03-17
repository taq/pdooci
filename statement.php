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

/**
 * State,emt class of PDOCI
 *
 * PHP version 5.3
 *
 * @category Statement
 * @package  PDOOCI
 * @author   Eustáquio Rangel <eustaquiorangel@gmail.com>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link     http://github.com/taq/pdoci
 */
class PDOOCIStatement implements \Iterator
{
    private $_pdooci    = null;
    private $_con       = null;
    private $_statement = null;
    private $_stmt      = null;
    private $_fetch_sty = null;
    private $_current   = null;
    private $_pos       = 0;
    public  $queryString= "";

    /**
     * Constructor
     *
     * @param resource $pdooci    PDOOCI connection
     * @param string   $statement sql statement
     *
     * @return PDOOCI\Statement $statement created
     */
    public function __construct($pdooci, $statement)
    {
        try {
            $this->_pdooci    = $pdooci;
            $this->_con       = $pdooci->getConnection();
            $this->_statement = PDOOCIStatement::insertMarks($statement);
            $this->_stmt      = \oci_parse($this->_con, $this->_statement);

            $this->queryString = $this->_statement;
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * Execute statement
     *
     * @param mixed $values optional values
     *
     * @return this object
     */
    public function execute($values=null)
    {
        $ok = false;
        set_error_handler(array($this->_pdooci,"errorHandler"));
        try {
            $this->_pdooci->getAutoCommit();
            $auto = $this->_pdooci->getAutoCommit() ? \OCI_COMMIT_ON_SUCCESS : \OCI_NO_AUTO_COMMIT;

            if ($values) {
                foreach ($values as $key => $val) {
                    $parm = $key;
                    if (preg_match('/^\d+$/', $key)) {
                        $parm = ":pdooci_m$key";
                    }
                    \oci_bind_by_name($this->_stmt, $parm, $values[$key]);
                    $this->_pdooci->setError();
                }
            }
            $ok = \oci_execute($this->_stmt, $auto);
            if (!$ok) {
                $this->_pdooci->setError($this->_stmt);
                throw new \PDOException($this->_pdooci->errorInfo[2]);
            }
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        } finally {
            restore_error_handler();
        }
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
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        } finally {
            restore_error_handler();
        }
        return $rows;
    }

    /**
     * Close the current cursor
     *
     * @return null
     */
    public function closeCursor()
    {
        set_error_handler(array($this->_pdooci,"errorHandler"));
        try {
            \oci_free_statement($this->_stmt);
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        } finally {
            restore_error_handler();
        }
        $this->_stmt = null;
    }

    /**
     * Fetch a value
     *
     * @param int $style to fetch values
     *
     * @return mixed
     */
    public function fetch($style=null)
    {
        set_error_handler(array($this->_pdooci,"errorHandler"));
        try {
            $style = !$style ? \PDO::FETCH_BOTH : $style;
            $this->_fetch_sty = $style;
            $rst   = null;

            switch ($style) 
            {
            case \PDO::FETCH_BOTH:
                $rst = \oci_fetch_array($this->_stmt, \OCI_BOTH);
                break;
            case \PDO::FETCH_ASSOC:
                $rst = \oci_fetch_array($this->_stmt, \OCI_ASSOC);
                break;
            case \PDO::FETCH_NUM:
                $rst = \oci_fetch_array($this->_stmt, \OCI_NUM);
                break;
            }
            $this->_current = $rst;
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        } finally {
            restore_error_handler();
        }
        return $rst;
    }

    /**
     * Convert a query to use bind marks
     *
     * @param string $query to insert bind marks
     *
     * @return query with bind marks
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
}
?>
