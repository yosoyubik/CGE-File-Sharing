/* USER LOGIN SCRIPTS
 * This script contains function which enables user login etc.
 * Eg. login:  Log in to a user account
 *     logout: Log out and end user session
 *     reset:  Reset the password of a user account
 */

// IE fix for undefined console
if(!console){console={}; console.log=function(){};}

function login(uid, pwd){
   /* Log in to a user account
    */
	if(uid != '' & pwd != ''){
		$.ajax({
			type : "POST",
			url : "/cge/user/login/php/login.php",
			data : {
				USERNAME : uid,
				PASSWORD : pwd
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				console.log(xml);
				if (xml.STATUS != 'ACCEPTED' & xml.STATUS != 'ENDRESET') {
					// AN ERROR OCCURED IN THE LOGIN PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
						alert("Username or password was incorrect!");
					}else if (xml.STATUS == 'BADUSER') {
						alert("Username is invalid!");
					}else if (xml.STATUS == 'NOACTIVATION') {
						alert("The user is pending activation!\nTo confirm user creation, please click on the confirm link in the received email.");
					}else if (xml.STATUS == 'CANCELDELETE') {
						alert("The user is pending deletion!\nTo cancel user deletion, please click on the cancel link in the received email.");
					}else{
						//console.log(xml);
						alert("Error occured during log in: "+ xml.STATUS);
					}
				}else{
					showLoggedin(xml.USERNAME, xml.SESSIONID);
               if (xml.STATUS == 'ENDRESET') {
						if(confirm("The password has been reset!\nDo you want to change your password?")){
                     window.location = '/cge/user/login/user_manager.php?action=edit';
                  }
               }
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				//AN ERROR OCCURED IN THE COMMUNICATION
				alert('Communication Error: '+ errorThrown +" "+ textStatus);
			}
		});
	}
}


function logout(){
   /* Log out and end user session
    */
	$.ajax({
		type : "POST",
		url : "/cge/user/login/php/logout.php",
		data : {},
		timeout : 2000,
		dataType : 'xml',
		async : false,
		success : function(xml) {
			xml = $.xml2json(xml);
			if (xml.STATUS != 'ACCEPTED') {
				// AN ERROR OCCURED IN THE LOGOUT PHP SCRIPT
				if (xml.STATUS == 'REJECTED') {
					alert("Username and session id did not match!\nYou are not logged in.");
				}else if (xml.STATUS == 'BADUSER') {
					alert("Session username is invalid!\nYou are not logged in.");
				}else if (xml.STATUS == 'BADSESSION') {
					alert("Session id is invalid!\nYou are not logged in.");
				}else{
					//console.log(xml);
					alert("Error occured during log out!\n"+ xml.STATUS);
				}
				showLogin();
			}else{
				showLogin();
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//AN ERROR OCCURED IN THE COMMUNICATION
			alert('Communication Error!\n'+ errorThrown +" "+ textStatus);
		}
	});
}


function reset_user(email){
   /* Reset the password of a user account
    */
	if(email != ''){
		$.ajax({
			type : "POST",
			url : "/cge/user/login/php/reset_user.php",
			data : {
				EMAIL : email
         },
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE LOGIN PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
						alert("The email was not found in our user database!");
					}else if (xml.STATUS == 'BADEMAIL') {
						alert("Email is invalid!");
					}else if (xml.STATUS == 'ISRESET') {
						alert("The password has already been reset!\nTo log in, please use the new password received by email.");
					}else if (xml.STATUS == 'NOACTIVATION') {
						alert("The user is pending activation!\nTo confirm user creation, please click on the confirm link in the received email.");
					}else if (xml.STATUS == 'CANCELDELETE') {
						alert("The user is pending deletion!\nTo cancel user deletion, please click on the cancel link in the received email.");
					}else{
						//console.log(xml);
						alert("Error occured during user reset: "+ xml.STATUS);
					}
				}else{
               // User password was reset, show pending login alert.
   				showLogin();
               alert("The password has been reset!\nTo log in, please use the new password received by email.");
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				//AN ERROR OCCURED IN THE COMMUNICATION
				alert('Communication Error: '+ errorThrown +" "+ textStatus);
			}
		});
	}
}


function showLoggedin(uid, sesid){
   /* DISPLAYING LOGGED IN ELEMENT
    * REMOVING RESET ELEMENT
    * REMOVING LOG IN ELEMENT
	 */
   // SETTING user_logged_in VARIABLE
   window.user_logged_in = true;
	// SETTING USERID IN FORM
	if(document.forms["theform"]) {
		document.forms["theform"].userID.value = uid;
		document.forms["theform"].usersession.value = sesid;
	}
	// DISPLAYING LOGGED IN ELEMENT AND HIDING LOGIN
	document.getElementById('logged').innerHTML = "";
	document.getElementById('reset').innerHTML = "";
	document.getElementById('login').innerHTML = "\
         <ul class='topnav'>\
            <li style='font-size:16px;'>Welcome <span style='font-size:16px;'><a href='javascript:void(0)'>"+ uid +"</a></span>\
               <ul class='subnav'>\
                  <li><a href='/services/cge/map.php'>Map Overview</a></li>\
                  <li><a href='/services/cge/batch.php'>Batch Uploader</a></li>\
                  <li><a href='/tools_new/client/platform/isolate_manager.php'>Manage Isolates</a></li>\
                  <li><a href='/tools_new/client/platform/user_manager.php?action=show'>Settings</a></li>\
                  <li><a href='javascript:void(0)' onclick='logout();'>Log out</a></li>\
               </ul>\
            </li>\
         </ul>";
//						<li><span><a href='#'>Profile</a></span>\
//                     <ul class='subsubnav'>\
//                        <li><a href='/cge/user/login/user_manager.php'>Show Profile</a></li>\
//                        <li><a href='/cge/user/login/user_manager.php?action=edit'>Edit Profile</a></li>\
//						   </ul>\
//						</li>
	activate();
}


function showLogin(){
	/* DISPLAYING LOG IN ELEMENT
    * REMOVING RESET ELEMENT
    * REMOVING LOGGED IN ELEMENT
	 */
   // SETTING user_logged_in VARIABLE
   window.user_logged_in = false;
	// RESETTING USERID IN FORM
	if(document.forms["theform"]) {
		document.forms["theform"].userID.value = 'Anonymous';
		document.forms["theform"].usersession.value = '';
	}
	// DISPLAYING LOGIN ELEMENT AND HIDING + RESETTING LOGGED IN
	document.getElementById('logged').innerHTML = "";
	document.getElementById('reset').innerHTML = "";
	document.getElementById('login').innerHTML = "\
	<form> \
		<div> \
			<span style='margin: 0 2 0 0;'>Username</span> \
			<span><input type='text' name='username' value='' style='width: 65px;height:14px;font-size: 12px;'></span> \
		</div><div> \
			<span style='margin: 0 5 0 0;'>Password</span> \
			<span><input type='password' name='password' value='' style='width: 65px;height:14px;font-size: 12px;'></span> \
		</div><div> \
			<input type='button' value='New' onclick='window.open(&quot;/cge/user/login/user_manager.php?action=create&quot;)' style='width: 34px;height:17px;font-size: 8px;text-align: center;'> \
			<input type='button' value='Reset' onclick='showReset();' style='width: 42px;height:17px;font-size: 8px;text-align: center;'> \
			<input type='button' value='Login' onclick='login(this.form.username.value, this.form.password.value);' style='width: 39px;height:17px;font-size: 8px;text-align: center;'> \
		</div> \
	</form>";
}


function showReset(){
	/* DISPLAYING RESET ELEMENT
    * REMOVING LOGGED IN ELEMENT
    * REMOVING LOG IN ELEMENT
	 */
	document.getElementById('logged').innerHTML = "";
	document.getElementById('reset').innerHTML = "";
	document.getElementById('login').innerHTML = "\
	<form> \
		<div> \
			<span style='margin: 0 5 0 0;font-size:14px;'>Email</span> \
		</div><div style='margin: 0 0 2 0;'> \
			<span><input type='text' name='email' value='' style='width:127px;height:16px;font-size:12px;'></span> \
		</div><div> \
			<input type='button' value='Login' onclick='showLogin();' style='width: 40px;height:17px;font-size: 9px;'> \
			<input type='button' value='Reset Password' onclick='reset_user(this.form.email.value);' style='width: 83px;height:17px;font-size: 9px;'> \
		</div> \
	</form>";
}


function activate(){
	/* Activation of user menu navigation pane.
	 */
	//NAVIGATION PANE
	$("ul.topnav li span").click(function() { //When trigger is clicked...  ul.topnav li span
		//Following events are applied to the trigger (Hover events for the trigger)
	}).hover(function() {
		$(this).addClass("subhover"); //On hover over, add class "subhover"
		$(this).parent().find("ul.subnav").slideDown('fast').show(); //Drop down the subnav on click
		$(this).parent().hover(function() {
			$(this).parent().find("ul.subnav").show();
		}, function(){
			$(this).parent().find("ul.subnav").slideUp('fast'); //When the mouse hovers out of the subnav, move it back up
		});
	}, function(){  //On Hover Out
		$(this).removeClass("subhover"); //On hover out, remove class "subhover"
	});

	//SUB NAVIGATION PANE
	$("ul.topnav li ul.subnav li span").click(function() { //When trigger is clicked...  ul.topnav li span
		//Following events are applied to the trigger (Hover events for the trigger)
	}).hover(function() {
		$(this).addClass("subsubhover"); //On hover over, add class "subsubhover"
		$(this).parent().find("ul.subsubnav").slideDown('fast').show(); //Drop down the subnav on click
		$(this).parent().hover(function() {
			//HOVER
		}, function(){
			$(this).parent().find("ul.subsubnav").slideUp('fast'); //When the mouse hovers out of the subnav, move it back up
		});
	}, function(){  //On Hover Out
		$(this).removeClass("subsubhover"); //On hover out, remove class "subhover"
	});
}
