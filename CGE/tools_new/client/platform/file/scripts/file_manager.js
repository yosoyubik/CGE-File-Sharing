/* File MANAGER SCRIPTS
 * This script contains function which enables user management.
 * Eg. create_user(uid, pwd, email, captcha): Creation of a new user
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

//CGE Sample meta data fetcher
function get_all_file_data(iid){
	var isolate_data;
	$.ajax({
		type : "POST",
		url : ScriptDirPath('file_manager.js') +"../../../../server/platform/file/get_file_data.php",
		data : { 'IID': iid },
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

function table_add_file(filTab, fdata, iid){
   /* Add a new isolate to the list
    */
   var filRow, filSerRow, filSerFld, runTab, runRow, run, rdata;
   var download_file = ScriptDirPath('file_manager.js') +'../../download_files.php';
   // Handle NULL data
   fdata.id = fdata.id != 'NULL'? fdata.id : 'NA';
   fdata.service = fdata.service != 'NULL'? fdata.service : 'Unknown';
   fdata.date = fdata.date != 'NULL'? fdata.date : 'NA';
   fdata.name = fdata.name != 'NULL'? fdata.name : 'NA';
   fdata.description = fdata.description != 'NULL'? fdata.description : 'NA';
   // ADD FILE ENTRY (ROW)
   filRow = filTab.insertRow(-1);
   if (fdata.service == 'Uploaded Data') {
      filRow.insertCell(0).innerHTML = '<a href="'+download_file+'?IID='+iid+'&UN='+fdata.id+'">'+fdata.service+'</a>';
   }else{
      filRow.insertCell(0).innerHTML = '<a href="'+download_file+'?FID='+fdata.id+'">'+fdata.service+'</a>';
   }
   filRow.insertCell(1).innerHTML = fdata.date;
   filRow.insertCell(2).innerHTML = fdata.name;
   filRow.insertCell(3).innerHTML = fdata.description;
   // SETUP SERVICE TEXT
   filRow.cells[0].setAttribute("class",'left');
   filRow.cells[1].setAttribute("class",'left');
   filRow.cells[2].setAttribute("class",'left');
   filRow.cells[3].setAttribute("class",'left');
   filRow.cells[0].style.fontWeight = "bold";
}

function table_turn_on_service_button(filRow){
   /* Turn on the Sample service expand button
    */
   filRow.cells[0].innerHTML      = '+';
   filRow.cells[0].style.color    = "green";
   filRow.cells[0].style.fontSize = "21";
   filRow.cells[0].setAttribute("onclick",'visswitch("'+filRow.id+'_services", "OFF", "table-row");switchtag(this);');
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

function populate_file_table(file_data){
   /* DISPLAY ISOLATE DATA
    */
   var filTab, file_num, idata;
   filTab = document.getElementById('filview'); // TABLE CONTAINING FILES
   namespan = document.getElementById('sample_name'); // SPAN CONTAINING SAMPLE NAME
   // Set Sample Name
   namespan.innerHTML = file_data.sample_name;
   // Populate File Table
   for (file_num in file_data.files){
      if(file_num.charAt(0)=='f'){ // FILE DATA FOUND
         // ADD FILE ENTRY (ROW)
         fdata = file_data.files[file_num];
         table_add_file(filTab, fdata, file_data.sample_id);
      }
   }
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
