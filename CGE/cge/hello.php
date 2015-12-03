<!DOCTYPE html>
<html lang="en">

<?php
  require 'header.php';
  include '/var/www/php/cge_std-2.0.php'; // Including CGE_std clases and functions
?>

<!-- include_once('/var/www/php/cge_std-2.0.php'); // Including CGE_std clases and functions -->
<div id='userinfo' style='width:1024px;float:left;'>
<!-- USER INFO -->

</div>
<div id='runs' style='width:1024px;float:left;'>
<!-- RUN INFO -->
<table><tr>
<?php
#RETRIEVING USER DATA FROM DATABASE
$last_login = time();
$services = array();
if(isset($_SESSION['USERNAME'])){
	// CONNECT TO THE DATABASE
	$mysqli = new mysqli('cge', 'cgeclient', 'www', 'cge');
	if (!mysqli_connect_errno()) {
		//CHECK FOR CORRECT USERNAME AND PASSWORD
		$stmt = $mysqli->prepare("SELECT service, run_id, date FROM runs WHERE user_id = ?");
		$stmt->bind_param('s', $USERNAME);
		$USERNAME = $_SESSION['USERNAME'];
		//EXECUTE PREPARED STATEMENT
		$stmt->execute();
		// BIND RESULT VARIABLES
		$stmt->bind_result($service, $run_id, $date);
		// FETCH VALUES
		$i=1;
		while($stmt->fetch()){
			$tmp = split('-',$service);
			$curService = $tmp[0];
			$curVersion = $tmp[1];
			$name = "Run ".$i." (".$date.")";
			if(!isset($services[$curService])){$services[$curService] = array();};
			$services[$curService][] = array($curVersion,$run_id,$name);
			$i++;
		}
		if (preg_match("/(\d{2})\/(\d{2})\-(\d{4}) (\d{2})\:(\d{2})\:(\d{2})/", $last_login, $regs)){
		   $last_login = mktime($regs[4],$regs[5],$regs[6],$regs[2],$regs[1],$regs[3]);
		}
		// CLOSE STATEMENT
		$stmt->close();
		// CLOSE DATABASE
		$mysqli->close();
	}
}


$i=1;
foreach ($services as $service => $value) {
	 if($i>5){echo"</tr><tr>";$i=1;}; # max 5 Services per line
    echo "<td style='width:200px;vertical-align:top;'><table style='margin-bottom: 20px;'><tr><td style='border-bottom: 1px dashed #000;text-align:center;font-weight:bold;font-size:20;'>$service</td></tr><tr height='5'><td>&nbsp;</td></tr>\n";
	 foreach ($value as $run) { echo runLink($service, $run[0], $run[1], $run[2]); }
	 echo "</table></td>";
	 $i++;
}
?>
</tr></table>

  <!-- page content -->
  <div class="row">
    <div class="col-xs-2 small">
      <h4><a href="services.html">Services</a></h4>
      <section>
        <h6 class="margin10px"><b>Phenotyping:</b></h6>
        <ul>
          <li>
            Identifcation of acquired antibiotic resistance genes.
            <a href="https://cge.cbs.dtu.dk/services/ResFinder/">
              <span>
                ResFinder
              </span>
            </a>
          </li>
          <li>
            Prediction of a bacteria's pathogenicity towards human hosts.
            <a href="https://cge.cbs.dtu.dk/services/PathogenFinder/">
              <span>
                PathogenFinder
              </span>
            </a>
          </li>
          <li>
            Identifcation of acquired virulence genes.
            <a href="https://cge.cbs.dtu.dk/services/VirulenceFinder/">
              <span>
                VirulenceFinder
              </span>
            </a>
          </li>
        </ul>
      </section>
      <section>
        <h6 class="margin10px"><b>Typing:</b></h6>
        <ul>
          <li>
            Multi Locus Sequence Typing (MLST) from an assembled genome or from a set of reads
            <a href="http://cge.cbs.dtu.dk/services/MLST/">
              <span>
                MLST
              </span>
            </a>
          </li>
          <li>
            PlasmidFinder identifies plasmids in total or partial sequenced isolates of bacteria.
            <a href="http://cge.cbs.dtu.dk/services/PlasmidFinder/">
              <span>
                PlasmidFinder
              </span>
            </a>
          </li>
          <li>
            Multi Locus Sequence Typing (MLST) from an assembled plasmid or from a set of reads
            <a href="https://cge.cbs.dtu.dk/services/pMLST/">
              <span>
                pMLST
              </span>
            </a>
          </li>
          <li>
            Prediction of bacterial species using a fast K-mer algorithm.
            <a href="https://cge.cbs.dtu.dk/services/KmerFinder/">
              <span>
                KmerFinder
              </span>
            </a>
          </li>
          <li>
            Prediction of bacterial species using the S16 ribosomal DNA sequence.
            <a href="https://cge.cbs.dtu.dk/services/SpeciesFinder/">
              <span>
                SpeciesFinder
              </span>
            </a>
          </li>
          <li>
            Fast prediction of bacterial taxonomy.
            <a href="https://cge.cbs.dtu.dk/services/Reads2Type/">
              <span>
                Reads2Type
              </span>
            </a>
          </li>
          <li>
            Fast DNA search engine.
            <a href="https://tapir.cbs.dtu.dk/">
              <span>
                Tapir
              </span>
            </a>
          </li>
        </ul>
      </section>
      <section>
        <h6 class="margin10px"><b>Phylogeny:</b></h6>
        <ul>
          <li>
            SNPs phylogenetic tree from assembled genomes or sets of reads.
            <a href="https://cge.cbs.dtu.dk/services/snpTree/">
              <span>
                snpTree
              </span>
            </a>
          </li>
          <li>
            NDtree constructs phylogenetic trees from Single-End or Pair-End FASTQ files.
            <a href="https://cge.cbs.dtu.dk/services/NDtree/">
              <span>
                NDtree
              </span>
            </a>
          </li>
        </ul>
      </section>
    </div>
    <div class="col-xs-8">
      <img border="0" alt="CGE" src="http://www.genomicepidemiology.org/CGE2.jpg" height="100%" width=100%>
      <h3><b>Welcome to the Center for Genomic Epidemiology</b></h3>
      <section>
        <p>
          The cost of sequencing a bacterial genome is $50 and is expected to decrease further in the near future and the equipment needed cost less than $150 000. Thus, within a few years all clinical microbiological laboratories will have a sequencer in use on
          a daily basis. The price of genome sequencing is already so low that whole genome sequencing will also find worldwide application in human and veterinary practices as well as many other places where bacteria are handled. In Denmark alone this
          equals more than 1 million isolates annually in 15-20 laboratories and globally up to 1-2 billion isolates per year. The limiting factor will therefore in the future not be the cost of the sequencing, but how to assemble, process and handle
          the large amount of data in a standardized way that will make the information useful, especially for diagnostic and surveillance.
        </p>
        <p>
          The aim of this center is to provide the scientific foundation for future internet-based solutions where a central database will enable simplification of total genome sequence information and comparison to all other sequenced including spatial-temporal
          analysis. We will develop algorithms for rapid analyses of whole genome DNA-sequences, tools for analyses and extraction of information from the sequence data and internet/web-interfaces for using the tools in the global scientific and medical
          community. The activity is being expanded to also include other microorganisms, such as vira and parasites as well as metagenomic samples.
        </p>
      </section>
    </div>
    <div class="col-xs-2 small">
      <h4><a href="news.html">News</a></h4>
      <section>
        <h6><b>Benchmarking of Methods for Genomic Taxonomy</b></h6>
        <h7>April 2014</h7>
        <p>
          How to optimally determine taxonomy from whole genome sequences.
          <a href="https://www.ncbi.nlm.nih.gov/pubmed/24574292">Link to article...</a>
        </p>

        <h6><b>CGE tools applied for bacteriophage characterization</b></h6>
        <h7>March 2014</h7>
        <p>
          Applying the ResFinder and VirulenceFinder web-services for easy identification of acquired antibiotic resistance and E. coli virulence genes in bacteriophage and prophage nucleotide sequences.
          <a href="http://www.ncbi.nlm.nih.gov/pubmed/24575358">Link to article...</a>
        </p>


        <h6><b>Evaluation of Whole Genome Sequencing for Outbreak Detection of Salmonella enterica</b></h6>
        <h7>March 2014</h7>
        <p>
          We evaluated WGS for outbreak detection of Salmonella enterica including different approaches for analyzing and comparing with a traditional typing, PFGE.
          <a href="http://www.plosone.org/article/info%3Adoi%2F10.1371%2Fjournal.pone.0087991">Link to article...</a>
        </p>

        <h6><b>Low-bandwidth and non-compute intensive remote identification of microbes from raw sequencing reads</b></h6>
        <h7>January 2014</h7>
        <p>
          Cheap dna sequencing may soon become routine not only for human genomes but also for practically anything requiring the identification of living organisms from their dna.
          <a href="http://www.ncbi.nlm.nih.gov/pubmed/?term=34.%09Gautier+L%2C+Lund+O.+Low-bandwidth+and+non-compute+intensive+remote+identification+of+microbes+from+raw+sequencing+reads.+PLoS+One.+2013+Dec+31%3B8(12)%3Ae83784.+doi%3A+10.1371%2Fjournal.pone.0083784.+PubMed+PMID%3A+24391826%3B+PubMed+Central+PMCID%3A+PMC3877093.">Link to article...</a>
        </p>

        <h6><b>Pathogenic bacteria identified and described directly from clinical samples within a day</b></h6>
        <h7>December 2013</h7>
        <p>
          The CGE project has demonstrated how, within the space of a single day, pathogenic bacteria can be identified and described directly from clinical samples. In the long term, this will help doctors treat patients more quickly with the right medicine, thus
          reducing periods of illness and saving lives.
          <a href="http://www.ncbi.nlm.nih.gov/pubmed/?term=Hasman+H%2C+Saputra+D%2C+Sicheritz-Ponten+T%2C+Lund+O%2C+Svendsen+CA%2C+Frimodt-M%C3%B8ller">Link to article...</a>
        </p>

        <h6><b>Global Microbial Identifier</b></h6>
        <h7>December 2012</h7>
        <p>
          The initiative 'Global Microbial Identifier' focuses on the use of genome sequencing techniques in a global system for microbiological identification and epidemiological surveillance.
          <a href="http://www.g-m-i.org/">Visit the homepage for further information</a>
        </p>
      </section>
    </div>
  </div>

  <?php
    require 'footer.php';
  ?>

</html>
