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
                }
            }
            $ok = \oci_execute($this->_stmt, $auto);
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
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
        return \oci_num_rows($this->_stmt);
    }

    /**
     * Close the current cursor
     *
     * @return null
     */
    public function closeCursor()
    {
        \oci_free_statement($this->_stmt);
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
        try {
            $style = !$style ? \PDO::FETCH_BOTH : $style;
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
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
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
    }

    /**
     * Return the current key/position
     *
     * @return null
     */
    public function key() 
    {
    }
    
    /**
     * Return the next value
     *
     * @return null
     */
    public function next()
    {
    }

    /**
     * Rewind
     *
     * @return null
     */
    public function rewind()
    {
    }
    
    /**
     * Check if the current value is valid
     *
     * @return null
     */
    public function valid()
    {
    }
}
?>
