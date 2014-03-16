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
    private $_con       = null;
    private $_statement = null;
    private $_stmt      = null;

    /**
     * Constructor
     *
     * @param resource $con       database connection
     * @param string   $statement sql statement
     *
     * @return PDOOCI\Statement $statement created
     */
    public function __construct($con, $statement)
    {
        try {
            $this->_con       = $con;
            $this->_statement = $statement;
            $this->_stmt      = oci_parse($con, $statement);
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        }
    }

    /**
     * Execute statement
     *
     * @return this object
     */
    public function execute()
    {
        try {
            oci_execute($this->_stmt);
        } catch (Exception $e) {
            throw new \PDOException($e->getMessage());
        }
        return $this;
    }

    /**
     * Get the number of affected rows
     *
     * @return int number of rows
     */
    public function rowCount()
    {
        return oci_num_rows($this->_stmt);
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
