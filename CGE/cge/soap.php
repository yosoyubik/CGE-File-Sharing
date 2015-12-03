<?php #! /usr/bin/php5 -q
################################################################################
#                                CGE SERVICES                                  #
################################################################################
# CONFIG VARIABLE
$serviceRoot = "/srv/www/htdocs/services/";

# STANDARD CBS PAGE TEMPLATES, always include this file
include_once('/srv/www/php-lib/cge_std-2.5.php'); // Including CGE_std clases and functions
include_once('header.php'); // Including CGE_std clases and functions
$domain = 'https://cge.cbs.dtu.dk';
$meta_headers = "<base href='$domain'>";

if (substr($_SERVER['REMOTE_ADDR'],0,3) == '10.'){$cbsUser = TRUE;}else{$cbsUser = FALSE;}

?>
<!-- START INDHOLD -->

<h3>Main Service - Pipeline</h3>
<ul>
  <a href='/services/CGEpipeline'>CGEpipeline</a> (In development)<br>
  <a href='/services/CGEpipeline/batch.php'>Batch Upload</a> (In development)<br>
</ul>

<h3>Phenotyping</h3>
<ul>
  <a href="/services/ResFinder">ResFinder</a> (Works)<br>
  <a href="/services/PathogenFinder">PathogenFinder</a> (Works)<br>
  <a href="/services/VirulenceFinder">VirulenceFinder</a> (Works)<br>
  <a href="/services/Restriction-ModificationFinder">Restriction-ModificationFinder</a> (Works)<br>
</ul>

<h3>Typing</h3>
<ul>
  <a href="/services/MLST">MLST</a> (Works)<br>
  <a href="/services/pMLST">pMLST</a> (Works)<br>
  <a href="/services/PlasmidFinder">PlasmidFinder</a> (Works)<br>
  <a href="/services/KmerFinder">KmerFinder</a> (Works)<br>
  <a href="/services/SpeciesFinder">SpeciesFinder</a> (Works)<br>
  <a href="/services/Read2Type-1.0/">Read2Type</a> (This service is not implemented on the new server)<br>
  <a href="/services/TaxonomyFinder">TaxonomyFinder</a> (This program is in development)<br>
  <a href="http://tapir.cbs.dtu.dk">Tapir</a> (This service is not implemented on the new server)<br>
</ul>

<h3>Phylogeny</h3>
<ul>
  <a href="/services/snpTree">snpTree</a>  (Works)<br>
  <a href="/services/NDtree">NDtree</a>  (Works)<br>
  <a href="/services/CSIPhylogeny">CSIPhylogeny</a>  (Works)<br>
  <a href="/services/TreeViewer-1.0">TreeViewer</a>  (Works)<br>
</ul>

<h3>Other</h3>
<ul>
  <a href="/services/Assembler">Assembler</a> (Works)<br>
  <a href="/services/PanFunPro">PanFunPro</a> (in development)<br>
  <a href="/services/MGmapper">MGmapper</a> (in development)<br>
  <a href="/services/MyDbFinder">MyDbFinder</a> (Works)<br>
  <a href="/services/GeneticDiseaseProject">GeneticDiseaseProject</a> (stalled indefinitely)<br>
  <a href="/services/NetFCM">NetFCM</a> (in development)<br>
  <a href="/services/HostPhinder">HostPhinder</a> (in development)<br>
</ul>
<!-- END OF CONTENT -->
<?php
$CGE->Piwik(14); // Printing Piwik codes!!

# Displays a standard footer; two parameters:
# First a simple headline like: "Support"
# then a list of emails like this: "('Scientific problems','foo','foo@cbs.dtu.dk'),('Technical problems','bar','bar@cbs.dtu.dk')"
include('footer.php');
?>
