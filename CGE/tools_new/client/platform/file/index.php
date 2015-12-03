<?php #! /usr/bin/php5 -q
################################################################################
#                              CGE File MANAGER                                #
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
$serviceRoot = "/srv/www/htdocs/services/";

# STANDARD CBS PAGE TEMPLATES, always include this file
include_once('/srv/www/php-lib/cge_std_tools.php'); // Including CGE_std clases and functions
// Load the CGE class (title, meta_tags, banner_path, css_paths, js_paths) '' is default
$CGE = new CGE('File Overview', '<base href="'.$domain.'" target="_blank">', '', '/tools_new/client/platform/styles/isolate_manager.css', '/tools_new/client/platform/file/scripts/file_manager.js');

# CGE MENU
# Format is: ServerName, "(Link/Path.html, 'NameOfLink'),(Link/Path.html, 'NameOfLink')"
$CGE->std_header("File Overview", "(/tools_new/client/platform/sample/,'Home'),(/services/,'Services'),(/services/cge/index.php,'Batch Upload'),(/services/CGEpipeline-1.0/map.php,'MapViewer')", FALSE); // Print the Menu

// REQUIRE THE USER TO LOGIN
if($CGE->user_is_logged_in()){
   $iid = _INPUT("IID");
   if (!is_null($iid)){
      // SHOW Single sample Downloadable files
      ?><!-- START OF CONTENT -->
      <h3>Sample Name: <span id='sample_name'>NA</span></h3>
      <table id='filview'>
         <tr><th>Service</th><th>Date</th><th>Name</th><th>Description</th></tr>
      </table>
      <br>
      <!-- GET SAMPLE DATA AND POPULATE ABOVE LIST -->
      <script type='text/javascript'>
         $(document).ready(function(){
            window.file_data = get_all_file_data(<?php echo $iid;?>);
            populate_file_table(file_data);
         })
      </script>
      <!-- END OF CONTENT --><?php
      }else{
   
   // SHOW OVERVIEW TABLE
   ?>
   <!-- END OF CONTENT --><?php
   }
}
$CGE->Piwik(15); // Printing Piwik codes!!

# STANDARD FOOTER
# First a simple headline like: "Support"
# Then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
$CGE->standard_foot("Support","('Technical problems','Martin Thomsen','mcft@cbs.dtu.dk')");
?>