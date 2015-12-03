<?php #! /usr/bin/php5 -q
################################################################################
#                               CGE USER MANAGER                               #
################################################################################
/* This is the user manager, where the user can create, see, edit and delete
   his/her user details:
 *  'create'      -> Allows a new users to create a profile
 *  'edit'        -> Allows the users to edit and delete their profile
 *  'show'        -> Allows the users to see their profile details
 *  'pend_create' -> Waiting page while the user activates the profile
 *  'pend_delete' -> Waiting page while the user confirms the profile deletion
 *  'pend_delete' -> Waiting page while the user confirms the profile deletion
 */
$domain = 'https://cge.cbs.dtu.dk';
$meta_headers = "<base href='$domain'>";
$serviceRoot = "/srv/www/htdocs/services/";

# STANDARD CBS PAGE TEMPLATES, always include this file
include_once('/srv/www/php-lib/cge_std_tools.php'); // Including CGE_std clases and functions
// Load the CGE class (title, meta_tags, banner_path, css_paths, js_paths) '' is default
$CGE = new CGE('CGE User-pages', "$meta_headers", '', '', "/cge/user/login/js/user_manager.js,/cge/user/login/js/user_actuator.js");

# CGE MENU
# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
$CGE->std_header("User Overview", "(/cge/user/login/user_manager.php?action=show,'User Home'),(/services/,'Services')", FALSE); // Print the Menu

//function _INPUT($name){
//   // QUERY HANDLER: Used to get form elements and queries in a simple manner
//   // AUTHOR: Martin Thomsen
//   // USAGE: $form_text = _INPUT('form_text_name');
//   if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_POST[$name]))
//      return strip_tags($_POST[$name]);
//   elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_GET[$name]))
//      return strip_tags($_GET[$name]);
//   else return NULL;
//}

$ACTION = _INPUT("action");
if ($ACTION == "create"){
	// SHOW CREATION FORM
   ?><!-- START OF CONTENT -->
   <div id='create'>
      <h1>Create User Profile</h1>
      <form name='profile'>
      	<table>
      	<tr><td>Username*:</td><td><input type='text' name='username' onchange='check_name(this.value);' style='width:110px;'></td><td><span id='username_check' style='font-weight: bold;'></span></td></tr>
      	<tr><td>Email*:</td><td><input type='text' name='email' onchange='check_email(this.value);' style='width:110px;'></td><td><span id='email_check' style='font-weight: bold;'></span></td></tr>
      	<tr><td>Password*:</td><td><input type='password' name='password' onkeyup='password_strength();check_password();' style='width:110px;'></td><td>Security: <span id='password_strength' style='font-weight: bold;'>None</span></td></tr>
      	<tr><td>Confirm Password*:</td><td><input type='password' name='passwordcheck' onkeyup='check_password();' style='width:110px;'></td><td><span id='check_password' style='font-weight: bold;'></span></td></tr>
         </table>
      	<p>
          <img id="siimage" style="border: 1px solid #000; margin-right: 15px" src="/cge/user/securimage/securimage_show.php?sid=<?php echo md5(uniqid()) ?>" alt="CAPTCHA Image" align="left" />
          <embed type="application/x-shockwave-flash" src="/cge/user/securimage/securimage_play.swf?bgcol=#ffffff&amp;icon_file=/cge/user/securimage/images/audio_icon.png&amp;audio_file=/cge/user/securimage/securimage_play.php" height="32" width="32">
            <param name="movie" value="/cge/user/securimage/securimage_play.swf?bgcol=#ffffff&amp;icon_file=/cge/user/securimage/images/audio_icon.png&amp;audio_file=/cge/user/securimage/securimage_play.php" />
          </embed>
          &nbsp;
          <a tabindex="-1" style="border-style: none;" href="#" title="Refresh Image" onclick="document.getElementById('siimage').src = '/cge/user/securimage/securimage_show.php?sid=' + Math.random(); this.blur(); return false"><img src="/cge/user/securimage/images/refresh.png" alt="Reload Image" height="32" width="32" onclick="this.blur()" align="bottom" border="0" /></a><br />
          <strong>Enter Code*:</strong><br />
          <input type="text" name="captcha" size="12" maxlength="8" />
         </p>
          <input type='button' value='Create User' onclick="create_user(this.form.username.value, this.form.password.value, this.form.email.value, this.form.captcha.value);">
      </form>
   </div>
   <!-- END OF CONTENT --><?php

}elseif($ACTION == "edit"){
	// SHOW EDIT FORM AND DELETE BUTTON
	?><!-- START OF CONTENT -->
   <div id='edit'>
	   <h1>Edit Profile</h1> <button onclick="delete_user();">Delete profile</button>
	   <form name='profile'>
	   	<p>
	   		Change username to: <input type='text' id='username' name='username' onchange='check_name(this.value);'> <span id='username_check' style='font-weight: bold;'></span><br>
	   		Change password to: <input type='password' name='password' onkeyup='password_strength();check_password();'> Security: <span id='password_strength' style='font-weight: bold;'>None</span><br>
	   		Confirm password: <input type='password' name='passwordcheck' onkeyup='check_password();'> <span id='check_password' style='font-weight: bold;'></span><br>
	   	</p>
	   	<p>
	   		Edit name: <input type='text' name='name'><br>
	   		Edit company/institution: <input type='text' id='company' name='company'><br>
	   		Edit email: <input id='email' type='text' name='email' onchange='check_email(this.value);' style='width:110px;'> <span id='email_check' style='font-weight: bold;'></span><br>
	   	</p>
	   	<p>
	   		Pathogenicity: <input type='text' id='p' name='p'><br>
	   		Location: <input type='text' id='l' name='l'><br>
	   		Origin: <input type='text' id='o' name='o'><br>
	   		Time: <input type='text' id='t' name='t'><br>
	   	</p>
         <input type='button' value='Save changes' onclick="update_user(this.form.username.value, this.form.password.value, this.form.email.value);"><br>
	   </form>
   </div>
   <script type="text/javascript">
      // Retrieve User details from the database and populate the fields
      get_user_details();
   </script>
   <!-- END OF CONTENT --><?php

}elseif($ACTION == "show"){

	?><!-- START OF CONTENT -->
	<h1>View Profile</h1>
	<h3>Login information:</h3>
	<p>
		Username: <span id='username'>N/A<span><br>
	</p>
	<h3>User data:</h3>
	<p>
		Name: <span id='fullname'>N/A<span><br>
		Company / Institution: <span id='company'>N/A<span><br>
		Email: <span id='email'>N/A<span><br>
	</p>
	<h3>Defaults for service runs:</h3>
	<p>
		Pathogenicity: <span id='pathogenicity'>N/A<span><br>
		Location: <span id='location'>N/A<span><br>
		Origin: <span id='origin'>N/A<span><br>
		Date: <span id='date'>N/A<span><br>
	</p><br>
	<a href='/cge/user/login/user_manager.php?action=edit'>Edit profile</a><br>
   <script type="text/javascript">
      // Retrieve User details from the database and populate the fields
      get_user_details();
   </script>
   <!-- END OF CONTENT --><?php

}elseif($ACTION == "cancel_create"){
   $uid = _INPUT("uid");
   $tmp = _INPUT("tmp");
   echo "<p id='msg'>Cancelling your account creation...</p><script type='text/javascript'>cancel_create('$uid', '$tmp');</script>";

}elseif($ACTION == "confirm_create"){
   $uid = _INPUT("uid");
   $tmp = _INPUT("tmp");
   echo "<p id='msg'>Activating your account...</p><script type='text/javascript'>confirm_create('$uid', '$tmp');</script>";
 
}elseif($ACTION == "cancel_reset"){
   $uid = _INPUT("uid");
   echo "<p id='msg'>Cancelling reset of your password...</p><script type='text/javascript'>cancel_reset('$uid');</script>";

}elseif($ACTION == "cancel_delete"){
   $uid = _INPUT("uid");
   $tmp = _INPUT("tmp");
   echo "<p id='msg'>Cancelling the deletion of your account...</p><script type='text/javascript'>cancel_delete('$uid', '$tmp');</script>";

}elseif($ACTION == "confirm_delete"){
   $uid = _INPUT("uid");
   $tmp = _INPUT("tmp");
   echo "<p id='msg'>Finalising deletion of your account...</p><script type='text/javascript'>confirm_delete('$uid', '$tmp');</script>";

}else{
   echo "<p id='msg'>Redirecting to service overview page...</p><script type='text/javascript'>window.location='/services/index.php';</script>";
}

$CGE->Piwik(15); // Printing Piwik codes!!

# Displays a standard footer; two parameters:
# First a simple headline like: "Support"
# then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
$CGE->standard_foot("Support","('Technical problems','CGE Support','cgehelp@cbs.dtu.dk')");
?>