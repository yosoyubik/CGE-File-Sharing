/* USER MANAGER SCRIPTS
 * This script contains function which enables user management.
 * Eg. create_user(uid, pwd, email, captcha): Creation of a new user
 *     update_user(uid, pwd, email): Update/changing of user settings; email, password etc.
 *     delete_user(): Deletion of a user and all of their user data, including isolate data
 *     get_user_details(): Retreive the user details from the database
 */
function get_data_analysis(uid, pwd, email, captcha){
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
			url : "https://cge.cbs.dtu.dk/cge/user/php/get_data_analysis.php",
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
