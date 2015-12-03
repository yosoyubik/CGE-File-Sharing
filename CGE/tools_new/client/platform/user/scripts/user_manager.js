/* USER MANAGER SCRIPTS
 * This script contains function which enables user management.
 * Eg. create_user(uid, pwd, email, captcha): Creation of a new user
 *     update_user(uid, pwd, email): Update/changing of user settings; email, password etc.
 *     delete_user(): Deletion of a user and all of their user data, including isolate data
 *     get_user_details(): Retreive the user details from the database
 */
function create_user(uid, pwd, email, captcha){
   /* Creation of a new user
    */
   // VALIDATE FORM INPUTS
   if(!check_name(uid) | !check_email(email) | !check_password()){
      return false;
   }
   
   // SEND REQUEST FOR USER CREATION
	if(uid != '' & pwd != '' & email != '' & captcha != ''){
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/login/php/create_user.php",
			data : {
				USERNAME : uid,
				PASSWORD : pwd,
            EMAIL    : email,
            CAPTCHA  : captcha
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE PHP SCRIPT
					if (xml.STATUS == 'BADUSER') {
						alert("Username was invalid!");
					}else if (xml.STATUS == 'BADEMAIL') {
						alert("Email was invalid!");
					}else if (xml.STATUS == 'BADIMAGE') {
						alert("Image interpretation was incorrect!");
					}else if (xml.STATUS == 'USERTAKEN') {
						alert("Username is in use!");
					}else if (xml.STATUS == 'EMAILTAKEN') {
						alert("Email is in use!");
                  if(confirm('Reset password?')){
                     reset_user(email); // Call reset user script on confirm
                  }
					}else{
						//console.log(xml);
						alert("Error occured during user creation: "+ xml.STATUS);
					}
				}else{
               // User created, remove creation div, and show pending div.
					show_pending('create');
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				//AN ERROR OCCURED IN THE COMMUNICATION
				alert('Communication Error: '+ errorThrown +" "+ textStatus);
			}
		});
      return true;
	}
   return false;
}


function update_user(uid, pwd, email){
   /* Update user settings
    * email, password etc.
    */
   // VALIDATE FORM INPUTS
   if(!check_name(uid) | !check_email(email) | !check_password()){
      return false;
   }
   
	if(uid != '' & pwd != '' & email != ''){
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/login/php/update_user.php",
			data : {
				USERNAME : uid,
				PASSWORD : pwd,
            EMAIL    : email,
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
						alert("Username and session id did not match!\nPlease login again.");
					}else if (xml.STATUS == 'BADUSER') {
						alert("Session username is invalid!\nPlease login.");
					}else if (xml.STATUS == 'BADSESSION') {
						alert("Session id is invalid!\nPlease login.");
					}else if (xml.STATUS == 'BADEMAIL') {
						alert("New email is invalid!");
					}else if (xml.STATUS == 'BADUSER2') {
						alert("New username is invalid!");
					}else if (xml.STATUS == 'USERTAKEN') {
						alert("New username is in use!");
					}else if (xml.STATUS == 'EMAILTAKEN') {
						alert("New email is in use!");
					}else{
						//console.log(xml);
						alert("Error occured during user update: "+ xml.STATUS);
					}
				}else{
               // User updated, update shown username, and show succes message.
					showLoggedin(uid);
					alert('Your changes was updated succesfully');
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				//AN ERROR OCCURED IN THE COMMUNICATION
				alert('Communication Error: '+ errorThrown +" "+ textStatus);
			}
		});
      return true;
	}
   return false;
}


function delete_user(){
   /* Deletion of a user and all of their user data, including isolate data
    */
   if(confirm('Are you sure you wish to delete this profile and all of it non-public data?')){
	   $.ajax({
	   	type : "POST",
	   	url : "https://cge.cbs.dtu.dk/cge/user/login/php/delete_user.php",
	   	data : {},
	   	timeout : 2000,
	   	dataType : 'xml',
	   	async : false,
	   	success : function(xml) {
	   		xml = $.xml2json(xml);
	   		if (xml.STATUS != 'ACCEPTED') {
	   			// AN ERROR OCCURED IN THE PHP SCRIPT
	   			if (xml.STATUS == 'REJECTED') {
	   				alert("Username and session id did not match!\nPlease login again.");
	   			}else if (xml.STATUS == 'BADUSER') {
	   				alert("Session username is invalid!\nPlease login.");
	   			}else if (xml.STATUS == 'BADSESSION') {
	   				alert("Session id is invalid!\nPlease login.");
	   			}else if (xml.STATUS == 'NOACTIVATION') {
	   				alert("The user is pending activation!\nTo cancel user creation, please click on the cancel link in the received email.");
	   			}else if (xml.STATUS == 'PREDELETE') {
	   				alert("The user has already been marked for deletion!\nTo complete deletion please click on the confirmation link in the received email.");
	   			}else{
	   				//console.log(xml);
	   				alert("Error occured during user deletion: "+ xml.STATUS);
	   			}
	   		}else{
               // User marked as deleted, remove creation div, and show pending div.
	   			show_pending('delete');
	   		}
	   	},
	   	error : function(jqXHR, textStatus, errorThrown) {
	   		//AN ERROR OCCURED IN THE COMMUNICATION
	   		alert('Communication Error: '+ errorThrown +" "+ textStatus);
	   	}
	   });
   }
}


function get_user_details(){
   /* Retreive the user details from the database
    */
	$.ajax({
		type : "POST",
		url : "https://cge.cbs.dtu.dk/cge/user/login/php/get_user_details.php",
		data : {},
		timeout : 2000,
		dataType : 'xml',
		async : false,
		success : function(xml) {
			xml = $.xml2json(xml);
			if (xml.STATUS != 'ACCEPTED') {
				// AN ERROR OCCURED IN THE PHP SCRIPT
				if (xml.STATUS == 'REJECTED') {
					alert("Username and session id did not match!\nPlease login again.");
				}else if (xml.STATUS == 'NOSESSION') {
					alert("You have not logged in!\nPlease login.");
				}else if (xml.STATUS == 'BADUSER') {
					alert("Session username is invalid!\nPlease login.");
				}else if (xml.STATUS == 'BADSESSION') {
					alert("Session id is invalid!\nPlease login.");
				}else if (xml.STATUS == 'TIMEOUT') {
					alert("The session has timed out!\nPlease login again.");
				}else if (xml.STATUS == 'NEWIP') {
					alert("Your IP address has changed!\nPlease login again.");
				}else{
					//console.log(xml);
					alert("Error occured during retrieval of user data: "+ xml.STATUS);
				}
			}else{
            // User data was retrieved, populate the form with the data.
				populate_user_form(xml.USERNAME, xml.SESSIONID); // username and email adress
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//AN ERROR OCCURED IN THE COMMUNICATION
			alert('Communication Error: '+ errorThrown +" "+ textStatus);
		}
	});
}


function show_pending(action){
   /* Remove create user div, and show a pending message
   */
   if(action=='create'){
      document.getElementById('create').innerHTML =
         ['<h1>Pending Activation of account</h1>',
          '<p>',
          '  The user is created, please activate it by following the confirmation link send to the provided email address.<br>',
          '</p>'].join('\n');
   }else if(action=='delete'){
      document.getElementById('edit').innerHTML =
         ['<h1>Pending Confirmation of account deletion</h1>',
          '<p>',
          '  The user is marked for deletion, please confirm this by following the confirmation link send to the registered email address.<br>',
          '</p>'].join('\n');
   }
}


function populate_user_form(name, email){
   /* Populate the form with the retrieved data
   */
   add_detail('username', name);
   add_detail('email', email);
}

function add_detail(id, value){
   /* Check the nodeName of the element and assign the new value properly
   */
   elem = document.getElementById(id)
   if(elem === null){ return; } // skipping unexisting elements
   if(elem.nodeName == 'SPAN'){
      elem.innerHTML = value;
   }else if(elem.nodeName == 'INPUT'){
      elem.value = value;
   }else{
      alert(elem.nodeName, id);
   }
}


// FORM VALIDATION FUNCTIONS
function show_security(lvl){
	var element = document.getElementById('password_strength');
	var text, color;
	if (lvl<=0) {text = 'None';color = '#000';}
	else if (lvl>=80) {text = 'Very high';color = '#0F0';}
	else if (lvl>=55) {text = 'High';color = '#3C0';}
	else if (lvl>=30) {text = 'Medium';color = '#690';}
	else if (lvl>=15) {text = 'Low';color = '#C30';}
	else if (lvl>=1) {text = 'Very low';color = '#F00';}
	else {text = 'ERROR!';};
	element.style.color = color;
	element.innerHTML = text;
}

function password_strength(){
	//This function check the security strength of the password
   var hasLowerCap = /[a-z]/;
   var hasUpperCap = /[A-Z]/;
   var hasNumber = /[0-9]/;
   var hasSpecial = /[^a-zA-Z0-9]/;
	var lvl = 0;
	var chars = new Object;
	var numChar = 0;
	var string = document.forms['profile'].elements["password"].value;
   var length = string.length;
   // Check for diversity
	if (hasLowerCap.test(string)){ lvl+=3;};
	if (hasUpperCap.test(string)){ lvl+=3;};
	if (hasNumber.test(string)){ lvl+=1;};
	if (hasSpecial.test(string)){ lvl+=3;};
   // Check for adjusted length (adjusted for repeats)
   var al = 0;
	for(var x=0, c, oc=''; x < length; x++) {
		c = string.charAt(x);
      if ( c != oc ){ // Switched char
         if(!chars.hasOwnProperty(c)){ // New char
            al++;
            chars[c] = 0;
         }else{ // Previously used char
            al += 1 / chars[c];
         }
      }else{ // Repeated char
         al += 0.5 / chars[c];
      }
      chars[c]++;
      oc = c;
	}
	show_security(lvl * al);
}

function check_password(){
	//This function check the passwords are identical
	var form = document.forms['profile'];
	var element = document.getElementById('check_password');
	if (form.elements["passwordcheck"].value != ''){
		if (form.elements["password"].value === form.elements["passwordcheck"].value){
			element.style.color = '#0F0';
			element.innerHTML = 'Correct';
			return true;
		}else{
			element.style.color = '#F00';
			element.innerHTML = 'Passwords are not identical!';
			return false;
		}
	}else{
		element.style.color = '#F00';
		element.innerHTML = 'Please provide a password!';
      return false;
   }
}

function check_name(name){
   var validUsername = /[^a-zA-Z0-9\.\-\_\@]/;
	//var name = document.forms['profile'].elements["username"].value;
	var element = document.getElementById('username_check');
	if(validUsername.test(name)){
		//Special characters not allowed in Username
		element.innerHTML = "Warning! Only word characters a-z, A-Z and numbers 0-9, and following characters . - _ @ are allowed in the Username.";
		return false;
	}else if(name.length <=0){
		//Username must be provided
		element.innerHTML = "Warning! Please add a Username.";
		return false;
	}else{
		element.innerHTML = "";
		return true;
	}
}

function check_email(email){
   var validEmail = /[^a-zA-Z0-9\.\+\-\_\@]/;
	//var email = document.forms['profile'].elements["email"].value;
	var element = document.getElementById('email_check');
	if(validEmail.test(email)){
		//Special characters not allowed in Username
		element.innerHTML = "Warning! Only word characters a-z, A-Z and numbers 0-9, and following characters . + - _ @ are allowed in the Email.";
		return false;
	}else if(email.length <=0){
		//Username must be provided
		element.innerHTML = "Warning! Please add a Email.";
		return false;
	}else{
		element.innerHTML = "";
		return true;
	}
}
