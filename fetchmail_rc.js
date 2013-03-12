
if (window.rcmail) {
    // Function to show a message and avoid user interaciton
    var showWaiting = function(msg) {
        var $loader = $("#fetchmail_rc_loader");
        $loader.css({
            width: $(document).outerWidth(),
            height: $(document).outerHeight()
        });
        $loader.find(".message").html(msg);
        
        // Place the message on the middle of the windows
        var $msg_box = $loader.find(".message");
        $msg_box.css({
           left :  ($(document).outerWidth() / 2) - ($msg_box.outerWidth() / 2),
           top :  ($(document).outerHeight() / 2) - ($msg_box.outerHeight() / 2)
        });
        
        $loader.fadeIn();
    };

    // Function to hide the message
    var hideWaiting = function () {
        var $loader = $("#fetchmail_rc_loader");
        $loader.fadeOut();
    };
  
  // This function will test all fields of the form and return true if all ok, false else
  var valid_fetchmail_rc_form = function() {
      if(!$("#_mail_host").val()) {
          alert(rcmail.gettext('fetchmail_rc.fill_server_address'));
          $("#_mail_host").focus();
          return false;
      }
      
      if(!$("#_mail_username").val()) {
          alert(rcmail.gettext('fetchmail_rc.fill_username'));
          $("#_mail_username").focus();
          return false;
      }
      return true;
  };

    
  rcmail.addEventListener('init', function(evt) {
      // Generate the root menu
      var tab = $('<span>').attr('id', 'settingstabpluginfetchmailrc').addClass('tablink filter');  
      var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.fetchmail_rc')
          .attr('title', rcmail.gettext('fetchmail_rc.manageaccounts'))
          .html(rcmail.gettext('fetchmail_rc.accounts'))
          .appendTo(tab);
    // Add the generated menu to the menu
      rcmail.add_element(tab, 'tabs');
      
      // Set the plugin root menu class selected if selected.
      if (rcmail.env.action === 'plugin.fetchmail_rc') {          
          tab.addClass("selected");
      }
      
      // Open the url in the frame if exist, in a new windows else
      var openFrame = function(path) {
          var add_url = '';
          var target = window;
          if (rcmail.env.contentframe && window.frames && window.frames[rcmail.env.contentframe]) {              
             add_url = '&_framed=1';
             target = window.frames[rcmail.env.contentframe];
             rcube_find_object(rcmail.env.contentframe).style.visibility = 'inherit';
             if(path === undefined || path === '') {
                   target.location.href = 'skins/larry/watermark.html';
                   return;
              }
          }
          target.location.href = path+ add_url;
      };
      
      // Show form add / edit command
      rcmail.register_command('plugin.fetchmail_rc.add', function() {
         openFrame( rcmail.env.comm_path+'&_action=plugin.fetchmail_rc.add');
      }, true);      
      
      // Validation formulaire
      rcmail.register_command('plugin.fetchmail_rc.save', function() {
        if(!valid_fetchmail_rc_form()) return;
        var params = $("#fetchmail_rc_form").serialize();
	rcmail.http_post('plugin.fetchmail_rc.save', params);            
      }, true);
      
      // Deletion command
      rcmail.register_command('plugin.fetchmail_rc.delete', function() {         
         var selected = $('#fetchmail-rc-table tbody').find(".selected").get(0);
         var regexp = /rcmrow([0-9]+)/;
         var arr = regexp.exec(selected.id);                  
         var id = arr[1];
         var account_name = $(selected).find("td").html();
         
         if(confirm("Voulez vous vraiment supprimer le compte '" + account_name + "' ?")) {
             var params = {_fetchmail_rc_id: id};
             rcmail.http_post('plugin.fetchmail_rc.delete', params);
             
         }
      }, false);
      
      //Test account command
      rcmail.register_command('plugin.fetchmail_rc.test_account', function() {
        if(!valid_fetchmail_rc_form()) return;
        showWaiting(rcmail.gettext('fetchmail_rc.please_wait'));
        var params = $("#fetchmail_rc_form").serialize();
	rcmail.http_post('plugin.fetchmail_rc.test_account', params);      
      }, true);
      
      //Test account command
      rcmail.register_command('plugin.fetchmail_rc.forceretrieve', function() {
        showWaiting(rcmail.gettext('fetchmail_rc.please_wait'));
        var params = $("#fetchmail_rc_form").serialize();
	rcmail.http_post('plugin.fetchmail_rc.forceretrieve', params);
      }, true);   
      
      
      // Event fired when ajax save process complete succesfully
      rcmail.addEventListener('plugin.save_success', function(vars) {
          // Mise à jour de l'id dans le formulaire
          
          $("#_fetchmail_rc_id").val(vars.id);
          // Ajout ou mise à jour dans la liste des comptes
          if(vars.type === "add") {
              // Added
              // Add the line to the menu
              var $tr = $('<tr id="rcmrow' + vars.id + '"><td>' + vars.new_label + '</td></tr>');
              $("#fetchmail-rc-table", window.parent.document).append($tr);
              // Delete the row "no datas"
              $("#fetchmail-rc-table", window.parent.document).find("tbody tr").each(function() {
                  if($(this).attr('id') === undefined) {
                      $(this).remove();
                  }
              });
              // Activate the links on the menu
              window.parent.rcmail.fetchmail_rc_add_edit_links();
              $tr.click();
              
              rcmail.display_message(rcmail.gettext('fetchmail_rc.new_account_saved'), "confirmation");
          }
          else {
              // Edited
              $("#rcmrow"+vars.id, window.parent.document).find("td").html(vars.new_label);              
              rcmail.display_message(rcmail.gettext('fetchmail_rc.account_updated'), "confirmation");
          }
      });
      
      // Event fired when ajax save process threws errors.
      rcmail.addEventListener('plugin.save_error', function(vars) {
          rcmail.display_message(rcmail.gettext('fetchmail_rc.save_error'), "error");  
      });
      
      // Event fired when an error occurs during deletion
      rcmail.addEventListener('plugin.delete_error', function(vars) {
          rcmail.display_message(rcmail.gettext('fetchmail_rc.delete_error'), "error");          
      });
      
      // Event fired when an item has been successfully deleted
      rcmail.addEventListener('plugin.delete_success', function(vars) {
          // Show confirmation message
          rcmail.display_message(rcmail.gettext('fetchmail_rc.delete_success'), "confirmation");  
          // Close the frame
          openFrame();
          // Disable delete button
          rcmail.enable_command('plugin.fetchmail_rc.delete', false);
          // Suppression de l'entrée dans le menu
          $("#rcmrow"+vars.id, window.parent.document).remove();
          
          if(!$("#fetchmail-rc-table", window.parent.document).find("tbody tr").length) {
              $("#fetchmail-rc-table", window.parent.document).find("tbody").append('<tr><td colspan="2">' + rcmail.gettext('fetchmail_rc.noaccounts') + '</td></tr>');
          }
          
      });
      
      // Event fired when retrieve / test has finished
      rcmail.addEventListener('plugin.retrieve_account_finished', function(vars) {
            hideWaiting();
            console.log(vars);
            if(vars.error !== undefined) {
                rcmail.display_message(rcmail.gettext('fetchmail_rc.error_during_process')  + ' :<br />\n' + vars.error, "error");
            }
            else {
                switch (vars.type) {
                    case "test" :
                        rcmail.display_message(vars.success_message, "confirmation");
                        break;
                    case "retrieve" :
                        rcmail.display_message(vars.success_message, "confirmation");
                        break;
                    default :
                        rcmail.display_message(rcmail.gettext('fetchmail_rc.unknown_action_type'), "error");
                        break;
                }
            }
      });
      
      
      
      
      
      // Function to add the links on the list links
      rcmail.fetchmail_rc_add_edit_links = function () {
          $('#fetchmail-rc-table tr').each(function() {
              // Only manage the good tr's
              var regexp = /rcmrow([0-9]+)/;
              if($(this).attr('id') === undefined || !regexp.test($(this).attr('id'))) return;            
              
              $(this).unbind('click').bind('click', function() {
                  // Enable the deletion ability when a link is clicked
                  rcmail.enable_command('plugin.fetchmail_rc.delete', true);
              
                  $('#fetchmail-rc-table tr').each(function() { $(this).removeClass("selected focused");});
                  var arr = regexp.exec($(this).attr('id'));                  
                  var id = arr[1];
                  
                  openFrame( rcmail.env.comm_path+'&_action=plugin.fetchmail_rc.add&_fetchmail_rc_id=' + id);     
                  $(this).addClass("selected focused");
              });
              
          });
      };
      
      rcmail.fetchmail_rc_add_edit_links();
      
  });
}


