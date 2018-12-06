<?php
require_once("vendor/autoload.php");

// $host = 'localhost'; 
// $user = 'root';
// $password = ''; 
// $db_name = 'parser_db'; 

// $conn = mysqli_connect($host, $user, $password, $db_name);
// mysqli_query($link, "SET NAMES 'utf8'");


function searchForSymb($id, $array) {
   foreach ($array as $key => $val) {
	
       if ($val['symb'] == $id) {
           return $key+1;
       }
   }
   return null;
}

function from_preparaty_org(){
	$html = file_get_contents("http://preparaty.org/atc");
	$d = phpQuery::newDocument($html);
	$tmp = array();
	$id = 1;
	$parent = 0;
	foreach($d->find('ul li>a') as $link){
		$prev = pq($link)->parents('li')->parent()->parent()->find("b:first")->text();

		$b = pq($link)->parents('li')->find("b:first");
		$a = pq($link);
		$n = strlen($b->text());
				
		if($n==1)
			$prev = "";

		$tmp[] = array(
			"id" => $id,
			"parent" => searchForSymb($prev, $tmp), 
			"symb" => $b->text(),
			"text" => $a->text(),
			"url"  => $a->attr('href')
		);
		
		$id++;

	}
	phpQuery::unloadDocuments();
	return $tmp;
}

//$tmp = from_preparaty_org();

/*coompedium*/
function data_from_compendium_page($url, &$arr, $parent_id){
	global $conn;

	$host = "https://compendium.com.ua";
	$html = file_get_contents($url);
	$d = phpQuery::newDocument($html);
	$id = count($arr)+1;
	$flag = false;
	$tmp = array();

	foreach($d->find('.list-unstyled:first li>a') as $link){ //'.list-unstyled:first li>a'
		$flag = true;
		$b = pq($link)->find("b:first");
		$a = pq($link);
		$n = strlen($b->text());
		$tmp[] = array(
			"id" => $id,
			"parent" => $parent_id, 
			"symb" => $b->text(),
			"text" => $a->text(),
			"url"  => $a->attr('href')
		);	
		$id++;
	}

	foreach($tmp as $item){
		array_push($arr, $item);
	//	mysqli_query($conn, "INSERT INTO `atx`(`id`, `text`, `symb`, `parent_id`) VALUES (" . ($item['id']+8000) . ",'" . $item['text'] . "','" . $item['symb'] . "'," . ($item['parent'] + +8000) .")");
		data_from_compendium_page($host. $item['url'], $arr, $item['id']);		
	}
	
	phpQuery::unloadDocuments();
}

function from_compendium(){
	$host = "https://compendium.com.ua";
	$tmp = array();
	data_from_compendium_page($host . "/uk/atc/A", $tmp, 0);
	return $tmp;
}
$tmp = from_compendium();
?>

<ul>
	<?php foreach($tmp as $value): ?>
		<li>
			<a href="<?php echo($value["url"]); ?>" target="_blank">
				<b><?php echo($value["symb"]);?> </b>  <?php echo($value["id"]);?>[<?php echo($value["parent"]);?>]	<?php echo($value["text"]); ?> 
			</a>
		</li>
	<?php endforeach; ?>
</ul>