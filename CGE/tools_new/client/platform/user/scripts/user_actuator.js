/* USER ACTUATOR SCRIPTS
 * This script contains function which confirms activation/removal of user accounts etc.
 * Eg. cancel_create:  Cancel the creation of a user account
 *     confirm_create: Confirm the creation of a user account
 *     cancel_reset:   Undo reset of a user account
 *     cancel_delete:  Cancel the deletion of a user account
 *     confirm_delete: Confirm the deletion of a user account
 */
function cancel_create(uid, tmp){
   /* Cancel the creation of a user account
    */
	if(uid != '' & tmp != ''){
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/login/php/cancel_create_user.php",
			data : {
				USERNAME : uid,
				ACTIVATE : tmp
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE ACTUATOR PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
                  document.getElementById('msg').innerHTML = 'Cancellation code was incorrect!';
					}else if (xml.STATUS == 'BADUSER') {
                  document.getElementById('msg').innerHTML = 'Username is invalid!';
					}else if (xml.STATUS == 'NOUSER') {
                  document.getElementById('msg').innerHTML = 'User does not exist!';
					}else if (xml.STATUS == 'BADHASH') {
                  document.getElementById('msg').innerHTML = 'Cancellation code was invalid!';
					}else if (xml.STATUS == 'ACTIVATED') {
                  document.getElementById('msg').innerHTML = 'The user account is active!\nPlease log in through the form in the top right corner of the page.';
					}else{
						//console.log(xml);
                  document.getElementById('msg').innerHTML = 'Error occured during cancellation of user creation: '+ xml.STATUS;
					}
				}else{
               // USER CREATION WAS CANCELLED, SHOW SUCCES ALERT
					showLogin();
               window.stop_default_login = true;
               document.getElementById('msg').innerHTML = 'The user creation has been cancelled!';
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				// AN ERROR OCCURED IN THE COMMUNICATION
            document.getElementById('msg').innerHTML = 'Communication Error: '+ errorThrown +" "+ textStatus;
			}
		});
	}
}


function confirm_create(uid, tmp){
   /* Confirm the creation of a user account
    */
	if(uid != '' & tmp != ''){
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/login/php/confirm_create_user.php",
			data : {
				USERNAME : uid,
				ACTIVATE : tmp
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE ACTUATOR PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
                  document.getElementById('msg').innerHTML = 'Activation code was incorrect!';
					}else if (xml.STATUS == 'BADUSER') {
                  document.getElementById('msg').innerHTML = 'Username is invalid!';
					}else if (xml.STATUS == 'NOUSER') {
                  document.getElementById('msg').innerHTML = 'User does not exist!';
					}else if (xml.STATUS == 'BADHASH') {
                  document.getElementById('msg').innerHTML = 'Activation code was invalid!';
					}else if (xml.STATUS == 'ACTIVATED') {
                  document.getElementById('msg').innerHTML = 'The user account is active!\nPlease log in through the form in the top right corner of the page.';
					}else{
						//console.log(xml);
                  document.getElementById('msg').innerHTML = 'Error occured during confirmation of user creation: '+ xml.STATUS;
					}
				}else{
               // USER CREATION WAS CONFIRMED, SHOW SUCCES ALERT
               window.stop_default_login = true;
					showLoggedin(xml.USERNAME);
               document.getElementById('msg').innerHTML = 'The user account has been activated!';
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				// AN ERROR OCCURED IN THE COMMUNICATION
            document.getElementById('msg').innerHTML = 'Communication Error: '+ errorThrown +" "+ textStatus;
			}
		});
	}
}


function cancel_reset(uid){
   /* Undo reset of a user account
    */
	if(uid != ''){
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/login/php/cancel_reset_user.php",
			data : {
				USERNAME : uid
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE ACTUATOR PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
                  document.getElementById('msg').innerHTML = 'This user is not marked for a password reset!\nTo log in, please use log in box in the top right corner.';
					}else if (xml.STATUS == 'BADUSER') {
                  document.getElementById('msg').innerHTML = 'Username is invalid!';
					}else if (xml.STATUS == 'NOUSER') {
                  document.getElementById('msg').innerHTML = 'User does not exist!';
               }else if (xml.STATUS == 'NOACTIVATION') {
                  document.getElementById('msg').innerHTML = 'The user is pending activation!\nTo complete user creation, please click on the confirmation link in the received email.';
               }else if (xml.STATUS == 'CANCELDELETE') {
                  document.getElementById('msg').innerHTML = 'The user has been marked for deletion!\nTo cancel deletion please click on the cancellation link in the received email.';
					}else{
						//console.log(xml);
                  document.getElementById('msg').innerHTML = 'Error occured during cancellation of user reset: '+ xml.STATUS;
					}
				}else{
               // USER RESET WAS CANCELLED, SHOW SUCCES ALERT
					//showLogin();
               window.location = '/services/index.php?'+ $.param({'alert': "The password reset of the user has been cancelled, And your previous password has been restored!\nTo log in, please use log in box in the top right corner."}, true);
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				// AN ERROR OCCURED IN THE COMMUNICATION
            document.getElementById('msg').innerHTML = 'Communication Error: '+ errorThrown +" "+ textStatus;
			}
		});
	}
}


function cancel_delete(uid, tmp){
   /* Cancel the deletion of a user account
    */
	if(uid != '' & tmp != ''){
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/login/php/cancel_delete_user.php",
			data : {
				USERNAME : uid,
				ACTIVATE : tmp
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE ACTUATOR PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
                  document.getElementById('msg').innerHTML = 'Cancellation code was incorrect!';
					}else if (xml.STATUS == 'BADUSER') {
                  document.getElementById('msg').innerHTML = 'Username is invalid!';
					}else if (xml.STATUS == 'NOUSER') {
                  document.getElementById('msg').innerHTML = 'User does not exist!';
					}else if (xml.STATUS == 'BADHASH') {
                  document.getElementById('msg').innerHTML = 'Cancellation code was invalid!';
					}else if (xml.STATUS == 'DONE') {
                  document.getElementById('msg').innerHTML = 'The user is not marked for deletion!The user account is already active, please log in through the form in the top right corner of the page.';
					}else if (xml.STATUS == 'ERROR') {
                  document.getElementById('msg').innerHTML = 'The user is not marked for deletion!';
					}else{
						//console.log(xml);
                  document.getElementById('msg').innerHTML = 'Error occured during cancellation of user deletion: '+ xml.STATUS;
					}
				}else{
               // USER DELETION WAS CANCELLED, SHOW SUCCES ALERT
					//showLogin();
               window.location = '/services/index.php?'+ $.param({'alert': "The deletion of the user acoount has been cancelled!\nYou are now able to log in through the form in the top right corner of the page."}, true);
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				// AN ERROR OCCURED IN THE COMMUNICATION
            document.getElementById('msg').innerHTML = 'Communication Error: '+ errorThrown +" "+ textStatus;
			}
		});
	}
}


function confirm_delete(uid, tmp){
   /* Confirm the deletion of a user account
    */
	if(uid != '' & tmp != ''){
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/login/php/confirm_delete_user.php",
			data : {
				USERNAME : uid,
				ACTIVATE : tmp
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE ACTUATOR PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
                  document.getElementById('msg').innerHTML = 'Confirmation code was incorrect!';
					}else if (xml.STATUS == 'BADUSER') {
                  document.getElementById('msg').innerHTML = 'Username is invalid!';
					}else if (xml.STATUS == 'NOUSER') {
                  document.getElementById('msg').innerHTML = 'User does not exist!';
					}else if (xml.STATUS == 'BADHASH') {
                  document.getElementById('msg').innerHTML = 'Confirmation code was invalid!';
					}else if (xml.STATUS == 'ERROR') {
                  document.getElementById('msg').innerHTML = 'The user is not marked for deletion!';
					}else{
						//console.log(xml);
                  document.getElementById('msg').innerHTML = 'Error occured during confirmation of user deletion: '+ xml.STATUS;
					}
				}else{
               // THE USER WAS PERMANENTLY DELETED, SHOW SUCCES ALERT
					showLogin();
               window.stop_default_login = true;
               document.getElementById('msg').innerHTML = 'The user and all of its none public data has been succesfully deleted!';
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				// AN ERROR OCCURED IN THE COMMUNICATION
				document.getElementById('msg').innerHTML = 'Communication Error: '+ errorThrown +" "+ textStatus;
			}
		});
	}
}

if (!String.prototype.format) {
  String.prototype.format = function() {
    var args = arguments;
    return this.replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[number] != 'undefined'
        ? args[number]
        : match
      ;
    });
  };
}
