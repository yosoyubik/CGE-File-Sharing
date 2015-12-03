/* MAIN CGE JAVA SCRIPT*/
function showhide(button, id){ //SHOW and HIDE BUTTON FUNCTION
   /* USAGE 1: <input type='button' class='showhide' onclick='showhide(this, "hidden_elem_id");' value='Show'>
    * USAGE 2: <input type='checkbox' onclick='showhide(this, "hidden_elem_id");' value='value'>
    */
   element = document.getElementById(id).style
   if ( button.type && button.type === 'checkbox' && button.checked ) {
		element.display='Block';
   }else if( button.value == 'Show' ){
		element.display='Block';
      button.value='Hide';
   }else if( button.value == 'Hide' ){
		element.display='None';
      button.value='Show';
   }else{
		element.display='None';
	};
};

function visswitch(id, option, style){ //SHOW / HIDE ELEMENT SWITCH FUNCTION
   style = (style) ? style : 'inline';
	element = document.getElementById(id).style
	option = (option) ? (option == 'ON' ? element.display: 'none') : 'none';
	if(element.display==option){
		element.display=style;
	}else{
		element.display='none';
	};
};

function switcher() {
	var currentElement = arguments[0],
		 len=arguments.length;
	for( var i=1; i < len; i++ ) {
		if(currentElement.value == arguments[i][1]){
			//define current(c), next(n) and next of next(n2)
			var c=i-1; 				if( c<=0 ){ c=len-1; }	// if out of bounds, setting it to the last element...
			var n=c+1, n2=c+2;	if( n >= len ){ n=1, n2=2; }else if( n2 >= len ){ n2=1; }
			//switch off i, switch on n, change text to n2 and break
			document.getElementById(arguments[c][0]).style.display = 'None'; //hide old entry tag
			document.getElementById(arguments[n][0]).style.display = 'Block'; //show next entry tag
			currentElement.value = arguments[n2][1]; //change buttontext to the next in line
			break;
		}
	}
}

function changeImage(id, color, position) {
   document.getElementById(id).style.backgroundImage = "url(/images/cge_buttons/"+ color +"_"+ position +".gif)";
};

function getParameterByName(name) {
   /* Get Parameter By Name
    * This script reads the query string of the url address, and return the
    * value of the requested field.
    */
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
function sendemail(email, name, type, service, version) {
   /* Send Email
    * This script parses the input email, name, etc. and makes a mail to
    * location change to a mail, with predefined subject and body.
    * It also reads the jobid query string and adds it to the subject header...
    */
   // Extract Jobid from URL query
   var jobid = getParameterByName('jobid');
   // Set mail details
   var subject = type +" with "+ service +"-"+ version +" ("+ jobid +")";
   var body = "Dear "+name+",\n\nIn regards to my job: "+document.URL+"\n\n";
   // Execute 'mail to' request
   contact(email, subject, body);
}

function contact(email, subject, body) {
   /* Send Email
    * This script parses the input email, name, etc. and makes a mail to
    * location change to a mail, with predefined subject and body.
    * It also reads the jobid query string and adds it to the subject header...
    */
   // Decrypt email encryption
   email = email.split('&#64;').join('@').split('&#46;').join('.');
   //email = email.replace('&#64;', '@').replace('&#46;', '.');
   // Encode special HTML chars /abc/g
   subject = subject.split(' ').join('%20');
   body = body.split(' ').join('%20').split('\n').join('%0D%0A');
   // Send mail setup details
   document.location.href = "mail" + "to:"+email+"?subject="+subject+"&body="+body+"";
}