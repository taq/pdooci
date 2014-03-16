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
class PDOOCI
{
    private $_con = null;

    /** 
     * Class constructor
     *
     * @param string $data     the connection string
     * @param string $username user name
     * @param string $password password
     * @param string $options  options to send to the connection
     *
     * @return PDOOCI object
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
     *
     * @return PDOOCIStatement
     */
    public function query($statement)
    {
        try {
            $stmt = new PDOOCIStatement($this->_con, $statement);
            return $stmt->execute();
        } catch (Exception $e) {
            throw new \PDOException($exception->getMessage());
        }
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
