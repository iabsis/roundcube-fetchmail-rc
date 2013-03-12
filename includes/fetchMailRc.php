<?php

/**
 * fetchMailRc object allow you to persist and retrieve datas in/from the database.
 * It allows to retrieve mails and save thems in the local server
 * 
 * All setXXX methods return instance of itself to allow you to chain the sequences
 * 
 * Example:
 * 
 * <code>
 * // Instanciate object and directly get the row with id 2
 * $fetchMailRc = new fetchMailRc(2);
 * 
 * // Set new values and save the row
 * $fetchMailRc
 *   ->increment_count_errors()
 *   ->set_error("Your error message")
 *   ->save();
 * </code>
 * 
 * @author Gilles Hemmerlé (iabsis) <giloux@gmail.com> 
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl.html
 */

class fetchMailRc {
    
    const PROTOCOL_AUTO = "AUTO";
    const PROTOCOL_POP2 = "POP2";
    const PROTOCOL_POP3 = "POP3";
    const PROTOCOL_IMAP = "IMAP";    
    
    private $id;
    private $fk_user;
    private $mail_host;
    private $mail_username;
    private $mail_password;
    private $mail_enabled;
    private $mail_arguments;
    private $mail_ssl;
    private $mail_protocol;
    private $mail_date_last_retrieve;
    private $count_error;
    private $last_error;
    
    private $rcmail;
    private $ignore_security;

    /**
     * Instantiate the fetchMailRc and get the fetchmail data of the passed id
     * @param type $fetch_mail_id
     */
    function __construct($fetch_mail_id=null) {
        $this->rcmail = rcmail::get_instance();
        $this->id = $fetch_mail_id;
        
        $this->reset_fields();
        
        if($fetch_mail_id > 0) {
            $this->retrieve_account_conf();
        }
    }
    
    /**
     * Enable or disable the security check when persisting datas
     * @param bool $ignore True to ignore security, false else
     * @return \fetchMailRc
     */
    function ignore_authenticated_user_security($ignore) {
        $this->ignore_security = $ignore;
        return $this;
    }
    
    /**
     * Populate the current object with data provided by an array
     * @param array $account
     */
    public function from_array($account) {
        $this->reset_fields();
        $this->id = $account['fetchmail_rc_id'];        
        $this
            ->set_count_errors($account['count_error'])
            ->set_error($account['label_error'])
            ->set_fk_user($account['fk_user'])
            ->set_last_retrieve($account['mail_date_last_retrieve'])
            ->set_mail_arguments($account['mail_arguments'])
            ->set_mail_enabled($account['mail_enabled'])
            ->set_mail_host($account['mail_host'])
            ->set_mail_password($account['mail_password'])
            ->set_mail_protocol($account['mail_protocol'])
            ->set_mail_ssl($account['mail_ssl'])
            ->set_mail_username($account['mail_username']);
    }
    
    /**
     * Initialize object values
     */
    private function reset_fields() {
        $this->fk_user = null;
        $this->mail_host = "";
        $this->mail_username = "";
        $this->mail_password = "";
        $this->mail_enabled = "";
        $this->mail_arguments = "";
        $this->mail_ssl = "";
        $this->mail_protocol = "";
        $this->mail_date_last_retrieve = "";
        $this->count_error = "";
        $this->last_error = "";
    }
    
    /**
     * Get a new fetchmail account and refresh all datas
     * @param type $fetch_mail_id
     */
    function refresh($fetch_mail_id) {
        $this->id = $fetch_mail_id;
        $this->retrieve_account_conf();
    }
    
    /**
     * Get a fetchmail account
     * @throws Exception If someone try to get a fetchmail from another owner or if the fetchmail aacount does not exist
     */
    private function retrieve_account_conf() {
        $user_id = $this->rcmail->user->data['user_id'];
        $sql_result = $this->rcmail->db->query(
            "SELECT * FROM " . get_table_name('fetchmail_rc') . " WHERE fetchmail_rc_id=?",
            $this->id           
        );
        $account_data = $this->rcmail->db->fetch_assoc($sql_result);
        
        // If someone try to access an accounts which he is not the owner
        if(!$this->ignore_security && is_array($account_data) && $user_id != $account_data['fk_user']) {
            throw new Exception("Compte inconnu");
        }
        
        // If error occured during retrieving
        if(!is_array($account_data) && $this->id > 0) {
            throw new Exception("Compte inconnu");
        }
        
        $this->reset_fields();
        
        if(is_array($account_data)) {
            $this->id = $account_data['fetchmail_rc_id'];
            $this->fk_user = $account_data['fk_user'];
            $this->mail_host = $account_data['mail_host'];
            $this->mail_username = $account_data['mail_username'];
            $this->mail_password = $account_data['mail_password'];
            $this->mail_enabled = $account_data['mail_enabled'];
            $this->mail_arguments = $account_data['mail_arguments'];
            $this->mail_ssl = $account_data['mail_ssl'];
            $this->mail_protocol = $account_data['mail_protocol'];
            $this->mail_date_last_retrieve = $account_data['mail_date_last_retrieve'];
            $this->count_error = $account_data['count_error'];
            $this->last_error = $account_data['label_error'];
        }   
    }
    
    /**
     * Save current state of the fetch mail account into the database
     */
    public function save() {
        if(!$this->ignore_security && $this->fk_user != $this->rcmail->user->data['user_id']) {
            throw new Exception("Compte inconnu");
        }
        
        $sql_query = "REPLACE INTO " . get_table_name('fetchmail_rc') . " (
                     fetchmail_rc_id,
                     fk_user,
                     mail_host,
                     mail_username,
                     mail_password,
                     mail_enabled,
                     mail_arguments,
                     mail_ssl,
                     mail_protocol,
                     mail_date_last_retrieve,
                     count_error,
                     label_error
                 ) VALUES  (
                     ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                 )";
        
        try {
             $this->rcmail->db->query(
                $sql_query,
                ((int)$this->id > 0 ? $this->id : ''),
                $this->fk_user,
                $this->mail_host,
                $this->mail_username,
                $this->mail_password,
                $this->mail_enabled,
                $this->mail_arguments,
                $this->mail_ssl,
                $this->mail_protocol,
                $this->mail_date_last_retrieve,
                $this->count_error,
                $this->last_error
            );
            if($this->rcmail->db->is_error()) throw new Exception("SQL Error");
        }
        catch(Exception $e) {
            throw $e;
        }
        
        // If insert, update the current object and set the correct ID
        $id = $this->rcmail->db->insert_id();
        if($id != 0) $this->id = $id;
        return true;
    }
    
    /**
     * Delete the current account from the database
     * @throws Exception If error appears during deletion process
     * @return bool True if delation has succeded
     */
    function delete() {
        $sql_query = "DELETE FROM " . get_table_name('fetchmail_rc') . " 
                      WHERE fk_user=? AND fetchmail_rc_id=? ";
        
        try {
             $this->rcmail->db->query(
                $sql_query,
                $this->rcmail->user->data['user_id'],
                $this->id
            );
            if($this->rcmail->db->is_error()) throw new Exception("SQL Error");
        }
        catch(Exception $e) {
            throw $e;
        }
        
        return true;
    }
    
    /**
     * Start the fetchmail script in test mode
     */
    function test_account() {
        return $this->start_procmail(true);
    }
    
    /**
     * Start the fetchmail script and retrive mails on server
     */
    function retrieve_mails() {
        return $this->start_procmail(false);
    }
    
    /**
     * Start the fetchmail script
     * @param bool $test_mode True to use the test mode, false else
     * @throws Exception if the fetchmail command give back an error code
     */
    private function start_procmail($test_mode=true) {
        $sql_result = $this->rcmail->db->query(
            "SELECT * FROM " . get_table_name('users') . " WHERE user_id=?",
            $this->fk_user
        );
        $user = $this->rcmail->db->fetch_assoc($sql_result);
        
        if(empty($user)) throw new Exception($this->rcmail->gettext('fetchmail_rc.error_during_user_retrieving'));
        
        // Generate command
        $command = sprintf(
            'echo "poll %s with protocol %s user %s password %s" is %s %s | LANG="' . $user['language'] . '.utf8" fetchmail %s -t 10 --pidfile /tmp/fetchmail_rc-%s.pid -f - 2>&1',
            $this->mail_host,
            $this->mail_protocol,
            $this->mail_username,
            $this->mail_password,
            $user['username'],
            ($this->mail_ssl == 1 ? 'ssl' : ''),
            ($test_mode ? '--check' : ''),
            $user['username']
        );
        // Launch command
        $output_lines = $returned_code = null;
        exec($command, $output_lines, $returned_code);

        // Throw errors if necessary
        switch ($returned_code) {
            case "0" : case "1" :
                break;
            case "3" :
                throw new Exception($this->rcmail->gettext('fetchmail_rc.authenticaction_error'));
                break;
            default : 
                throw new Exception(implode("\n", $output_lines));
                break;
        }        
        // Method return true if success.
        return true;        
    }
    
    /**
     * Return the object in array format
     * @return array
     */
    public function getArray() {
        return array(
            'fetchmail_rc_id' => $this->id,
            'fk_user' => $this->fk_user,
            'mail_host' => $this->mail_host,
            'mail_username' => $this->mail_username,
            'mail_password' => $this->mail_password,
            'mail_enabled' => $this->mail_enabled,
            'mail_arguments' => $this->mail_arguments,
            'mail_ssl' => $this->mail_ssl,
            'mail_protocol' => $this->mail_protocol,
            'mail_date_last_retrieve' => $this->mail_date_last_retrieve,
            'count_error' => $this->count_error,
            'label_error' => $this->last_error
        );
    }
    
    /**
     * Get the identifier
     * @return int user identifier
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Get the user identifier
     * @return int user identifier
     */
    public function get_fk_user (){
        return $this->fk_user;
    }
    
    /**
     * Set the owner
     * @param type $user_id
     * @return self
     */
    public function set_fk_user($user_id) {
        $this->fk_user = $user_id;
        return $this;
    }
    
    /**
     * Get the mail host
     * @return string mail host
     */
    public function get_mail_host (){
        return $this->mail_host;
    }
    
    /**
     * 
     * @param type $mail_host
     * @return \fetchMailRc
     */
    public function set_mail_host($mail_host) {
        $this->mail_host = $mail_host;
        return $this;
    }
    
    /**
     * Return username of the mailbox
     * @return string
     */
    public function get_mail_username (){
        return $this->mail_username;
    }
    
    /**
     * Set the username of the mailbox
     * @param type $mail_username
     * @return \fetchMailRc
     */
    public function set_mail_username($mail_username) {
        $this->mail_username = $mail_username;
        return $this;
    }

    /**
     * Get the password of the mailbox
     * @return string
     */
    public function get_mail_password (){
        return $this->mail_password;
    }
    
    /**
     * Set the mailbox password
     * @param type $mail_password
     * @return \fetchMailRc
     */
    public function set_mail_password($mail_password) {
        if($mail_password) $this->mail_password = $mail_password;
        return $this;
    }
    
    /**
     * Get the state of the account
     * @return bool
     */
    public function get_enabled (){
        return $this->mail_enabled;
    }

    /**
     * Set the state of the mailbox
     * @param bool $enabled
     * @return \fetchMailRc
     */
    public function set_mail_enabled($enabled) {
        $this->mail_enabled = (bool)$enabled;
        return $this;
    }
    
    /**
     * Get the customs arguments to add to the command
     * @return string
     */
    public function get_mail_arguments (){
        return $this->mail_arguments;
    }
    
    /**
     * Set the customs arguments to add to the command
     * @param string $mail_arguments
     * @return \fetchMailRc
     */
    public function set_mail_arguments($mail_arguments) {
        $this->mail_arguments = $mail_arguments;
        return $this;
    }
    
    /**
     * Return the ssl configuration of the mailbox
     * @return bool
     */
    public function get_mail_ssl (){
        return $this->mail_ssl;
    }
    
    /**
     * Set  the ssl configuration of the mailbox
     * @param bool $mail_ssl
     * @return \fetchMailRc
     */
    public function set_mail_ssl($mail_ssl) {
        $this->mail_ssl = (bool)$mail_ssl;
        return $this;
    }
    
    /**
     * Get the protocol to retrieve the mailbox
     * @return string
     */
    public function get_mail_protocol (){
        return $this->mail_protocol;
    }
    
    /**
     * Set the protocol to retrieve the mailbox
     * @param string $mail_protocol
     * @return \fetchMailRc
     */
    public function set_mail_protocol($mail_protocol) {
        $this->mail_protocol = $mail_protocol;
        return $this;
    }
    
    /**
     * Get the last retrieve date
     * @return string yyyy-mm-dd H:i:s
     */
    public function get_date_last_retrieve (){
        return $this->mail_date_last_retrieve;
    }
    
    /**
     * Set the date and time of the last retrieve event
     * @param string $last_retrieve Date and time of the last retrieve with format : yyyy-mm-dd H:i:s
     * @return \fetchMailRc
     */
    public function set_last_retrieve($last_retrieve) {
        $this->mail_date_last_retrieve = $last_retrieve;
        return $this;
    }
    
    /**
     * Get number of consecutives errors which happened during synchronization process
     * @return type
     */
    public function get_count_errors (){
        return $this->count_error;
    }
    
    /**
     * Set manually the number of consecutives errors  which happened during synchronization process
     * @param int $nb_errors
     * @return \fetchMailRc
     */
    public function set_count_errors($nb_errors) {
        $this->count_error = $nb_errors;
        return $this;
    }
    
    /**
     * Increment the number of consecutives errors  which happened during synchronization process
     * @return \fetchMailRc
     */
    public function increment_count_errors() {
        $this->count_error++;
        return $this;
    }
    
    /**
     * Get the last error message
     * @return string
     */
    public function get_last_error (){
        return $this->last_error;
    }
    
    /**
     * Set the message error that happened during import process
     * @param string $error
     * @return \fetchMailRc
     */
    public function set_error($error) {
        $this->last_error = $error;
        return $this;
    }

}
