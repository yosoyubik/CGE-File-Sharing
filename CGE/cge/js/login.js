//CGE User Login javascripts

// IE fix for undefined console
if(!console){console={}; console.log=function(){};}


function showLoggedin(uid){ // SUCCESSFUL LOGIN
	// SETTING USERID IN FORM
	if(document.forms["theform"]) {
		document.forms["theform"].userID.value = uid;
	}
	// DISPLAYING LOGGED IN ELEMENT AND HIDING LOGIN
	//document.getElementById('login').style.display = "None";
	//document.getElementById('logged').style.display = "Block";
	document.getElementById('login').innerHTML = "";
	document.getElementById('logged').innerHTML = "\
         <ul class='topnav'>\
            <li>Welcome <span><a href='#'>"+ uid +"</a></span>\
               <ul class='subnav'>\
                  <li><a href='/cge/user/useroverview.php'>User Overview</a></li>\
                  <li><a onclick='logout()'>Log out</a></li>\
               </ul>\
            </li>\
         </ul>";
//						<li><span><a href='#'>Profile</a></span>\
//                     <ul class='subsubnav'>\
//                        <li><a href='/cge/user/user_settings.php'>Show Profile</a></li>\
//                        <li><a href='/cge/user/user_settings.php?action=edit'>Edit Profile</a></li>\
//						   </ul>\
//						</li>
	activate();
}


function showLogin(){ // SUCCESSFUL LOGOUT
	// RESETTING USERID IN FORM
	if(document.forms["theform"]) { 
		document.forms["theform"].userID.value = 'Anonymous';
	}
	// DISPLAYING LOGIN ELEMENT AND HIDING + RESETTING LOGGED IN
	//document.getElementById('logged').style.display = "None";
	//document.getElementById('login').style.display = "Block";
	document.getElementById('logged').innerHTML = "";
	document.getElementById('login').innerHTML = "\
	<form> \
		<div style='width:110px;margin:2 0;text-align: right;'> \
			<span style='margin: 0 5 0 0;'>Username</span> \
			<span><input type='text' name='username' value='' style='width: 50px;height:14px;font-size: 9px;'></span> \
		</div><div style='width:110px;margin:2 0;text-align: right;'> \
			<span style='margin: 0 5 0 0;'>Password</span> \
			<span><input type='password' name='password' value='' style='width: 50px;height:14px;font-size: 9px;'></span> \
		</div><div  style='width:110px;margin:2 3;text-align: center;'> \
			<input type='button' value='New login' onclick='window.location=&quot;/cge/user/user_settings.php?action=create&quot;' style='width: 60px;height:17px;font-size: 9px;'> \
			<input type='button' value='Login' onclick='login(this.form.username.value, this.form.password.value)' style='width: 40px;height:17px;font-size: 9px;'> \
		</div> \
	</form>";
	
}


function login(uid, pwd){
	if(uid != '' & pwd !=''){
		$.ajax({
			type : "POST",
			url : "/cge/user/login.php", //https://cge.cbs.dtu.dk
			data : {
				USERNAME : uid,
				PASSWORD : pwd
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				var xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE LOGIN PHP SCRIPT
					if (xml.STATUS == 'REJECTED') {
						alert("Username or password was incorrect!");
					}else if (xml.STATUS == 'BADUSER') {
						alert("Username was invalid!");
					}else{
						console.log(xml);
						alert("Error With Login: "+ xml.STATUS);
					}
				}else{
					showLoggedin(xml.USERNAME);
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				//AN ERROR OCCURED IN THE COMMUNICATION
				alert('Communication Error: '+ errorThrown +" "+ textStatus);
			}
		});
	};
};


function logout(){
	$.ajax({
		type : "POST",
		url : "/cge/user/logout.php", //https://cge.cbs.dtu.dk
		data : {},
		timeout : 2000,
		dataType : 'xml',
		async : false,
		success : function(xml) {
			var xml = $.xml2json(xml);
			if (xml.STATUS != 'ACCEPTED') {
				// AN ERROR OCCURED IN THE LOGOUT PHP SCRIPT
				if (xml.STATUS == 'REJECTED') {
					alert("Invalid or corrupted session cookie!\n"+xml.MESSAGE);
				}else if (xml.STATUS == 'BADSESSION') {
					alert("Corrupted session cookie!\n"+xml.MESSAGE);
				}else{
					console.log(xml);
					alert("Error With Login!\n"+ xml.STATUS);
				}
			}else{
				showLogin();
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//AN ERROR OCCURED IN THE COMMUNICATION
			alert('Communication Error!\n'+ errorThrown +" "+ textStatus);
		}
	});
};


function activate(){
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
};