#!/usr/bin/php
<?php

/**
 * This a script that should be added in your crontab. It will parse all the user's account and retrive their mails
 * into the local server with fetchmail.
 * 
 * @author Gilles Hemmerlé (iabsis) <giloux@gmail.com> 
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/gpl.html
 */

if(php_sapi_name() !== "cli") {
    die("Run only in command line");
}

## Starting roundcube environnement #####################################################
require_once dirname(__FILE__) . "/config.inc.php";
define("INSTALL_PATH", $rcmail_config['fetchmail_rc_roundcube_install_path']);
require_once dirname(__FILE__) . "/../../program/include/iniset.php";
require_once "includes/fetchMailRcList.php";

// init application, start session, init output class, etc.
$RCMAIL = rcmail::get_instance();
$RCMAIL->config->load_from_file(dirname(__FILE__) . "/config.inc.php");


## Debug management ##################################################

define("CONSOLE_DEBUG_MODE", true);
function console_show($msg) {
    if(CONSOLE_DEBUG_MODE) {
        echo $msg . "\n";
    }
}


## Parse all the accounts ###########################################
$accountsList = new fetchMailRcList($RCMAIL->db);
foreach ($accountsList as $currentAccount) {
    /* @var $currentAccount fetchMailRc */
    
    // Don't manage disabled accounts
    if(!$currentAccount->get_enabled())  continue;
    console_show("Parse account " . $currentAccount->get_mail_host());
    
    try {
        // Start mail retrieving
        $currentAccount->ignore_authenticated_user_security(true);
        if($currentAccount->retrieve_mails()) {
            // If account has been retrieved, reset errers and set the update date
            $currentAccount->set_error('')->set_count_errors(0)->set_last_retrieve(date("Y-m-d H:i:s"))->save();
        }
        console_show("Account retived !");
    } catch (Exception $exc) {
        // Show error if retrive failed              
        try {
            // Save the issue message and increment the errors count  
            $currentAccount
                ->set_error($exc->getMessage())
                ->increment_count_errors()
                ->save();
            // Notify users if maximum error number has been reached
            if($currentAccount->get_count_errors() == $RCMAIL->config->get("fetchmail_rc_errors_before_warning")) {
                console_show("Notification de l'utilisateur");
            }
            console_show("Error saved in the user's account : " . $exc->getMessage());
        } catch(Exception $e) {
            // Exceptio if the error management has failed
            console_show($e->getMessage());
        }
    }
}

console_show("Done ...");