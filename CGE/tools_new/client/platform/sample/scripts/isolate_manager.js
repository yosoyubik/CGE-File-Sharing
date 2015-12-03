/* USER MANAGER SCRIPTS
 * This script contains function which enables user management.
 * Eg. create_user(uid, pwd, email, captcha): Creation of a new user
 *     update_user(uid, pwd, email): Update/changing of user settings; email, password etc.
 *     delete_user(): Deletion of a user and all of their user data, including isolate data
 *     get_user_details(): Retreive the user details from the database
 */

function status_check(xml){
   switch(xml.STATUS) {
      case 'BADUSER':         alert("Username was invalid!"); break;
      case 'BADSESSION':      alert("Sessionid was invalid!"); break;
      case 'NOIID':           alert("Sample id was not provided!"); break;
      case 'NOSID':           alert("Service id was not provided!"); break;
      case 'BADIID':          alert("Sample id was invalid!"); break;
      case 'BADSID':          alert("Service id was invalid!"); break;
      case 'MYSQLERROR':      alert("MYSQL query failed, it responded with this: "+ xml.MESSAGE); break;
      case 'NOUSER':          alert("This user account does not exist!"); break;
      case 'INVALIDSESSION':  alert("Your session is invalid!"); break;
      case 'NOISOLATE':       alert("The requested sample does not exist!"); remove_isolate(xml.isolateID); break;
      case 'NOSERVICE':       alert("The requested service instance does not exist!"); remove_run(xml.serviceID); break;
      case 'NOACCESS':        alert("This user account does not have the required access permission to the requested data!"); break;
      case 'NODATA':          alert("No data was found!"); break;
      case 'NOIFOLDER':       console.log('Sample folder not found - '+ xml.folder); remove_isolate(xml.isolateID); break; // The isolate, was deleted, though no files existed, and is removed from the list.
      case 'NOSFOLDER':       console.log('Service folder not found - '+ xml.folder); remove_run(xml.serviceID); break;     // The service, was deleted, though no files existed, and is removed from the list.
      case 'NOCOUNTRY':       alert("No country was specified!"); break;
      case 'BADLON':          alert("Invalid longitude ("+ xml.VALUE +")!"); break;
      case 'BADLAT':          alert("Invalid latitude ("+ xml.VALUE +")!"); break;
      case 'BADPATHOGEN':     alert("Invalid pathogenicity choice ("+ xml.VALUE +")!"); break;
      case 'BADPUBLIC':       alert("Public ("+ xml.VALUE +") was not a boolean value!"); break;
      default:                console.log("Error unhandled script status: "+ xml.STATUS);
   }
}

function get_isolate(iid){
   /* Retrieve isolate data and populate the form
    */
	var isolate_data;
	$.ajax({
		type : "POST",
		url : ScriptDirPath('isolate_manager.js') +"../../../server/platform/isolate/get_isolate.php", // https://cge.cbs.dtu.dk/tools_new/server/platform/isolate/get_isolate.php
		data : {
         IID : iid
         },
		timeout : 2000,
		dataType : 'xml',
		async : false,
		success : function(xml) {
			xml = $.xml2json(xml);
			// CHECK FOR ERRORS FROM THE PHP SCRIPT
			if (xml.STATUS != 'ACCEPTED') {
				// AN ERROR OCCURED IN THE PHP SCRIPT
            //console.log(xml);
            status_check(xml);
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



//CGE Sample meta data fetcher
function get_isolate_data_all(){
	var isolate_data;
	$.ajax({
		type : "POST",
		url : ScriptDirPath('isolate_manager.js') +"../../../server/platform/isolate/get_data_all.php", // "https://cge.cbs.dtu.dk/tools_new/server/platform/isolate/get_data_all.php",
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
				// AN ERROR OCCURED IN THE PHP SCRIPT
            window.autoupdate = false;
            status_check(xml);
			}else{
            //console.log('SUCCESS: ', xml);
				isolate_data = xml;
			}
		},
		error : function(jqXHR, textStatus, errorThrown) {
			//AN ERROR OCCURED IN THE COMMUNICATION
         window.autoupdate = false;
			alert('Communication Error: '+ errorThrown +" "+ textStatus);
		}
	});
	//console.log('return: ', data);
	return isolate_data;
};


function update_isolate(form){
   /* Update isolate */
   // VALIDATE FORM INPUTS
   if(!check_name(form.country.value, 'country_check') |
      !check_date(form.mdate.value, 'date_check') |
      form.origin.value === '' |
      !check_float(form.lon.value, 'lon_check', true) |
      !check_float(form.lat.value, 'lat_check', true)
      ){
      alert("Invalid Inputs!");
      return false;
   }else{
		$.ajax({
			type : "POST",
			url : ScriptDirPath('isolate_manager.js') +"../../../server/platform/isolate/update_isolate.php", // "https://cge.cbs.dtu.dk/tools_new/server/platform/isolate/update_isolate.php",
			data : {
            IID                : form.iid.value,
            NAME               : form.name.value,
            NOTE               : form.note.value,
            MDATE              : form.mdate.value,
            TECHNOLOGY         : form.technology.value,
            IPUBLIC            : Number(form.ipublic.checked),
            COUNTRY            : form.country.value,
            REGION             : form.region.value,
            CITY               : form.city.value,
            ZIP                : form.zip.value,
            LONGITUDE          : form.lon.value,
            LATITUDE           : form.lat.value,
            LOCATION_NOTE      : form.lnote.value,
            ORIGIN             : form.origin.value,
            ORIGIN_NOTE        : form.onote.value,
            PATHOGENICITY      : form.pathogenicity.value,
            PATHOGENICITY_NOTE : form.pnote.value
			},
			timeout : 2000,
			dataType : 'xml',
			async : false,
			success : function(xml) {
				xml = $.xml2json(xml);
				if (xml.STATUS != 'ACCEPTED') {
					// AN ERROR OCCURED IN THE PHP SCRIPT
               status_check(xml);
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
   if(confirm('Are you sure you wish to delete this sample and all of its data?')){
	   $.ajax({
	   	type : "POST",
	   	url : ScriptDirPath('isolate_manager.js') +"../../../server/platform/isolate/delete_isolate.php", // "https://cge.cbs.dtu.dk/tools_new/server/platform/isolate/delete_isolate.php",
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
               status_check(xml);
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
	   	url : ScriptDirPath('isolate_manager.js') +"../../../server/platform/service/delete_run.php", // "https://cge.cbs.dtu.dk/tools_new/server/platform/service/delete_run.php",
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
               status_check(xml);
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
   deleteRow(document.getElementById("R"+ serviceID));
}

function deleteRow(r){
   /* Remove the selected row from its parent table.
   */
   if (r != null) {
      //console.log(r);
      r.parentNode.deleteRow(r.rowIndex);
   }
}

function populate_isolate_form(isolate){
   /* Populate the form with the retrieved data
   */
   if (isolate) {
      document.getElementById('files').innerHTML = isolate.files
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
   f.value = value!=='NULL'? value : '';
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

function check_float(number, id, optional){
   optional = typeof optional !== 'undefined'? optional : false;
   var validFloat = /^\d+\.\d+$/;
	//var name = document.forms['profile'].elements["username"].value;
   if(optional && number === ''){
      return true;
   }else{
      var element = document.getElementById(id);
   	if(!validFloat.test(number)){
   		//Special characters not allowed in Username
   		element.innerHTML = "Warning! A float must follow the following format: X.X Eg. 100.98";
   		return false;
   	}else{
   		element.innerHTML = "";
   		return true;
   	}
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

//function link2output(iid, date, sid, service){
//   // THIS FUNCTION CREATES AND SUBMITS A FORM TO A SERVICE OUTPUT PAGE
//   service = service.split('-');
//   // CREATING FORM
//   var form = document.createElement("form");
//   form.action = ScriptDirPath('isolate_manager.js') +'../show_result.php';
//   form.method = 'post';
//   // ADDING INPUTS
//   input2form(form, 'hidden', 'SID', sid);
//   // SUBMITTING FORM
//   document.body.appendChild(form);
//   form.submit();
//}

function update_isolate_data(isolates_old){
   /* Update the Sample overview table, by checking for the database for new
    * isolates
   */
   var isolate, idata, idata_old, isoSerRow;
   // Check for new data
   if (window.autoupdate){ window.isolates = get_isolate_data_all(); }
   var isoTab = document.getElementById('isoview'); // TABLE CONTAINING ISOLATES
   for (isolate in isolates){
      if(isolate.charAt(0)=='I'){
         // ISOLATE ENTRY WITH DATA FOUND
         idata = isolates[isolate];
         if(!(isolate in isolates_old)){
            // NEW ENTRY
            console.log('Add Sample...');
            // Add the new isolate to the list
            table_add_isolate(isoTab, idata, 1);
         }else if( isolates_old[isolate].services == 'NULL' && isolates[isolate].services != 'NULL'){
            // OLD ENTRY, NOW WITH DATA
            console.log('Update Sample...');
            // Add the new isolate to the list
            table_update_old_isolate(isoTab, idata, 1);
         }else{
            idata_old = isolates_old[isolate];
            // Check if the service table should be created 
            if(idata.services !== 'NULL' && idata_old.services == 'NULL' ){
               // ADD AN ISOLATE SERVICE ROW TO THE ISOLATE OVERVIEW TABLE
               isoSerRow = isoTab.insertRow(2);
               isoSerRow.setAttribute("id", "I"+idata.id+"_services");
               isoSerRow.style.display = 'None';
               // ADD THE SERVICE OVERVIEW
               table_add_service_table(isoSerRow, idata);
            }
            if(idata.services !== 'NULL'){
               // Check if Services are up to date
               for (run in idata.services){
                  if(run.charAt(0)=='S'){
                     // ISOLATE DATA FOUND
                     var rdata = idata.services[run];
                     if(!(run in idata_old.services)){
                        console.log('Add Run...');
                        var runTab = document.getElementById("Runtab_"+idata.id);
                        table_add_service(runTab, rdata);
                     }else{
                        // Update status
                        document.getElementById("R"+rdata.id+'_status').innerHTML = rdata.status;
                     }
                  }
               }
            }
         }
      }
   }
}

function table_add_service_table(isoSerRow, idata){
   /* Add a new isolate to the list
    */
   var run, rdata;
   // PREPARE AN ISOLATE SERVICE FIELD FOR THE SERVICE OVERVIEW TABLE
   var isoSerFld = isoSerRow.insertCell(0);
   isoSerFld.setAttribute("colspan",'7');
   // CREATE THE SERVICE OVERVIEW TABLE
   var runTab = document.createElement('table');
   // APPEND THE SERVICE OVERVIEW TABLE TO THE PREPARED ISOLATE SERVICE FIELD
   isoSerFld.appendChild(runTab);
   // MAKE A RUN TABLE HEADER
   var runRow = runTab.insertRow(-1);
   runRow.insertCell(0).innerHTML = 'Service';
   runRow.insertCell(1).innerHTML = 'Date';
   runRow.insertCell(2).innerHTML = "Status";
   runRow.insertCell(3).innerHTML = "Action";
   // SET ATTRIBUTES FOR THE RUN TABLE ETC
   runTab.setAttribute("id", "Runtab_"+idata.id);
   runTab.setAttribute("class",'runTab');
   runRow.setAttribute("class",'runhead');
   // DISPLAY ISOLATE RUNS
   for (run in idata.services){
      rdata = idata.services[run];
      table_add_service(runTab, rdata);
   }
}

function table_add_service(runTab, rdata){
   /* Add a new isolate to the list
    */
   var show_results_path = ScriptDirPath('isolate_manager.js') +'../show_result.php';
   // Handle NULL data
   rdata.service = rdata.service != 'NULL'? rdata.service : 'Nameless service';
   rdata.date = rdata.date != 'NULL'? rdata.date : 'NA';
   rdata.status = rdata.status != 'NULL'? rdata.status : 'NA';
   // ADD RUN ENTRY (ROW)
   var runRow = runTab.insertRow(-1);
   runRow.setAttribute("id", "R"+rdata.id);
   // INSERT RUN DATA
   runRow.insertCell(0).innerHTML = rdata.service;
   runRow.insertCell(1).innerHTML = rdata.date;
   var statuscell = runRow.insertCell(2);
   statuscell.innerHTML = rdata.status;
   statuscell.setAttribute("id","R"+rdata.id+'_status');
   // INSERT RUN ACTIONS
   runRow.insertCell(3).innerHTML = "<button onclick='delete_run("+rdata.id+");'>Remove</button>";
   // ADD OUTPUT REDIRECT LINK
   runRow.cells[0].setAttribute("class",'left');
   runRow.cells[0].style.font = "bold";
   runRow.cells[0].style.color = "blue";
   runRow.cells[0].style.textDecoration = "underline";
   runRow.cells[0].setAttribute("onclick","window.open('"+show_results_path+"?SID="+rdata.id+"')");
}

function table_add_isolate(isoTab, idata, pos){
   /* Add a new isolate to the list
    */
   var isoRow, isoSerRow, isoSerFld, runTab, runRow, run, rdata;
   var show_results_path = ScriptDirPath('isolate_manager.js') +'../show_result.php';
   // Handle NULL data
   idata.sample_name = idata.sample_name != 'NULL'? idata.sample_name : 'Nameless sample';
   idata.metadata.collection_date = idata.metadata.collection_date != 'NULL'? idata.metadata.collection_date : '';
   idata.metadata.country = idata.metadata.collectioncountry_date != 'NULL'? idata.metadata.country : '';
   idata.metadata.city = idata.metadata.city != 'NULL'? idata.metadata.city : '';
   idata.metadata.isolation_source = idata.metadata.isolation_source != 'NULL'? idata.metadata.isolation_source : '';
   // ADD ISOLATE ENTRY (ROW)
   isoRow = isoTab.insertRow(pos);
   isoRow.setAttribute("id", "I"+idata.id);
   // INSERT ISOLATE META DATA
   isoRow.insertCell(0).innerHTML = ''; // Expand button
   isoRow.insertCell(1).innerHTML = '<input type="checkbox" name="select" value="'+idata.id+'">'; // Checkbox
   isoRow.insertCell(2).innerHTML = '<a href="'+show_results_path+'?IID='+idata.id+'">'+idata.sample_name+'</a>';
   isoRow.insertCell(3).innerHTML = idata.metadata.collection_date;
   isoRow.insertCell(4).innerHTML = idata.metadata.country;
   isoRow.insertCell(5).innerHTML = idata.metadata.city;
   isoRow.insertCell(6).innerHTML = idata.metadata.isolation_source;
   // INSERT ISOLATE ACTION BUTTONS /&nbsp;
   isoRow.insertCell(7).innerHTML =  "<button style='display:inline;' onclick='window.open(&quot;/tools_new/client/platform/file/?IID="+idata.id+"&quot;, &quot;_blank&quot;);'>Download</button>"+
                                     "<button style='display:inline;' onclick='window.open(&quot;/services/cge/submitservice.php?iid="+idata.id+"&quot;, &quot;_blank&quot;);'>Analyse</button>"+
                                     "<button style='display:inline;' onclick='window.location = window.location.pathname+&quot;?action=edit&iid="+idata.id+"&quot;'>Edit</button>"+
                                     '<button style="display:inline;" onclick="delete_isolate('+idata.id+');">Remove</button>';
   // SETUP SERVICE TEXT
   isoRow.cells[2].setAttribute("class",'left');
   isoRow.cells[2].style.fontWeight = "bold";
   if(idata.services !== 'NULL'){ // Service run(S) found
      // ADD AN ISOLATE SERVICE ROW TO THE ISOLATE OVERVIEW TABLE
      isoSerRow = isoTab.insertRow(pos<0?pos:pos+1);
      isoSerRow.setAttribute("id", "I"+idata.id+"_services");
      isoSerRow.style.display = 'None';
      // ADD THE SERVICE OVERVIEW
      table_add_service_table(isoSerRow, idata);
      // ACTIVATE SERVICE EXPAND BUTTON
      table_turn_on_service_button(isoRow);
   }else{
      // DEACTIVATE SERVICE EXPAND BUTTON
      table_turn_off_service_button(isoRow);
   }
}

function table_turn_on_service_button(isoRow){
   /* Turn on the Sample service expand button
    */
   isoRow.cells[0].innerHTML      = '+';
   isoRow.cells[0].style.color    = "green";
   isoRow.cells[0].style.fontSize = "21";
   isoRow.cells[0].setAttribute("onclick",'visswitch("'+isoRow.id+'_services", "OFF", "table-row");switchtag(this);');
}

function table_turn_off_service_button(isoRow){
   /* Turn on the Sample service expand button
    */
   isoRow.cells[0].style.color = "grey";
   isoRow.cells[0].innerHTML = 'X';
   isoRow.cells[0].style.fontSize = "10";
   isoRow.cells[0].setAttribute("onclick",'');
}

function switchtag(field){
   /* Switch the Sample service expand button (symbol + color)
    */
   if(field.innerHTML == '+'){
      field.innerHTML = '-';
      field.style.color = "red";
   }else{
      field.innerHTML = '+';
      field.style.color = "green";
   }
}

function table_update_old_isolate(isoTab, idata, pos){
   /* Add data to an existing isolate in the list
    */
   var isoRow, isoSerRow, isoSerFld, runTab, runRow, run, rdata;
   var show_results_path = ScriptDirPath('isolate_manager.js') +'../show_result.php';
   // Handle NULL data
   idata.sample_name = idata.sample_name != 'NULL'? idata.sample_name : 'Nameless sample';
   idata.metadata.collection_date = idata.metadata.collection_date != 'NULL'? idata.metadata.collection_date : '';
   idata.metadata.country = idata.metadata.collectioncountry_date != 'NULL'? idata.metadata.country : '';
   idata.metadata.city = idata.metadata.city != 'NULL'? idata.metadata.city : '';
   idata.metadata.isolation_source = idata.metadata.isolation_source != 'NULL'? idata.metadata.isolation_source : '';
   // SELECT ISOLATE ENTRY (ROW)
   isoRow = document.getElementById("I"+idata.id);
   if(idata.services !== 'NULL'){ // Service run(S) found
      // ADD AN ISOLATE SERVICE ROW TO THE ISOLATE OVERVIEW TABLE
      isoSerRow = isoTab.insertRow(pos<0?pos:pos+1);
      isoSerRow.setAttribute("id", "I"+idata.id+"_services");
      isoSerRow.style.display = 'None';
      // ADD THE SERVICE OVERVIEW
      table_add_service_table(isoSerRow, idata);
      // ACTIVATE SERVICE EXPAND BUTTON
      table_turn_on_service_button(isoRow);
   }else{
      // DEACTIVATE SERVICE EXPAND BUTTON
      table_turn_off_service_button(isoRow);
   }
}

function populate_isolate_table(isolates){
   /* DISPLAY ISOLATE DATA
    */
   var isoTab, isolate, idata;
   isoTab = document.getElementById('isoview'); // TABLE CONTAINING ISOLATES
   for (isolate in isolates){
      if(isolate.charAt(0)=='I'){ // ISOLATE DATA FOUND
         // ADD ISOLATE ENTRY (ROW)
         idata = isolates[isolate];
         table_add_isolate(isoTab, idata, -1);
      }
   }
}

function selectallsamples(main){
   /* SELECT OR DESELECT ALL SAMPLES
    */
   var new_value = typeof(main) !== 'undefined'? main.checked : true;
   var samples = document.getElementsByName('select');
   for (var i = 0; i < samples.length; i++) { 
      samples[i].checked = new_value;
   }
}
function analyse_selected(){
   /* SELECT OR DESELECT ALL SAMPLES
    */
   var sample_str = '';
   var samples = document.getElementsByName('select');
   for (var i = 0; i < samples.length; i++) { 
      if(samples[i].checked){
         sample_str += samples[i].value + ',';
      }
   }
   window.open("/services/cge/submitservice.php?iid="+sample_str.replace(/,+$/, ""));
}

var ScriptDirPath = function (scriptname) { // ScriptDirPath('scriptname.js')
   var re = new RegExp(RegExp.escape(scriptname)+'$', 'g');
   var scripts = document.getElementsByTagName('SCRIPT');
   var path = '';
   if(scripts && scripts.length>0) {
      for(var i in scripts) {
         if(scripts[i].src && scripts[i].src.match(re)) {
            path = scripts[i].src.replace(re, '');
            break;
         }
      }
   }
   return path;
};

RegExp.escape = function(text) {
  return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
};

window.scriptdir = ScriptDirPath('scriptname.js'); // https://cge.cbs.dtu.dk/tools_new/client/platform/scripts/../../../server/platform/isolate/get_isolate.php     ../show_result.php
