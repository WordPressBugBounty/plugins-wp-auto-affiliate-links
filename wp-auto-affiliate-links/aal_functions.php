<?php
//Miscellaneous functions used in plugin




//Get keyoword sugestions for Add Afiliates Link Tab

function aalGetSugestions(){
	

        echo '   <a href="javascript:;" id="aal_moresug" >Show keyword suggestions >></a>
        <div id="aal_extended" style="padding: 20px;">';
        	
       /*  foreach($extended as $in => $fin) {
                if($fin!='' && $fin!=' ' && $fin!= '   ') {
                        echo '<div class="aal_sugbox">'. $fin .' ('. $times[$in] .') &nbsp;&nbsp;&nbsp;<span><a class="aal_sugkey" href="javascript:;"  title="'. $fin .'">Add >> </a></span></div>';
                }
                
             }       
             
        */
        	
        
        echo '
        </div>
        <div style="clear: both;" ></div>
       
        
        
        ';        
        
        
        
        
}





function aal_removecommonwords($string) {
	
	$commonWords = aal_commonwords();
	$newstring  = preg_replace('/\b('.implode('|',$commonWords).')\b/','',strtolower($string));	
	return $newstring;
	
}

function aal_commonwords() {
	
        $commonWords = array('a','able','about','above','abroad','according','accordingly','across','actually','adj','after','afterwards','again','against','ago','ahead','ain\'t','all','allow','allows','almost','alone','along','alongside','already','also','although','always','am','amid','amidst','among','amongst','an','and','another','any','anybody','anyhow','anyone','anything','anyway','anyways','anywhere','apart','appear','appreciate','appropriate','are','aren\'t','around','as','a\'s','aside','ask','asking','associated','at','available','away','awfully','b','back','backward','backwards','be','became','because','become','becomes','becoming','been','before','beforehand','begin','behind','being','believe','below','beside','besides','best','better','between','beyond','both','brief','but','by','c','came','can','cannot','cant','can\'t','caption','cause','causes','certain','certainly','changes','clearly','c\'mon','co','co.','com','come','comes','concerning','consequently','consider','considering','contain','containing','contains','corresponding','could','couldn\'t','course','c\'s','currently','d','dare','daren\'t','definitely','described','despite','did','didn\'t','different','directly','do','does','doesn\'t','doing','done','don\'t','down','downwards','during','e','each','edu','eg','eight','eighty','either','else','elsewhere','end','ending','enough','entirely','especially','et','etc','even','ever','evermore','every','everybody','everyone','everything','everywhere','ex','exactly','example','except','f','fairly','far','farther','few','fewer','fifth','first','five','followed','following','follows','for','forever','former','formerly','forth','forward','found','four','from','further','furthermore','g','get','gets','getting','given','gives','go','goes','going','gone','got','gotten','greetings','h','had','hadn\'t','half','happens','hardly','has','hasn\'t','have','haven\'t','having','he','he\'d','he\'ll','hello','help','hence','her','here','hereafter','hereby','herein','here\'s','hereupon','hers','herself','he\'s','hi','him','himself','his','hither','hopefully','how','howbeit','however','hundred','i','i\'d','ie','if','ignored','i\'ll','i\'m','immediate','in','inasmuch','inc','inc.','indeed','indicate','indicated','indicates','inner','inside','insofar','instead','into','inward','is','isn\'t','it','it\'d','it\'ll','its','it\'s','itself','i\'ve','j','just','k','keep','keeps','kept','know','known','knows','l','last','lately','later','latter','latterly','least','less','lest','let','let\'s','like','liked','likely','likewise','little','look','looking','looks','low','lower','ltd','m','made','mainly','make','makes','many','may','maybe','mayn\'t','me','mean','meantime','meanwhile','merely','might','mightn\'t','mine','minus','miss','more','moreover','most','mostly','mr','mrs','much','must','mustn\'t','my','myself','n','name','namely','nd','near','nearly','necessary','need','needn\'t','needs','neither','never','neverf','neverless','nevertheless','new','next','nine','ninety','no','nobody','non','none','nonetheless','noone','no-one','nor','normally','not','nothing','notwithstanding','novel','now','nowhere','o','obviously','of','off','often','oh','ok','okay','old','on','once','one','ones','one\'s','only','onto','opposite','or','other','others','otherwise','ought','oughtn\'t','our','ours','ourselves','out','outside','over','overall','own','p','particular','particularly','past','per','perhaps','placed','please','plus','possible','presumably','probably','provided','provides','q','que','quite','qv','r','rather','rd','re','really','reasonably','recent','recently','regarding','regardless','regards','relatively','respectively','right','round','s','said','same','saw','say','saying','says','second','secondly','see','seeing','seem','seemed','seeming','seems','seen','self','selves','sensible','sent','serious','seriously','seven','several','shall','shan\'t','she','she\'d','she\'ll','she\'s','should','shouldn\'t','since','six','so','some','somebody','someday','somehow','someone','something','sometime','sometimes','somewhat','somewhere','soon','sorry','specified','specify','specifying','still','sub','such','sup','sure','t','take','taken','taking','tell','tends','th','than','thank','thanks','thanx','that','that\'ll','thats','that\'s','that\'ve','the','their','theirs','them','themselves','then','thence','there','thereafter','thereby','there\'d','therefore','therein','there\'ll','there\'re','theres','there\'s','thereupon','there\'ve','these','they','they\'d','they\'ll','they\'re','they\'ve','thing','things','think','third','thirty','this','thorough','thoroughly','those','though','three','through','throughout','thru','thus','till','to','together','too','took','toward','towards','tried','tries','truly','try','trying','t\'s','twice','two','u','un','under','underneath','undoing','unfortunately','unless','unlike','unlikely','until','unto','up','upon','upwards','us','use','used','useful','uses','using','usually','v','value','various','versus','very','via','viz','vs','w','want','wants','was','wasn\'t','way','we','we\'d','welcome','well','we\'ll','went','were','we\'re','weren\'t','we\'ve','what','whatever','what\'ll','what\'s','what\'ve','when','whence','whenever','where','whereafter','whereas','whereby','wherein','where\'s','whereupon','wherever','whether','which','whichever','while','whilst','whither','who','who\'d','whoever','whole','who\'ll','whom','whomever','who\'s','whose','why','will','willing','wish','with','within','without','wonder','won\'t','would','wouldn\'t','x','y','yes','yet','you','you\'d','you\'ll','your','you\'re','yours','yourself','yourselves','you\'ve','z','zero');
	
	
	
	return $commonWords;
}


function aal_removenumbers($karray) {

	foreach($karray as $id => $key) {
		if(is_numeric($key)) {	
			unset($karray[$id]);
		}
	}
	
	$karray = array_values($karray);

	return $karray;
}

function aal_removeshortkeys($karray) {

	foreach($karray as $id => $key) {
		if(strlen($key)<6)	
			unset($karray[$id]);
	}
	
	$karray = array_values($karray);

	return $karray;
}


function aal_keyscmp($a, $b) {
		 if (str_word_count($a) == str_word_count($b)) {
		  	if(strlen($a) == strlen($b)) {
		  		return 0;
		  	}
		  	else {
		  		if(strlen($a)>strlen($b)) return -1;
		  		else return 1;	
		  	}
  			  }
  			  else {
  			  	if(str_word_count($a) > str_word_count($b)) return -1;
  			  	else return 1;	  	
  			  }
}	



function aal_add_http($url) {
	 if(substr($url, 0, 2) == "//") return $url;
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "https://" . $url;
    }
    return $url;
}


function aal_get_host_from_parse($url){
    if(strpos($url,"://")===false && substr($url,0,1)!="/") $url = "http://".$url;
    $info = parse_url($url);
    if($info)
   	if(isset($info['host'])) 
   		return $info['host'];
}


?>