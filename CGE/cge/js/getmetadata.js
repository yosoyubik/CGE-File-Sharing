//CGE Isolate meta data fetcher
function getMetaData(uid, sid){
	var data
	if(uid != '' & sid !=''){
		$.ajax({
			type : "POST",
			url : "/cge/user/getmetadata.php", //https://cge.cbs.dtu.dk
			data : {
				USERNAME : uid,
				SESSIONID : sid
			},
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
						alert("Error With Login: "+ xml.STATUS);
					}
				}else{
               //console.log('SUCCESS: ', xml);
					data = xml;
				}
			},
			error : function(jqXHR, textStatus, errorThrown) {
				//AN ERROR OCCURED IN THE COMMUNICATION
				alert('Communication Error: '+ errorThrown +" "+ textStatus);
			}
		});
	};
	//console.log('return: ', data);
	return data;
};