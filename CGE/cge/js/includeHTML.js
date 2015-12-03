/* Made By Martin Thomsen (mcft@cbs.dtu.dk)
This function is used to include HTML pages in another HTML page by using javascript!
To use this script you will need to:
	> Include this javascript and jQuery.js on your HTML page
		<script type="text/javascript" src="/path/to/javascripts/jQuery.js"></script>
		<script type="text/javascript" src="/path/to/javascripts/includeHTML.js"></script>
	> Add a <div id="TAG_ID"></div> where you want your HTML page included
	> Edit the following function to include your tags!
*/

// Following script executes when the DOM is ready
$(document).ready(function() {
  // EDIT!
  // This is where the tags that should contain the html code should be edited
  // Eg. clientSideInclude('TAG_ID', 'VIRTUAL/PATH/TO/HTML');
  //clientSideInclude('std_head', '/cge/include_std_header.html');
  clientSideInclude('std_foot', '/cge/include_std_footer.html');
});


// THE INCLUDE SCRIPT DON'T TOUCH!
// COPIED FROM http://www.boutell.com/newfaq/creating/include.html
function clientSideInclude(id, url) {
  var req = false;
  // For Safari, Firefox, and other non-MS browsers
  if (window.XMLHttpRequest) {
    try {
      req = new XMLHttpRequest();
    } catch (e) {
      req = false;
    }
  } else if (window.ActiveXObject) {
    // For Internet Explorer on Windows
    try {
      req = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        req = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {
        req = false;
      }
    }
  }
 var element = document.getElementById(id);
 if (!element) {
  alert("Bad id " + id +
   "passed to clientSideInclude." +
   "You need a div or span element " +
   "with this id in your page.");
  return;
 }
  if (req) {
    // Synchronous request, wait till we have it all
    req.open('GET', url, false);
    req.send(null);
    element.innerHTML = req.responseText;
  } else {
    element.innerHTML =
   "Sorry, your browser does not support " +
      "XMLHTTPRequest objects. This page requires " +
      "Internet Explorer 5 or better for Windows, " +
      "or Firefox for any system, or Safari. Other " +
      "compatible browsers may also exist.";
  }
}