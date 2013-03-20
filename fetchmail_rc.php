<?php

/**
 * fetchmail_rc is a plugin that allows users 
 * to check some differents accounts directly in their roudcube application
 * 
 * Think to add the 'fetchmail_rc' in the $rcmail_config['plugins'] array on the main.inc.php file
 * in order to activate this plugin
 *
 * @author Gilles Hemmerlé (iabsis) <giloux@gmail.com> 
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl.html
 */

class fetchmail_rc extends rcube_plugin {
    /**
     * @var string $task
     */
    public $task = 'settings';
    
    /**
     * @var string Current action
     */
    private $action = "";
    /**
     *
     * @var rcmail Rcmail object
     */
    private $rcmail = null;
    
    /**
     * Launched during the plugin initialization (like a constructor)
     */
    public function init(){     
        // Save the rcmail object on the current one
        $this->rcmail = rcmail::get_instance();
        // Helper for the rcmail action property
        $this->action = $this->rcmail->action;
        
        // Add the Filter menu on the root preferences section (from JS)
        $this->add_texts('localization/', array('accounts', 'manageaccounts'));
        $this->include_script('fetchmail_rc.js');
        
        // Main action
        $this->register_action('plugin.fetchmail_rc', array($this, 'init_html')); 
        // Add action
        $this->register_action('plugin.fetchmail_rc.add', array($this, 'init_html')); 
        // Delete action
        $this->register_action('plugin.fetchmail_rc.delete', array($this, 'delete_account')); 
        // Save form
        $this->register_action('plugin.fetchmail_rc.save', array($this, 'form_submit')); 
        // Test account
        $this->register_action('plugin.fetchmail_rc.test_account', array($this, 'test_account')); 
        // Force retrieve account
        $this->register_action('plugin.fetchmail_rc.forceretrieve', array($this, 'force_retrieve_account')); 
        
        
        
    }
    
    /**
     * Initialize the plugin html
     */
    public function init_html() {   
        $this->rcmail->output->add_label('fetchmail_rc.please_wait', 'fetchmail_rc.fill_server_address', 'fetchmail_rc.fill_username');
        
        $this->api->output->add_handlers(array(
            // Show the iframe part
            'fetchmail_rc_frame' => array($this, 'fetchmail_rc_frame'),
            // Show the edit rule form
            'fetchmail_rc_form' => array($this, 'gen_form'),
            // Show the accounts list
            'fetchmail_rc_list' => array($this, 'show_accounts_list')
        ));
        
        // Start the requested action
        switch($this->action) {        
            case "plugin.fetchmail_rc.add" :
                $this->api->output->set_pagetitle($this->gettext('newaccount'));
		$this->api->output->send('fetchmail_rc.fetchmail_rc_add');
                break;
            case "plugin.fetchmail_rc" :
            default :
                $this->api->output->set_pagetitle($this->gettext('accounts'));
		$this->api->output->send('fetchmail_rc.fetchmail_rc');
                break;
        }
    }
    
    /**
     * Return the generated iframe
     * @param type $attrib
     * @return string html code
     */
    function fetchmail_rc_frame($attrib) {
        if (!$attrib['id'])
            $attrib['id'] = 'rcmprefsframe';

        $attrib['name'] = $attrib['id'];

        $this->api->output->set_env('contentframe', $attrib['name']);
        $this->api->output->set_env('blankpage', $attrib['src'] ? $this->api->output->abs_url($attrib['src']) : 'program/blank.gif');

        return html::iframe($attrib);
    }
    
    /**
     * Generate the add / edit account form
     * @param type $attrib
     * @return string html of the form
     */
    function gen_form($attrib) {
        require_once dirname(__FILE__) . '/includes/fetchMailRc.php';
        
        $fetchmail_rc_id = get_input_value('_fetchmail_rc_id', RCUBE_INPUT_GET);
        $fetchmailDatas = new fetchMailRc($fetchmail_rc_id);
        
        // Show hidden input with the id
        $hidden_id = new html_hiddenfield(array("id" => "_fetchmail_rc_id", "name" => "_fetchmail_rc_id", "value" => $fetchmail_rc_id));        

        $formToReturn .= '<form action="" id="fetchmail_rc_form" class="propform"><fieldset><legend>' . $this->gettext("configuration") . '</legend>' . $formToReturn .= $hidden_id->show();
        $formToReturn .= '<table class="propform">';
        
        // Mail host
        $field_name = "_mail_host";
        $formToReturn .= '<tr><td class="title">' . html::label($field_name, Q($this->gettext('mail_host'))) . ' <span class="required">*</span></td><td>' ;
        $input_name = new html_inputfield(array('name' => $field_name, 'id' => $field_name, 'value' => $fetchmailDatas->get_mail_host()));          
        $formToReturn .= $input_name->show() . "</td></tr>";
        
        // Mail host
        $field_name = "_mail_ssl";
        $formToReturn .= '<tr><td class="title">' . html::label($field_name, Q($this->gettext('mail_ssl'))) . '</td><td>' ;
        $input_ssl = new html_checkbox(array('name' => $field_name, 'id' => $field_name, "value" => 1));         
        $formToReturn .= $input_ssl->show($fetchmailDatas->get_mail_ssl()) . "</td></tr>";
        
        // Mail Login
        $field_name = "_mail_username";
        $formToReturn .= '<tr><td class="title">' . html::label($field_name, Q($this->gettext('mail_username'))) . ' <span class="required">*</span></td><td>' ;
        $input_username = new html_inputfield(array('name' => $field_name, 'id' => $field_name, 'value' => $fetchmailDatas->get_mail_username()));                
        $formToReturn .= $input_username->show() . "</td></tr>";       
        
        // Mail password
        $field_name = "_mail_password";
        $formToReturn .= '<tr><td class="title">' . html::label($field_name, Q($this->gettext('mail_password'))) . ' <span class="required">*</span></td><td>' ;
        $input_password = new html_passwordfield(array('name' => $field_name, 'id' => $field_name));        
        $formToReturn .= $input_password->show() . "</td></tr>";
        
        // Mail arguments
        $field_name = "_mail_arguments";
        $formToReturn .= '<tr><td class="title">' . html::label($field_name, Q($this->gettext('mail_arguments'))) . '</td><td>' ;
        $input_arguments = new html_inputfield(array('name' => $field_name, 'id' => $field_name, 'value' => $fetchmailDatas->get_mail_arguments()));         
        $formToReturn .= $input_arguments->show() . "</td></tr>";
        
        // Mail protocol
        $field_name = "_mail_protocol";
        $formToReturn .= '<tr><td class="title">' . html::label($field_name, Q($this->gettext('mail_protocol'))) . ' <span class="required">*</span></td><td>' ;
        $input_protocol = new html_select(array('name' => $field_name, 'id' => $field_name));  
        $input_protocol->add(fetchMailRc::PROTOCOL_AUTO,fetchMailRc::PROTOCOL_AUTO);
        $input_protocol->add(fetchMailRc::PROTOCOL_IMAP,fetchMailRc::PROTOCOL_IMAP);
        $input_protocol->add(fetchMailRc::PROTOCOL_POP2,fetchMailRc::PROTOCOL_POP2);
        $input_protocol->add(fetchMailRc::PROTOCOL_POP3,fetchMailRc::PROTOCOL_POP3);        
        $formToReturn .=$input_protocol->show($fetchmailDatas->get_mail_protocol()) . "</td></tr>";
        
        // Mail disabled
        $field_name = "_mail_disabled";
        $formToReturn .= '<tr><td class="title">' . html::label($field_name, Q($this->gettext('mail_disabled'))) . '</td><td>' ;
        $input_enabled = new html_checkbox(array('name' => $field_name, 'id' => $field_name, "value" => 1));  
        if($fetchmail_rc_id) {
            $formToReturn .= $input_enabled->show(!$fetchmailDatas->get_enabled()) . "</td></tr>";
        }
        else {
            $formToReturn .= $input_enabled->show(0) . "</td></tr>";
        }        
        $formToReturn .= "</table></fieldset>";
        
        // Show the test and debug block
        $formToReturn .= "<fieldset>";
        $formToReturn .= "<legend>" . $this->gettext("accountstatus") . "</legend>";
        $formToReturn .= '<table class="propform">';
        
        $formToReturn .= '<tr>';
        $formToReturn .= '<td class="title">' . $this->gettext("lastimport") . "</td>";
        $formToReturn .= "<td>" . $fetchmailDatas->get_date_last_retrieve() . "</td>";
        $formToReturn .= "</tr>";
        
        $formToReturn .= '<tr>';
        $formToReturn .= '<td class="title">' . $this->gettext("status") . "</td>";
        $statusMessage = $this->gettext("status_successful");
        if($fetchmailDatas->get_count_errors() > 0) {
            $statusMessage = "<b>" . $this->gettext("errorlabel") . "</b> : " . $fetchmailDatas->get_last_error() . "<br />";
            $statusMessage .= "<b>" . $this->gettext("nbconsecutiveserrors") . "</b> : " . $fetchmailDatas->get_count_errors();
        }
        $formToReturn .= "<td>" . ($fetchmailDatas->get_date_last_retrieve() == "0000-00-00 00:00:00" ? $this->gettext("notalreadyautoimported") : $statusMessage) . "</td>";
        $formToReturn .= "</tr>";
        
        $formToReturn .= '<tr>';
        $formToReturn .= '<td class="title">' . $this->gettext("testaccount") . '</td>';
        $formToReturn .= '<td><a href="javascript:;" onclick="return rcmail.command(\'plugin.fetchmail_rc.test_account\',\'\',this)">' . $this->gettext("testaccount") . '</a></td>';
        $formToReturn .= '</tr>';
        
        $formToReturn .= '<tr>';
        $formToReturn .= '<td class="title">' . $this->gettext("forceretrieve") . '</td>';
        $formToReturn .= '<td><a href="javascript:;" onclick="return rcmail.command(\'plugin.fetchmail_rc.forceretrieve\',\'\',this)">' . $this->gettext("forceretrieve") . '</a></td>';
        $formToReturn .= '</tr>';
        
        
        
        $formToReturn .= "</table>";
        $formToReturn .= "</fieldset>";
        
        $formToReturn .= "</form>";
        return $formToReturn;
        
    }
    
    /**
     * Start the account deletion (ajax)
     */
    function delete_account() {  
        $this->rcmail->output->add_label('fetchmail_rc.delete_success', 'fetchmail_rc.delete_error', 'fetchmail_rc.noaccounts');
        
        try {
            require_once dirname(__FILE__) . '/includes/fetchMailRc.php';
            $id = get_input_value('_fetchmail_rc_id', RCUBE_INPUT_POST);
            $fetchmailRc = new fetchMailRc($id);
            $deleted = $fetchmailRc->delete();
        } catch (Exception $e) {
            $this->rcmail->output->command('plugin.delete_error', Array("error" => $e->getMessage()));
            $this->rcmail->output->send('plugin');
        }
        
        if($deleted){                
                $this->rcmail->output->command('plugin.delete_success', Array(
                    "id" => $fetchmailRc->get_id()
                ));
                $this->rcmail->output->send('plugin');
         } else {
                $this->rcmail->output->command('plugin.delete_error', Array());
                $this->rcmail->output->send('plugin');
         }            
    }
    
    /**
     * Get the ajax result of the form and save it on the database
     */
    function form_submit() {  
        require_once dirname(__FILE__) . '/includes/fetchMailRc.php';
        
        $this->rcmail->output->add_label('fetchmail_rc.new_account_saved', 'fetchmail_rc.account_updated', 'fetchmail_rc.save_error');
        $id = get_input_value('_fetchmail_rc_id', RCUBE_INPUT_POST);            
        
        try {
            $fetchmailRc = new fetchMailRc($id);
            $fetchmailRc
                ->set_mail_host(get_input_value('_mail_host', RCUBE_INPUT_POST))
                ->set_fk_user($this->rcmail->user->data['user_id'])
                ->set_mail_username(get_input_value('_mail_username', RCUBE_INPUT_POST))
                ->set_mail_password(get_input_value('_mail_password', RCUBE_INPUT_POST))
                ->set_mail_arguments(get_input_value('_mail_arguments', RCUBE_INPUT_POST))
                ->set_mail_protocol(get_input_value('_mail_protocol', RCUBE_INPUT_POST))
                ->set_mail_enabled(!get_input_value('_mail_disabled', RCUBE_INPUT_POST))
                ->set_error('')
                ->set_count_errors(0)
                ->set_mail_ssl(get_input_value('_mail_ssl', RCUBE_INPUT_POST));

            $saved = $fetchmailRc->save();
        } catch(Exception $e) {
            $this->rcmail->output->command('plugin.save_error', Array("error" => $e->getMessage()));
            $this->rcmail->output->send('plugin');
        }
        
        
        if($saved){
                $new_label = $fetchmailRc->get_mail_host();
                if(!$fetchmailRc->get_enabled()) {
                    $new_label .= ' (' . $this->gettext('disabled') . ')';
                }
                $this->rcmail->output->command('plugin.save_success', Array(
                    "message" => $this->gettext("save_successfull"),
                    "type" => ($id ? "update" : "add"),
                    "new_label" => $new_label,
                    "id" => $fetchmailRc->get_id()
                ));
                $this->rcmail->output->send('plugin');
         } else {
                $this->rcmail->output->command('plugin.save_error', Array());
                $this->rcmail->output->send('plugin');
         }
        
    }
    
    /**
     * Generate the html of the account lists
     * @param type $attrib
     * @return type
     */
    function show_accounts_list($attrib) {
        $table = new html_table(array('id' => 'fetchmail-rc-table', 'class' => 'records-table', 'cellspacing' => '0', 'cols' => 1));
        $table->add_header(array('colspan' => 2), $this->gettext('accounts'));
        
        $accounts_list = $this->accounts_get_sorted_list();

        if (count($accounts_list) == 0) {
                $table->add(array('colspan' => '2'), rep_specialchars_output($this->gettext('noaccounts')));
        }
        else foreach($accounts_list as $account) {
            $idx =  $account['fetchmail_rc_id'];
            $table->set_row_attribs(array('id' => 'rcmrow' . $idx));

            if ($account['mail_enabled'] == 0)
                    $table->add(null, Q($account['mail_host']) . ' (' . $this->gettext('disabled') . ')');
            else
                    $table->add(null, Q($account['mail_host']) . ($account['count_error'] > 0 ? ' (' . $this->gettext('short_mail_error') . ')' : ''));

        }

        return html::tag('div', array('id' => 'fetchmail_rc-list-filters'), $table->show($attrib));
    }
    
    /**
     * Return the list of the current user accounts
     * @return array List of the user's accounts
     */
    function accounts_get_sorted_list() {
        require_once dirname(__FILE__) . '/includes/fetchMailRcList.php';
        $user_id = $this->rcmail->user->data['user_id'];
        $accounts = array();
        $accountsList = new fetchMailRcList($this->rcmail->db, $user_id);
        foreach ($accountsList as $currentAccount) {
            /* @var $currentAccount fetchMailRc */            
            $accounts[] = $currentAccount->getArray();
        }
        return $accounts;
    }
    
    function force_retrieve_account() {
        return $this->start_account_reception(false);
    }
    
    function test_account() {
        return $this->start_account_reception(true);
    }
    
    private function start_account_reception($test_mode = true) {
        require_once dirname(__FILE__) . '/includes/fetchMailRc.php';
        
        $this->rcmail->output->add_label('fetchmail_rc.error_during_process');
        $id = get_input_value('_fetchmail_rc_id', RCUBE_INPUT_POST);            
        
        try {
            $fetchmailRc = new fetchMailRc($id);
            $fetchmailRc
                ->set_fk_user($this->rcmail->user->data['user_id'])
                ->set_mail_host(get_input_value('_mail_host', RCUBE_INPUT_POST))
                ->set_mail_username(get_input_value('_mail_username', RCUBE_INPUT_POST))
                ->set_mail_password(get_input_value('_mail_password', RCUBE_INPUT_POST))
                ->set_mail_arguments(get_input_value('_mail_arguments', RCUBE_INPUT_POST))
                ->set_mail_protocol(get_input_value('_mail_protocol', RCUBE_INPUT_POST))
                ->set_mail_ssl(get_input_value('_mail_ssl', RCUBE_INPUT_POST));
            
            if($test_mode == true) {
                $retrieved = $fetchmailRc->test_account();
            }
            else {
                $retrieved = $fetchmailRc->retrieve_mails();
            }

        } catch(Exception $e) {
            $this->rcmail->output->command('plugin.retrieve_account_finished', Array("error" => $e->getMessage()));
            $this->rcmail->output->send('plugin');
        }
        
        $vars = array();
        if(!$retrieved) $vars['error'] = $this->gettext ("unknown_error_during_retrieve");
        $vars['type'] = $test_mode ? "test" : "retrieve";
        
        if($retrieved) $vars['success_message'] = $test_mode ? $this->gettext("test_account_success") : $this->gettext("retrieve_account_success") ;
        
        $this->rcmail->output->command('plugin.retrieve_account_finished', $vars);
        $this->rcmail->output->send('plugin');
    }
    
    
    
}