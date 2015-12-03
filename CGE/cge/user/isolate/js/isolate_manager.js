/* USER MANAGER SCRIPTS
 * This script contains function which enables user management.
 * Eg. create_user(uid, pwd, email, captcha): Creation of a new user
 *     update_user(uid, pwd, email): Update/changing of user settings; email, password etc.
 *     delete_user(): Deletion of a user and all of their user data, including isolate data
 *     get_user_details(): Retreive the user details from the database
 */
function get_isolate(iid){
   /* Retrieve isolate data and populate the form
    */
	var isolate_data;
	$.ajax({
		type : "POST",
		url : "https://cge.cbs.dtu.dk/cge/user/isolate/php/get_isolate.php",
		data : {
         ISOLATEID : iid
         },
		timeout : 2000,
		dataType : 'xml',
		async : false,
		success : function(xml) {
			xml = $.xml2json(xml);
			// CHECK FOR ERRORS FROM THE PHP SCRIPT
			if (xml.STATUS != 'ACCEPTED') {
				if (xml.STATUS == 'NODATA') {
					alert("Isolate does not exist!");
				}else if (xml.STATUS == 'REJECTED') {
					alert("Username and sessionid did not match!");
				}else if (xml.STATUS == 'BADUSER') {
					alert("Username was invalid!");
				}else if (xml.STATUS == 'BADSESSION') {
					alert("Sessionid was invalid!");
				}else{
					alert("Error unhandled script status: "+ xml.STATUS);
				}
			}else{
				isolate_data = xml;
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//AN ERROR OCCURED IN THE COMMUNICATION
			alert('Communication Error: '+ errorThrown +" "+ textStatus);
		}
	});
	return isolate_data;
}



//CGE Isolate meta data fetcher
function get_isolate_data_all(){
	var isolate_data;
	$.ajax({
		type : "POST",
		url : "https://cge.cbs.dtu.dk/cge/user/isolate/php/get_data_all.php",
		data : {},
		timeout : 2000,
		dataType : 'xml',
		async : false,
		success : function(xml) {
         //console.log(xml);
			xml = $.xml2json(xml);
         //console.log(xml);
			// CHECK FOR ERRORS IN THE getmetadata PHP SCRIPT
			if (xml.STATUS != 'ACCEPTED') {
				if (xml.STATUS == 'NODATA') {
					alert("User has no data available!");
				}else if (xml.STATUS == 'REJECTED') {
					alert("Username and sessionid didn't match up!");
				}else if (xml.STATUS == 'BADUSER') {
					alert("Username was invalid!");
				}else if (xml.STATUS == 'BADSESSION') {
					alert("Sessionid was invalid!");
				}else{
					alert("Error unhandled script status: "+ xml.STATUS);
				}
			}else{
            //console.log('SUCCESS: ', xml);
				isolate_data = xml;
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//AN ERROR OCCURED IN THE COMMUNICATION
			alert('Communication Error: '+ errorThrown +" "+ textStatus);
		}
	});
	//console.log('return: ', data);
	return isolate_data;
};


function update_isolate(form){
   /* Update isolate
    */
   var iid = form.iid.value,
   name = form.name.value,
   note = form.note.value,
   mdate = form.mdate.value,
   technology = form.technology.value,
   ipublic = Number(form.ipublic.checked),
   country = form.country.value,
   region = form.region.value,
   city = form.city.value,
   zip = form.zip.value,
   lon = form.lon.value,
   lat = form.lat.value,
   lnote = form.lnote.value,
   origin = form.origin.value,
   onote = form.onote.value,
   pathogenicity = form.pathogenicity.value,
   pnote = form.pnote.value;
   // VALIDATE FORM INPUTS
   if(!check_name(country, 'country_check') | !check_name(city, 'city_check') | !check_date(mdate, 'date_check') | origin === '' | !check_float(lon, 'lon_check') | !check_float(lat, 'lat_check')){
      alert("Invalid Inputs!");
      return false;
   }else{
		$.ajax({
			type : "POST",
			url : "https://cge.cbs.dtu.dk/cge/user/isolate/php/update_isolate.php",
			data : {
            IID                : iid,
            NAME               : name,
            NOTE               : note,
            MDATE              : mdate,
            TECHNOLOGY         : technology,
            IPUBLIC            : ipublic,
            COUNTRY            : country,
            REGION             : region,
            CITY               : city,
            ZIP                : zip,
            LONGITUDE          : lon,
            LATITUDE           : lat,
            LOCATION_NOTE      : lnote,
            ORIGIN             : origin,
            ORIGIN_NOTE        : onote,
            PATHOGENICITY      : pathogenicity,
            PATHOGENICITY_NOTE : pnote
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
					}else if (xml.STATUS == 'NOCOUNTRY') {
						alert("No country was specified!");
					}else if (xml.STATUS == 'NOCITY') {
						alert("No city was specified!");
               }else if (xml.STATUS == 'BADID') {
						alert("Invalid isolate ID ("+ xml.VALUE +")!");
					}else if (xml.STATUS == 'BADLON') {
						alert("Invalid longitude ("+ xml.VALUE +")!");
					}else if (xml.STATUS == 'BADLAT') {
						alert("Invalid latitude ("+ xml.VALUE +")!");
					}else if (xml.STATUS == 'BADPATHOGEN') {
						alert("Invalid pathogenicity choice ("+ xml.VALUE +")!");
					}else if (xml.STATUS == 'BADPUBLIC') {
						alert("Public ("+ xml.VALUE +") was not a boolean value!");
					}else if (xml.STATUS == 'MYSQLERROR') {
						alert("MYSQL query failed, it responded with this: "+ xml.MESSAGE);
					}else{
						//console.log(xml);
                  alert("Error unhandled script status: "+ xml.STATUS);
					}
				}else{
               // User updated, update shown username, and show succes message.
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
}


function delete_isolate(isolateID){
   /* Deletion of a user and all of their user data, including isolate data
    */
   if(confirm('Are you sure you wish to delete this isolate and all of its data?')){
	   $.ajax({
	   	type : "POST",
	   	url : "https://cge.cbs.dtu.dk/cge/user/isolate/php/delete_isolate.php",
	   	data : {
            'IID' : isolateID
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
	   			}else if (xml.STATUS == 'BADID') {
	   				alert("An invalid service id was specified!");
	   			}else if (xml.STATUS == 'NOID') {
	   				alert("No isolate id was specified!");
	   			}else if (xml.STATUS == 'NOISOLATE') {
	   				alert("Isolate did not exist!");
	   			}else if (xml.STATUS == 'NOFOLDER') {
                  // The service, was deleted, though no files existed, and is removed from the list.
                  console.log(xml.FOLDER);
                  remove_isolate(isolateID);
	   			}else{
	   				console.log(xml);
                  alert("Error unhandled script status: "+ xml.STATUS);
	   			}
	   		}else{
               // User marked as deleted, remove creation div, and show pending div.
               //console.log(xml.CMD);
	   			remove_isolate(isolateID);
	   		}
	   	},
	   	error : function(jqXHR, textStatus, errorThrown) {
	   		//AN ERROR OCCURED IN THE COMMUNICATION
	   		alert('Communication Error: '+ errorThrown +" "+ textStatus);
	   	}
	   });
   }
}


function delete_run(serviceID){
   /* Deletion of a user and all of their user data, including isolate data
    */
   // Are you sure you wish to remove this isolate?
   if(confirm('Are you sure you wish to delete this service instance and all of its files?')){
	   $.ajax({
	   	type : "POST",
	   	url : "https://cge.cbs.dtu.dk/cge/user/isolate/php/delete_run.php",
	   	data : {
            'SID' : serviceID
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
	   			}else if (xml.STATUS == 'BADID') {
	   				alert("An invalid service id was specified!");
	   			}else if (xml.STATUS == 'NOID') {
	   				alert("No service id was specified!");
	   			}else if (xml.STATUS == 'NOSERVICE') {
	   				alert("Service instance did not exist!");
	   			}else if (xml.STATUS == 'NOFOLDER') {
                  // The service, was deleted, though no files existed, and is removed from the list.
                  console.log(xml.FOLDER);
                  remove_run(serviceID);
	   			}else{
	   				console.log(xml);
                  alert("Error unhandled script status: "+ xml.STATUS);
	   			}
	   		}else{
               // The service, was deleted, and is removed from the list.
               //console.log(xml.CMD);
	   			remove_run(serviceID);
	   		}
	   	},
	   	error : function(jqXHR, textStatus, errorThrown) {
	   		//AN ERROR OCCURED IN THE COMMUNICATION
	   		alert('Communication Error: '+ errorThrown +" "+ textStatus);
	   	}
	   });
   }
}


function remove_isolate(isolateID){
   /* Remove the deleted isolate from the list.
   */
   deleteRow(document.getElementById("I"+ isolateID));
   deleteRow(document.getElementById("I"+ isolateID +"_services"));
}

function remove_run(serviceID){
   /* Remove the deleted service from the list.
   */
   deleteRow(document.getElementById("S"+ serviceID));
}

function deleteRow(r){
   /* Remove the selected row from its parent table.
   */
   if (r != null) {
      console.log(r);
      r.parentNode.deleteRow(r.rowIndex);
   }
}


function populate_isolate_form(isolate){
   /* Populate the form with the retrieved data
   */
   if (isolate) {
      // isolate.idate
      // isolate.shared
      add_form_data('iid', isolate.id);
      add_form_data('name', isolate.name);
      add_form_data('note', isolate.metadata.note);
      add_form_data('mdate', isolate.metadata.date);
      add_form_data('country', isolate.metadata.country);
      add_form_data('region', isolate.metadata.region);
      add_form_data('city', isolate.metadata.city);
      add_form_data('zip', isolate.metadata.zip);
      add_form_data('lon', isolate.metadata.lon);
      add_form_data('lat', isolate.metadata.lat);
      add_form_data('lnote', isolate.metadata.lnote);
      add_form_data('onote', isolate.metadata.onote);
      add_form_data('pnote', isolate.metadata.pnote);
   // Handle Checkboxes and select
      handle_select('technology', isolate.technology);
      handle_checkbox('ipublic', isolate.ipublic);
      handle_select('pathogenicity', isolate.metadata.pathogenicity);
      handle_select('origin', isolate.metadata.origin);
   }
}

function add_form_data(name, value){
   /* Check the nodeName of the element and assign the new value properly
   */
   f = document.forms['profile'].elements[name]
   if(f === null){ return; } // skipping unexisting elements
   f.value = value;
}

function handle_checkbox(name, bool){
   /* Check the nodeName of the element and assign the new value properly
   */
   f = document.forms['profile'].elements[name]
   if(f === null){ return; } // skipping unexisting elements
   if(bool){ f.checked = 'checked'; }else{ f.checked = ''; }
}

function handle_select(name, value){
   /* Check the nodeName of the element and assign the new value properly
   */
   f = document.forms['profile'].elements[name]
   if(f === null){ return; } // skipping unexisting elements
   for (var i=0; i<f.length; i++){
      if(f.options[i].value === value){
         f.selectedIndex = i;
         break;
      }
   }
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
function check_password(id){
	//This function check the passwords are identical
	var form = document.forms['profile'];
	var element = document.getElementById(id);
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

function check_name(name, id){
   var validUsername = /[^a-zA-Z0-9\.\-\_\@]/;
	//var name = document.forms['profile'].elements["username"].value;
	var element = document.getElementById(id);
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

function check_date(date, id){
   var validDate = /^\d{4}-\d{2}-\d{2}/;
	//var name = document.forms['profile'].elements["username"].value;
	var element = document.getElementById(id);
	if(!validDate.test(date)){
		//Special characters not allowed in Username
		element.innerHTML = "Warning! Date must follow the following format: YYYY-MM-DD Eg. 1987-12-31";
		return false;
	}else{
		element.innerHTML = "";
		return true;
	}
}

function check_float(number, id){
   var validFloat = /^\d+\.\d+$/;
	//var name = document.forms['profile'].elements["username"].value;
	var element = document.getElementById(id);
	if(!validFloat.test(number)){
		//Special characters not allowed in Username
		element.innerHTML = "Warning! A float must follow the following format: X.X Eg. 100.987";
		return false;
	}else{
		element.innerHTML = "";
		return true;
	}
}

function check_email(email, id){
   var validEmail = /[^a-zA-Z0-9\.\+\-\_\@]/;
	//var email = document.forms['profile'].elements["email"].value;
	var element = document.getElementById(id);
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


// FUNCTIONS
function input2form(form, type, name, value){
   // FUNCTION WHICH APPENDS AN INPUT TO THE FORM
   var input = document.createElement("input");
   input.type = type;
   input.value = value;
   input.name = name;
   form.appendChild(input);
}

function link2output(iid, sid){
   // THIS FUNCTION CREATES AND SUBMITS A FORM TO A SERVICE OUTPUT PAGE
   //iid = typeof iid !== 'undefined' ? iid : null;
   //sid = typeof sid !== 'undefined' ? sid : null;
   // CREATING FORM
   var form = document.createElement("form");
   form.action = '/tools_new/client/platform/show_result.php';
   form.method = 'post';
   // ADDING INPUTS
   if(iid !== null){input2form(form, 'hidden', 'IID', iid);}
   if(sid !== null){input2form(form, 'hidden', 'SID', sid);}
   // SUBMITTING FORM
   document.body.appendChild(form);
   form.submit();
}

function switchtag(field){
   if(field.innerHTML == '+'){
      field.innerHTML = '-';
      field.style.color = "red";
   }else{
      field.innerHTML = '+';
      field.style.color = "green";
   }
}

function populate_isolate_table(isolates){
   /* DISPLAY ISOLATE DATA
    */
   var isoview, isolate, isoTab, isoRow, isoFld, runTab, runRow;
   isoTab = document.getElementById('isoview'); // TABLE CONTAINING ISOLATES
   for (isolate in isolates){
      if(isolate.charAt(0)=='I'){ // ISOLATE DATA FOUND
         // ADD ISOLATE ENTRY (ROW)
         idata = isolates[isolate];
         isoRow = isoTab.insertRow(-1);
         isoRow.setAttribute("id", isolate);
         // INSERT ISOLATE META DATA
         isoRow.insertCell(0).innerHTML = '+';
         isoRow.insertCell(1).innerHTML = '<a href="https://cge.cbs.dtu.dk/'+
                              'tools_new/client/platform/show_result.php?IID='+
                              idata.id+'">'+ idata.sample_name +'</a>';
         isoRow.insertCell(2).innerHTML = idata.metadata.collection_date;
         isoRow.insertCell(3).innerHTML = idata.metadata.country;
         isoRow.insertCell(4).innerHTML = idata.metadata.city;
         isoRow.insertCell(5).innerHTML = idata.metadata.isolation_source;
         // INSERT ISOLATE ACTION BUTTONS
         isoRow.insertCell(6).innerHTML =  "<form method='post'><input type='hidden' name='action' value='edit'><input type='hidden' name='iid' value='"+idata.id+"'><input type='submit' value='Edit'></form>"+
                                           ' / <button onclick="delete_isolate('+idata.id+');">Remove</button>';
         // LEFT-ALIGN SERVICE TEXT
         isoRow.cells[1].setAttribute("class",'left');
         isoRow.cells[1].style.fontWeight = "bold";
         isoRow.cells[0].style.fontSize = "21";
         isoRow.cells[0].style.color = "green";
         //isoRow.cells[0].style.textDecoration = "underline";
         isoRow.cells[0].setAttribute("onclick",'visswitch("'+isolate+'_services", "OFF", "table-row");switchtag(this);');
         if(idata.services !== 'NULL'){ // Service run(S) found
            // ADD AN ISOLATE SERVICE ROW TO THE ISOLATE OVERVIEW TABLE
            isoSerRow = isoTab.insertRow(-1);
            isoSerRow.setAttribute("id", isolate+"_services");
            isoSerRow.style.display = 'None';
            // PREPARE AN ISOLATE SERVICE FIELD FOR THE SERVICE OVERVIEW TABLE
            isoSerFld = isoSerRow.insertCell(0);
            isoSerFld.setAttribute("colspan",'7');
            // CREATE THE SERVICE OVERVIEW TABLE
            runTab = document.createElement('table');
            // APPEND THE SERVICE OVERVIEW TABLE TO THE PREPARED ISOLATE SERVICE FIELD
            isoSerFld.appendChild(runTab);
            // MAKE A RUN TABLE HEADER
            runRow = runTab.insertRow(-1);
            runRow.insertCell(0).innerHTML = 'Service';
            runRow.insertCell(1).innerHTML = 'Date';
            runRow.insertCell(2).innerHTML = "Status";
            runRow.insertCell(3).innerHTML = "Action";
            // SET ATTRIBUTES FOR THE RUN TABLE ETC
            runTab.setAttribute("class",'runTab');
            runRow.setAttribute("class",'runhead');
            // DISPLAY ISOLATE RUNS
            for (run in idata.services){
               var rdata = idata.services[run];
               // ADD RUN ENTRY (ROW)
               runRow = runTab.insertRow(-1);
               runRow.setAttribute("id", run);
               // INSERT RUN DATA
               runRow.insertCell(0).innerHTML = rdata.service;
               runRow.insertCell(1).innerHTML = rdata.date;
               runRow.insertCell(2).innerHTML = rdata.status;
               // INSERT RUN ACTIONS
               runRow.insertCell(3).innerHTML = "<button onclick='delete_run("+rdata.id+");'>Remove</button>";
               // ADD OUTPUT REDIRECT LINK
               runRow.cells[0].setAttribute("class",'left');
               runRow.cells[0].style.font = "bold";
               runRow.cells[0].style.color = "blue";
               runRow.cells[0].style.textDecoration = "underline";
               runRow.cells[0].setAttribute("onclick",'link2output(null, "'+rdata.id+'");');
            }
         }else{
            // TURN OFF SERVICE BUTTON
            isoRow.cells[0].style.color = "grey";
            isoRow.cells[0].innerHTML = 'X';
            isoRow.cells[0].style.fontSize = "10";
            isoRow.cells[0].setAttribute("onclick",'');
         }
      }
   }
   
   //data.metadata.region
   //data.metadata.zip
   //data.metadata.pathogenicity
   //data.assembly.id
   //data.assembly.n50
}
