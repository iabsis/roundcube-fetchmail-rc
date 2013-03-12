<?php
/**
 * fetchMailRcList is a list of fetchMailRc objects
 * 
 * @author Gilles Hemmerlé (iabsis) <giloux@gmail.com> 
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl.html
 */

require_once dirname(__FILE__) . "/fetchMailRc.php";

class fetchMailRcList implements Iterator {
    
    /**
     * Cursor position
     * @var int 
     */
    private $position = 0;
    
    /**
     *
     * @var fetchMailRc[]  List of fetchMailRc
     */
    private $datas = array();
    
    /**
     * Instance of the database manager
     * @var rcube_mdb2
     */
    private $dbm;


    /**
     * Get all the fetchmail account configured (can specify a user id to retrieve only account for a single user)
     * @param MDB2 $dbm database connection
     * @param int $user_id
     */
    public function __construct($dbm, $user_id=-1) {
        $this->dbm = $dbm;
        $this->position = 0;
        // Retrieve the accounts list
        if($user_id != -1) {
            $sql_result = $this->dbm->query(
                "SELECT * FROM " . get_table_name('fetchmail_rc') . " WHERE fk_user=?",
                $user_id
            );
        }
        else {
            $sql_result = $this->dbm->query(
                "SELECT * FROM " . get_table_name('fetchmail_rc')
            );
        }
        
        while ($account = $this->dbm->fetch_assoc($sql_result)) {
            $fetchmailRc = new fetchMailRc();
            $fetchmailRc->from_array($account);
            $this->datas[] = $fetchmailRc;
        }
        
    }

    /**
     * Return current value
     * @return mixed
     */
    public function current() {
        return $this->datas[$this->position];
    }

    /**
     * Return current key
     * @return int
     */
    public function key() {
        return $this->position;
    }

    /**
     * Increment the cursor position
     */
    public function next() {
        ++$this->position;
    }

    /**
     * Set the cursor on the first element
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * Check if row exists
     * @return bool yes if exists, false else
     */
    public function valid() {
        return isset($this->datas[$this->position]);
    }
}