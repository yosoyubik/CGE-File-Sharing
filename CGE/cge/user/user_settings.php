<?php #! /usr/bin/php5 -q
################################################################################
#                             CGE USER SETTINGS                                #
################################################################################
# This is the script which:
#   -> Allows new users to create a profile
#   -> Allows current users to edit their profile
#   -> Allows current users to delete their profile

$serviceRoot = "/srv/www/htdocs/services/";
error_reporting(E_ALL);
ini_set('error_log','/srv/www/htdocs/services/error_log');
ini_set('log_errors','true');
# STANDARD CBS PAGE TEMPLATES, always include this file
include_once('/srv/www/php-lib/cge_std.php'); // Including CGE_std clases and functions
$CGE = new CGE; // Load the Class

# CGE MENU
# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
$CGE->std_header("User Overview", "(./,'User Home'),(/services/,'Services')", FALSE); // Print the Menu

function _INPUT($name){
   // QUERY HANDLER: Used to get form elements and queries in a simple manner
   // AUTHOR: Martin Thomsen
   // USAGE: $form_text = _INPUT('form_text_name');
   if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST[$name]))
      return strip_tags($_POST[$name]);
   elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_GET[$name]))
      return strip_tags($_GET[$name]);
   else return NULL;
}

if (_INPUT("action") == "create"){
	// SHOW CREATION FORM

?>
<!-- START OF CONTENT -->
<script type='text/javascript'>
	var hasLowerCap = /[a-z]/;
	var hasUpperCap = /[A-Z]/;
	var hasNumber = /[0-9]/;
	var hasSpecial = /[^a-zA-Z0-9]/;
	var validUsername = /[^a-zA-Z0-9\.\-\_\@]/;
	var validEmail = /[^a-zA-Z0-9\.\+\-\_\@]/;
	
	function show_security(lvl){
		var element = document.getElementById('password_strength');
		var text, color;
		if (lvl<=0) {text = 'None';color = '#000';}
		else if (lvl>=17) {text = 'Very high';color = '#0F0';}
		else if (lvl>=15) {text = 'High';color = '#3C0';}
		else if (lvl>=12) {text = 'Medium';color = '#690';}
		else if (lvl>=6) {text = 'Low';color = '#C30';}
		else if (lvl>=1) {text = 'Very low';color = '#F00';}
		else {text = 'ERROR!';};
		element.style.color = color;
		element.innerHTML = text;
	}

	function password_strength(){
		//This function check the security strength of the password
		var lvl = 0;
		var chars = new Object;
		var numChar = 0;
		var string = document.forms['create_user'].elements["password"].value;
		if (hasLowerCap.test(string)){ lvl+=2;};
		if (hasUpperCap.test(string)){ lvl+=3;};
		if (hasNumber.test(string)){ lvl+=3;};
		if (hasSpecial.test(string)){ lvl+=5;};
		lvl+= parseInt(string.length/5);
		for(x = 0, length = string.length; x < length; x++) {
			var c = string.charAt(x);
			if (isNaN(chars[c])){chars[c] = 1;numChar++;};
		}
		lvl+= parseInt(numChar/3);
		show_security(lvl);
	}

	function check_password(){
		//This function check the passwords are identical
		var form = document.forms['create_user'];
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
		}else{return false;}
	}
	
	function check_name(){
		var name = document.forms['create_user'].elements["username"].value;
		var element = document.getElementById('username');
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
	
	function check_email(){
		var email = document.forms['create_user'].elements["email"].value;
		var element = document.getElementById('email');
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
function create_login(uid, pwd, email, captcha){
	$.ajax({
		type : "POST",
		url : "/cge/user/login_create.php", //https://cge.cbs.dtu.dk
		data : {
			USERNAME : uid,
			PASSWORD : pwd,
			EMAIL : email,
			CAPTCHA : captcha
		},
		timeout : 2000,
		dataType : 'xml',
		async : false,
		success : function(xml) {
			xml = $.xml2json(xml);
			if (xml.STATUS != 'ACCEPTED') {
				// AN ERROR OCCURED IN THE LOGIN PHP SCRIPT
				if (xml.STATUS == 'USERTAKEN') {
					alert("Username is taken!");
				}else if (xml.STATUS == 'BADUSER') {
					alert("Username is invalid!");
				}else if (xml.STATUS == 'BADIMAGE') {
					alert("Incorrect security code entered!");
				}else{
					console.log(xml);
					alert("Error With Login: "+ xml.STATUS);
				}
			}else{
				//Redirect to Edit User Settings
				alert('Success!\nUser '+xml.USERNAME+' created.');
				window.location = "user_settings.php";
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//AN ERROR OCCURED IN THE COMMUNICATION
			alert('Communication Error: '+ errorThrown +" "+ textStatus);
		}
	});
};
</script>
<h1>Create Profile</h1>
<form name='create_user'>
	<table>
	<tr><td>Username*:</td><td><input type='text' name='username' onchange='check_name();' style='width:110px;'></td><td><span id='username' style='font-weight: bold;'></span></td></tr>
	<tr><td>Email*:</td><td><input type='text' name='email' onchange='check_email();' style='width:110px;'></td><td><span id='email' style='font-weight: bold;'></span></td></tr>
	<tr><td>Password*:</td><td><input type='password' name='password' onkeyup='password_strength();check_password();' style='width:110px;'></td><td>Security: <span id='password_strength' style='font-weight: bold;'>None</span></td></tr>
	<tr><td>Confirm Password*:</td><td><input type='password' name='passwordcheck' onkeyup='check_password();' style='width:110px;'></td><td><span id='check_password' style='font-weight: bold;'></span></td></tr>
   </table>
	<p>
    <img id="siimage" style="border: 1px solid #000; margin-right: 15px" src="./securimage/securimage_show.php?sid=<?php echo md5(uniqid()) ?>" alt="CAPTCHA Image" align="left" />
    <object type="application/x-shockwave-flash" data="./securimage/securimage_play.swf?bgcol=#ffffff&amp;icon_file=./securimage/images/audio_icon.png&amp;audio_file=./securimage/securimage_play.php" height="32" width="32">
      <param name="movie" value="./securimage/securimage_play.swf?bgcol=#ffffff&amp;icon_file=./securimage/images/audio_icon.png&amp;audio_file=./securimage/securimage_play.php" />
    </object>
    &nbsp;
    <a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" onclick="document.getElementById('siimage').src = './securimage/securimage_show.php?sid=' + Math.random(); this.blur(); return false"><img src="./securimage/images/refresh.png" alt="Reload Image" height="32" width="32" onclick="this.blur()" align="bottom" border="0" /></a><br />
    <strong>Enter Code*:</strong><br />
    <input type="text" name="captcha" size="12" maxlength="8" />
   </p>
    <input type='button' value='Create User' onclick="check_name();check_email();check_password();create_login(this.form.username.value, this.form.password.value, this.form.email.value, this.form.captcha.value)">
</form>
<!-- END OF CONTENT -->
<?php

}elseif(_INPUT("action") == "edit"){
	// SHOW EDIT FORM AND DELETE BUTTON
	
	?>
	<h1>Edit Profile</h1>
	<h3>Login information: <?php $CGE->showhide("info_login"); ?></h3>
	
	<form name='change_user_info'>
		<p id='info_login' class="hide" style="display:None;">
			Change username to: <input type='text' name='username' onchange='check_name();'> <span id='username' style='font-weight: bold;'></span><br>
			Change password to: <input type='password' name='password' onkeyup='password_strength();check_password();'> Security: <span id='password_strength' style='font-weight: bold;'>None</span><br>
			Confirm password: <input type='password' name='passwordcheck' onkeyup='check_password();'> <span id='check_password' style='font-weight: bold;'></span><br>
			<input type='button' value='Save changes to login information' onclick="cremate_login(this.form.username.value, this.form.password.value, this.form.captcha.value)"><br>
		</p>
	</form>
	<h3>User data: <?php $CGE->showhide("info_userdata"); ?></h3>
	<form name='change_user_info'>
		<p id='info_userdata' class="hide">
			Edit name: <input type='text' name='name'><br>
			Edit company/institution: <input type='text' name='company'><br>
			Edit email: <input type='text' name='email'><br>
			<input type='button' value='Save changes to user information' onclick="cremate_login(this.form.username.value, this.form.password.value, this.form.captcha.value)"><br>
		</p>
	</form>
	<h3>Defaults for service runs: <?php $CGE->showhide("info_defaults"); ?></h3>
	
	<form name='change_user_info'>
		<p id='info_defaults' class="hide">
			Pathogenicity: <input type='text' name='p'><br>
			Location: <input type='text' name='l'><br>
			Origin: <input type='text' name='o'><br>
			Time: <input type='text' name='t'><br>
			<input type='button' value='Save changes to user information' onclick="cremate_login(this.form.username.value, this.form.password.value, this.form.captcha.value)"><br>
		</p>
	</form>
	
	<?php

}else{
	//If logged in:
	//Get User data
	$username = "NA";
	$name = "NA";
	$company = "NA";
	$email = "NA";
	$pathogenicity = "NA";
	$location = "NA";
	$origin = "NA";
	$time = "NA";
	//Show User data
	?>
	<h1>View Profile</h1>
	<h3>Login information:</h3>
	<p>
		Username: <?php echo $username ?><br>
	</p>
	<h3>User data:</h3>
	<p>
		Name: <?php echo $name ?><br>
		Company / Institution: <?php echo $company ?><br>
		Email: <?php echo $email ?><br>
	</p>
	<h3>Defaults for service runs:</h3>
	<p>
		Pathogenicity: <?php echo $pathogenicity ?><br>
		Location: <?php echo $location ?><br>
		Origin: <?php echo $origin ?><br>
		Time: <?php echo $time ?><br>
	</p><br>
	<a href='user_settings.php?action=edit'>Edit profile</a><br>
	<?php
}

$CGE->Piwik(15); // Printing Piwik codes!!

# Displays a standard footer; two parameters:
# First a simple headline like: "Support"
# then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
$CGE->standard_foot("Support","('Technical problems','Martin Thomsen','mcft@cbs.dtu.dk')");
?>