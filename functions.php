<?php
namespace {
ini_set('default_socket_timeout', 10);
const latCyrThresh = 50;
const cyrCyrThresh = 90;


// General
//   Shorthands
function checkPostFor($keys) {foreach($keys as $key) {if (!isset($_POST[$key])) {return false;}} return true;}
function nCopiesOf($n,$of) {return $n.' cop'.($n>1 ? 'ies' : 'y').' of "'.$of.'"';}

//   HTTP
function httpPost($url,$data=Null) {
	if ($data!=Null) {
		$postData = http_build_query($data);
		$cont = array('http' => array('method' => 'POST',
			'header' => 'Content-Type: application/x-www-form-urlencoded\r\n',
			'content' => $postData));
		$cont = stream_context_create($cont);
		$result = file_get_contents($url,false,$cont);
	}
	else {$result = file_get_contents($url);}
	if (!$result) {throw new Exception('Error opening remote file.');}

	return $result;
}
function loadDoc($url) {
	$str = httpPost($url);
	$doc = new \DOMDocument();
	if (@$doc->loadHTML($str)) {return $doc;}
	else {throw new \DOMException('File could not be parsed: '.$url);}
}
function ajax_return($status=1,$msg='',$redir=null) {
	echo json_encode(array('status'=>$status,'msg'=>$msg,"redir"=>$redir));
	exit();
}

//   Analysis
function parseTable($str) {
	$pattern = '/<tr>([^\\n]*)<\\/tr>/';
	if (preg_match_all($pattern,$str,$matches)) {
		$res = array();
		foreach ($matches[1] as $match) {
			if (!preg_match('/<th[^>]*>(.*)<\\/th>/',$match,$key)) {continue;}
			if (!preg_match('/<td[^>]*>(.*)<\\/td>/',$match,$value)) {continue;}
			$res[$key[1]]=$value[1];
		}
		return $res;
	}
	else {return false;}
}
function parseDivs($top,$by='class') {
	$data = array();
	foreach ($top->childNodes as $child) {
		if ($child->nodeName == 'div') {
			$data[$child->getAttribute($by)][] = parseDivs($child);
		} else {
			$innerHTML = $child->ownerDocument->saveHTML($child);
			if (preg_match('/[^\s]/',$innerHTML)) {$data[] = $innerHTML;}
		}
	}
	return $data;
}
function parseName($str) {
	$names = explode(',',$str);
	foreach($names as $key => $val) {$names[$key]='"'.htmlspecialchars(trim($val)).'"';}
	if (!isset($names[1])) {$names[]='NULL';}
	return $names;
}


// ISBN
function checksumISBN10($ISBN) {
	$sum = 0;
	for ($i=0;$i<9;$i++) {
		$sum += intval($ISBN[$i])*(10-$i);
	}
	$ch = 11-$sum%11;
	if ($ch==11) {$ch='0';}
	else if ($ch==10) {$ch='X';}
	else {$ch=strval($ch);}
	return $ch;
}
function checksumISBN13($ISBN) {
	$sum = 0;
	for ($i=0;$i<12;$i++) {
		$sum += intval($ISBN[$i])*(($i%2)*2+1);
	}
	$ch = 10-$sum%10;
	if ($ch==10) {$ch='0';}
	else {$ch=strval($ch);}
	return $ch;
}
function ISBNto13($ISBN) {
	if (!($ISBN and verifyISBN($ISBN)) or strlen($ISBN)==13) {return $ISBN;}
	$ISBN = '978'.substr($ISBN,0,-1);
	$ISBN.= checksumISBN13($ISBN);
	return $ISBN;
}
function ISBNto10($ISBN) {
	if (!($ISBN and verifyISBN($ISBN)) or strlen($ISBN)==10) {return $ISBN;}
	$ISBN = substr($ISBN,3,-1);
	$ISBN.= checksumISBN10($ISBN);
	return $ISBN;
}
function fixISBN($ISBN) {
	if (strlen($ISBN)==10) {return substr($ISBN,0,-1).checksumISBN10($ISBN);}
	else if (strlen($ISBN)==13) {return substr($ISBN,0,-1).checksumISBN13($ISBN);}
	else {return false;}
}
function matchISBN($ISBN) {return preg_match('/\d{9}[\dXx]|\d{13}/',$ISBN);}
function cleanISBN($ISBN) {return preg_replace('/X(.)/','$1',preg_replace('/[^\dX]/','',$ISBN));}
function dashISBN_specs($ISBN,$start,$i) {
	$specs = array();
	for ($j=1; $j<9-$i; $j++) {
		$specs[] = substr($ISBN,$start+$i,$j).'-'.substr($ISBN,$start+$i+$j,-1);
	}
	return $specs;
}
function dashISBN($ISBN,$bg=true) {
	if (strlen($ISBN)==13) {
		$ean = substr($ISBN,0,3).'-';
		$start = 3;
	} else {$ean = ''; $start = 0;}
	$vars = array();
	$chk = '-'.substr($ISBN,-1);
	if ($bg and substr($ISBN,$start,3)=='954') {
		$ean = $ean.'954-';
		foreach (dashISBN_specs($ISBN,$start,3) as $spec) {$vars[] = $ean.$spec.$chk;}
		return $vars;
	}
	for ($i=1; $i<6; $i++) {
		$group = substr($ISBN,$start,$i).'-';
		foreach (dashISBN_specs($ISBN,$start,$i) as $spec) {$vars[] = $ean.$group.$spec.$chk;}
	}
	return $vars;
}
function verifyISBN($ISBN) {
	if (preg_match('/^[0-9]{9}[0-9Xx]$/',$ISBN)) {
		$ch = checksumISBN10($ISBN);
		if ($ch==strtoupper($ISBN[9])) {return true;}
		else {return false;}
	}
	else if (preg_match('/^[0-9]{13}$/',$ISBN)) {
		$ch = checksumISBN13($ISBN);
		if ($ch==$ISBN[12]) {return true;}
		else {return false;}
	}
	else {return false;}
}


// Database interaction
//   Exists
function existsISBN($ISBN) {
	$where = verifyISBN($ISBN) ? '13="'.ISBNto13($ISBN).'"' : '="'.$ISBN.'"';
	return sendQuery('SELECT bookID FROM books WHERE ISBN'.$where);
}
function existsCobissID($cID) {return sendQuery('SELECT bookID FROM books WHERE service="cobiss" and permaID='.$cID);}

//   Search
function searchWeight($words, $fields, $min=2) {
	if(gettype($fields)=='string') {$allfields = $fields;}
	else {
		$allfields = 'CONCAT_WS(" ",NULL';
		foreach ($fields as $field) {$allfields .= ','.$field;}
		$allfields .= ')';
	}

	$string = 'IFNULL(';
	$string.= '(0';
	$repl = 'LOWER('.$allfields.')';
	$c=0;
	foreach ($words as $word) {
		if (strlen(utf8_decode($word))>$min) {
			$word = mb_strtolower($word);
			$string .= ' + (CHAR_LENGTH('.$allfields.')-CHAR_LENGTH(REPLACE(lower('.$allfields.'),"'.$word.'","")))/'.strlen(utf8_decode($word));
			$repl = 'REPLACE('.$repl.',"'.$word.'","")';
			$c++;
		}
	}
	$string.=') * (1-(CHAR_LENGTH('.$repl.')/CHAR_LENGTH('.$allfields.')))';
	$string .=' + (0.5*(CHAR_LENGTH('.$repl.') - CHAR_LENGTH(REPLACE('.$repl.',"  ",""))) + IF(INSTR('.$repl.'," ")=1,1,0))';
	$string .= ',0) AS weight';
	return $string;
}
function buildWhere($words,$fields,$min=2) {
	$where = '0';
	if (gettype($fields)=='string') {$fields = [$fields];}
	foreach ($words as $word) {
		if (strlen(utf8_decode($word))>$min) {
			foreach ($fields as $field) {
				$where .= ' OR ' . $field . ' LIKE "%' . $word . '%"';
			}
		}
	}
	return $where;
}
function authSearch($input) {
	$input = preg_replace('/\s+/',' ',str_replace([',',';'],' ',str_replace('\\','',trim(htmlspecialchars($input)))));
	if (strlen(utf8_decode($input))<3) {return false;}

	$fields = array('authFirst','authLast','authFirstLat','authLastLat');
	$words = explode(' ',$input);
	$weight1 = substr(searchWeight($words,array('authFirst','authLast')),0,-10);
	$weight2 = substr(searchWeight($words,array('authFirstLat','authLastLat')),0,-10);

	$where = buildWhere($words,$fields,2);
	$query = 'SELECT authorID,authorString, '.$weight1.' + '.$weight2.' as weight FROM authors_ws WHERE '.$where.' ORDER BY weight DESC';
	$q_result = sendQuery($query);
	return $q_result;
}

//   Add
function prepData($data) {
	if (isset($data['ISBN'][9])) {$data['ISBN'][9]=strtoupper($data['ISBN'][9]);}

	$data['ISBN13'] = ISBNto13($data['ISBN']);

	$data['ISBN'] = $data['ISBN'] ? '"'.$data['ISBN'].'"' : 'NULL';
	$data['ISBN13'] = $data['ISBN13'] ? '"'.$data['ISBN13'].'"' : 'NULL';
	$data['title'] = $data['title'] ? '"'.htmlentities($data['title']).'"' : 'NULL';
	$data['seriesID'] = isset($data['seriesID']) ? intval($data['seriesID']) : false;
	$data['seriesNum'] = isset($data['seriesNum']) ? intval($data['seriesNum']) : false;
	$data['year'] = $data['year'] ? intval($data['year']) : 'NULL';
	$data['locID'] = $data['locID'] ? intval($data['locID']) : 1;
	$data['service'] = isset($data['service']) ? '"'.$data['service'].'"' : 'NULL';
	$data['permaID'] = isset($data['permaID']) ? '"'.$data['permaID'].'"' : 'NULL';

	return $data;
}
function add($data) {
	$data = prepData($data);

	$query = 'INSERT INTO books ';
	$query.= '(ISBN,ISBN13,title,year,locID,service,permaID,addedDate) ';
	$query.= 'VALUES (';
	$query.= $data['ISBN'].', ';
	$query.= $data['ISBN13'].', ';
	$query.= $data['title'].', ';
	$query.= $data['year'].', ';
	$query.= $data['locID'].', ';
	$query.= $data['service'].', ';
	$query.= $data['permaID'].', ';
	$query.= 'CURDATE())';

	sendQuery($query);

	$bookID = $GLOBALS['con']->insert_id;

	$query = 'INSERT INTO authorship ';
	$query.= '(bookID,authorID) ';
	$query.= 'VALUES ';
	foreach ($data['authorIDs'] as $authorID) {
		$query.='('.$bookID.','.$authorID.'),';
	}
	$query = substr($query,0,-1);
	sendQuery($query);

	$query = 'INSERT INTO language ';
	$query.= '(bookID,langID) ';
	$query.= 'VALUES ';
	foreach ($data['langIDs'] as $langID) {
		$query.='('.$bookID.','.$langID.'),';
	}
	$query = substr($query,0,-1);
	sendQuery($query);

	if ($data['seriesID']) {
		if ($data['seriesNum']===false) {$data['seriesNum'] = 'NULL';}
		$query = 'INSERT INTO series_in VALUES (NULL,'.$bookID.','.$data['seriesID'].','.$data['seriesNum'].')';
		sendQuery($query);
	}

	return $bookID;
}


// Input -- !FIX!
function dealISBN($ISBN) {
	if (existsISBN($ISBN)) {echo $ISBN.' already exists. '; return false;}

	$data = getData(array('ISBN'=>$ISBN),true);
	if ($data) {add($data); return true;}
	else {return false;}
}
function dealCobissID($cID) {
	if (existsCobissID($cID)) {echo $cID.' already exists. '; return false;}

	$data = cobiss\getData(false,$cID);
	var_dump($data);
//	if ($data) {add($data); return true;}
//	else {return false;}
	return false;
}


// Output
function resultSetTable($q_result) {
	$res = '<table border=1>';
	$res.= '<tr>';
	foreach (mysqli_fetch_fields($q_result) as $h) {
		$res.= '<th>'.$h->name.'</th>';
	}
	$res.= '</tr>';

	foreach ($q_result as $line) {
		$res.= '<tr>';
		foreach ($line as $key => $val) {
			if ($key=='permaID' and $val) {$res.= '<td><a target="blank" href="'.SERVICES_PERMALINK[$line['service']].$val.'">more</a></td>';}
			else {$res.= '<td>'.$val.'</td>';}
		}
		$res.= '</tr>';
	}
	$res.= '</table>';
	
	return $res;
}
function bookActions($book,$class='') {
	$div = '<div class="actions '.$class.'">';
		$div.= '<form class="actionForm" method="post" action="book.php">';
			$div.= '<input name="exec" value="1" hidden>';
			$div.= '<input name="act" hidden>';
			$div.= '<input name="count" hidden>';
			$div.= '<input name="msg" hidden>';
			$div.= '<input name="ID" value="' . $book['bookID'] . '" hidden>';
			$div.= '<button value="add" onclick="setCountVal(this); return false;">Добави екземпляр</button>';
			$div.= '<button value="rem" onclick="setCountVal(this,'.($book['count']-$book['lended']).'); return false;">Премахни екземпляр</button>';
			$div.= '<button value="lend" onclick="setCountVal(this,'.($book['count']-$book['lended']).(($book['lendedComment']) ? ',\''.$book['lendedComment'].'\'' : '').'); return false;">Отдай екземпляр</button>';
			$div.= ($book['lended']>0) ?
				'<button value="ret" onclick="setCountVal(this,'.$book['lended'].(($book['lendedComment']) ? ',\''.$book['lendedComment'].'\'' : '').'); return false;">Върни екземпляр</button>' : '';
		$div.= '</form>';
		$div.= '<div class="actionContainer">';
			$div.= '<div class="bookIcon bookplus" title="Добави екземпляр" onclick="this.parentNode.parentNode.firstChild.children[5].click();"></div>';
			$div.= '<div class="bookIcon bookminus" title="Премахни екземпляр" onclick="this.parentNode.parentNode.firstChild.children[6].click();"></div>';
			$div.= '<div class="bookIcon bookout" title="Отдай екземпляр" onclick="this.parentNode.parentNode.firstChild.children[7].click();"></div>';
			$div.= ($book['lended'] > 0) ? '<div class="bookIcon bookin" title="Върни екземпляр" onclick="this.parentNode.parentNode.firstChild.children[8].click();"></div>' : '';
		$div.= '</div>';
	$div.= '</div>';
	return $div;
}
function multipleBooks_info($book) {
	$div = '<div class="info">';
		$div.= '<div class="addrow" title="автор"><div class="labelIcon labelauthor"></div><div class="data">' .$book['author'].'</div></div>';
		$div.= $book['year'] ?
				'<div class="addrow" title="година"><div class="labelIcon labelyear"></div><div class="data">' .$book['year'].'</div></div>' : '';
		$div.= '<div class="addrow" title="език"><div class="labelIcon labellang"></div><div class="data">' .$book['langName'].'</div></div>';
		$div.= '<div class="addrow" title="местоположение"><div class="labelIcon labelloc"></div><div class="data">' .$book['locName'].'</div></div>';
		$div.= $book['ISBN'] ?
				'<div class="addrow" title="ISBN"><div class="labelIcon labelisbn"></div><div class="data">' .$book['ISBN'].'</div></div>' : '';
		$div.= '<div class="addrow" title="екземпляри"><div class="labelIcon labelcopies"></div><div class="data">' .$book['count'].'</div></div>';
		$div.= $book['lendedComment'] ?
				'<div class="addrow" title="коментар"><div class="labelIcon labelcomment"></div><div class="data">' .$book['lendedComment'].'</div></div>' : '';
	$div.= '</div>';
	return $div;
}
function multipleBooks($q_result,$action=true) {
	$res = '';
	foreach ($q_result as $book) {
		if ($book['lended']) {$book['count'] .= ' - ' . $book['lended'] . ' = ' . ($book['count'] - $book['lended']);}
		
		$res.= '<div id="book_'.$book['bookID'].'" class="book row">';
			$res.= '<div class="title" title="'.$book['title'].'">';
				$res.= '<div class="checkContainer"><input type="checkbox" name="bookIDs[]" value="'.$book['bookID'].'"></div>';
				$res.= '<div class="titleContainer">'.$book['title'].'</div>';
			$res.= '</div>';
			$res.= '<div class="additional">';
				$res.= multipleBooks_info($book);
				if ($action) {$res.= bookActions($book);}
			$res.= '</div>';
		$res.= '</div>';
	}
	return $res;
}
function multipleAuthors($q_result) {
	$res = '<div id="authors">';
	foreach ($q_result as $auth) {
		$name = $auth['authLast'] ? ($auth['authFirst'].' '.$auth['authLast']) : $auth['authFirstLat'].' '.$auth['authLastLat'];

		$res.= '<div class="author row">';
			$res.= '<a href="search.php?authorID='.$auth['authorID'].'" target="_blank"><div class="title">'.$name.'&nbsp;<img class="ext" src="/Images/icons/ext.png"></div></a>';
		$res.= '</div>';
	}
	$res.= '</div>';
	return $res;
}
function singleBookTable($data) {
	$count = $data['count'];
	if ($data['lended']) {
		$count .= ' - '.$data['lended'].' (отдадени) = '.($count - $data['lended']).' (в наличност)';
	}

	$table = '';
	$table .= '<div class="bookData">';
	$table .= '<div class="row"><div class="infoLabel">Заглавие</div><div class="dataDiv">'.$data['title'];
	$table .= $data['permaID'] ? '&nbsp;<a href="'.\SERVICES_PERMALINK[$data['service']].$data['permaID'].'" target="blank"><img class="ext" src="/Images/icons/ext.png"></a>' : '';
	$table .='</div></div>';
	$table .= '<div class="row"><div class="infoLabel">Автор</div><div class="dataDiv">'.$data['author'].'</div></div>';
	$table .= '<div class="row"><div class="infoLabel">Местоположение</div><div class="dataDiv flex">'.$data['locName'].'<div id="moveButton" class="moveButton"><input value="'.$data['bookID'].'"></div></div></div>';
	$table .= '<div class="row"><div class="infoLabel">Година</div><div class="dataDiv">'.$data['year'].'</div></div>';
	$table .= '<div class="row"><div class="infoLabel">Език</div><div class="dataDiv">'.$data['langName'].'</div></div>';
	$table .= $data['ISBN'] ? '<div class="row"><div class="infoLabel">ISBN</div><div class="dataDiv">'.$data['ISBN'].'</div></div>' : '';
	$table .= '<div class="row"><div class="infoLabel">Екземпляри</div><div class="dataDiv">'.$count.'</div></div>';
	$table .= ($data['lendedComment']) ?
		'<div class="row"><div class="infoLabel">Коментар</div><div class="dataDiv">'.$data['lendedComment'].'</div></div>' : '';
	return $table;
}


function getData($input,$toadd=false) {
	if (isset($input['cID'])) {return \cobiss\getData(false,$input['cID'],$toadd);}
	else {
//		if ($toadd) {
			($data = \inprint\getData($input['ISBN'],$toadd));
//			($data = \cobiss\getData($input['ISBN'],false,$toadd)) or
//			($data = \isbndb\getData($input['ISBN']));
			return $data;
//		} else {return \isbndb\getData($input['ISBN']);}
	}
}
}

// Services
namespace cobiss {
	function detAuthorString($data) {
		if ($data['auth']=='id=1') {return 'неизвестен';}
		else {
			return preg_replace('/\s+/', ' ', str_replace(array('<br>', '<br/>'), '; ', strip_tags($data['auth'], '<br>')));
		}
	}
	function detAuth($data) {
		if ($data['auth']=='id=1') {return array(1);}

		$pattB = '/(?:^|(?:[^=]{2}))<a[^>]*>([^<]+)<\\/a> = <a[^>]*>([^<]+)<\\/a>/m';
		$pattC = '/(?:^|(?:[^=]{2}))<a[^>]*>([^<A-Za-z]+)<\\/a>(?:[^ ]|$)/m';
		$pattL = '/(?:^|(?:[^=]{2}))<a[^>]*>([^<А-я]+)<\\/a>(?:[^ ]|$)/m';

		$existsB = preg_match_all($pattB,$data['auth'],$matchesB,PREG_SET_ORDER);
		$existsC = preg_match_all($pattC,$data['auth'],$matchesC);
		$existsL = preg_match_all($pattL,$data['auth'],$matchesL);

		if ($existsB or $existsC or $existsL) {
			$auth = array();
			$namesC = array();
			foreach ($matchesB as $match) {
				$namesCyr = parseName($match[1]);
				$namesLat = parseName($match[2]);
				$name = $namesCyr[1].' '.$namesCyr[0];

				$cont = false;
				foreach ($namesC as $nameC) {
					similar_text($name,$nameC['name'],$p);
					if ($p > cyrCyrThresh) {$cont=true;}
				}
				if ($cont) {continue;}

				$query = 'SELECT authorID,authFirstLat,authLastLat FROM authors WHERE authFirst='.$namesCyr[1].' AND authLast='.$namesCyr[0];
				$q_result = sendQuery($query);

				if (!$q_result) {
					$query = 'INSERT INTO authors VALUES (0,'.$namesCyr[1].','.$namesCyr[0].','.$namesLat[1].','.$namesLat[0].')';
					sendQuery($query);
					$ID = $GLOBALS['con']->insert_id;
				}
				else {
					$row = $q_result->fetch_row();
					$ID = $row[0];
					if ($row[1]==NULL and $row[2]==NULL) {
						$query = 'UPDATE authors SET authFirstLat='.$namesLat[1].', authLastLat='.$namesLat[0].' WHERE authorID='.$ID;
						sendQuery($query);
					}
				}
				$auth[] = $ID;
				$namesC[] = array('name' => $name, 'ID' => $ID);

//			$nameC = $namesC[1].' '.$namesC[0];
//			$nameL = $namesL[1].' '.$namesL[0];

//			$trans=str_replace('ы','и',transliterator_transliterate('bg',$nameL));
//			similar_text($nameC,$trans,$p);

//			echo $nameC.' = '.$nameL.' ('.$trans.') ('.$p.'); ';
			}

			foreach ($matchesC[1] as $match) {
				$names = parseName($match);
				$name = $names[1].' '.$names[0];
				$name = str_replace(array('"','NULL'),'',$name);

				$cont = false;
				foreach ($namesC as $nameC) {
					similar_text($name,$nameC['name'],$p);
					if ($p > \cyrCyrThresh) {$cont=true;}
				}
				if ($cont) {continue;}

				$query = 'SELECT authorID FROM authors WHERE authFirst='.$names[1].' AND authLast='.$names[0];
				$q_result = \sendQuery($query);

				if (!$q_result) {
					$query = 'INSERT INTO authors VALUES (0,'.$names[1].','.$names[0].',NULL,NULL)';
					\sendQuery($query);
					$ID = $GLOBALS['con']->insert_id;
				}
				else {$ID = $q_result->fetch_row()[0];}
				$auth[] = $ID;
				$namesC[] = array('name' => $name, 'ID' => $ID);
//			echo $name.'; ';
			}

			foreach ($matchesL[1] as $match) {
				$names = parseName($match);
				$name = $names[1].' '.$names[0];
				$name = str_replace(array('"','NULL'),'',$name);

				$query = 'SELECT authorID FROM authors WHERE authFirstLat='.$names[1].' AND authLastLat='.$names[0];
				$q_result = sendQuery($query);

				if ($q_result) {
					$ID = $q_result->fetch_row()[0];
					$break=false;
					foreach ($auth as $authID) {
						if ($ID==$authID) {$break = true; break;}
					}
					if (!$break) {$auth[] = $ID;}
					continue;
				}

				$maxP = 0;
				$ID = -1;
				foreach ($namesC as $nameC) {
					$trans=str_replace('ы','и',transliterator_transliterate('bg',$name));
					similar_text($nameC['name'],$trans,$p);
					if ($p > \latCyrThresh and $p > $maxP) {$maxP = $p; $ID = $nameC['ID'];}
				}
				if ($ID==-1) {
					$query = 'INSERT INTO authors VALUES (0,NULL,NULL,'.$names[1].','.$names[0].')';
					$q_result = sendQuery($query);
					$auth[] = $GLOBALS['con']->insert_id;
				}
				else {
					$query = 'UPDATE authors SET authFirstLat='.$names[1].', authLastLat='.$names[0].' WHERE authorID='.$ID;
					sendQuery($query);
				}
//			echo $name.' ('.$ID.')';
			}
//		echo '<br>';
			return $auth;
		}
		else {return array(1);}
	}
	function detLang($data) {
		$langIDs = array();
		if ($data['lang']=='id=1') {$langIDs[]=1;}
		else {
			$langs = explode(',',$data['lang']);
			foreach ($langs as $lang) {
				$query = 'SELECT langID FROM langs WHERE langName="'.$lang.'"';
				$q_result = \sendQuery($query);

				if (!$q_result) {
					$query = 'INSERT INTO langs (langName) VALUES ("'.$lang.'")';
					\sendQuery($query);
					$langIDs[] = $GLOBALS['con']->insert_id;
				}
				else {$langIDs[] = $q_result->fetch_row()[0];}
			}
		}
		return $langIDs;
	}

	function fixData($data) {
		$data['auth'] = isset($data['Автор']) ?
			$data['Автор'] : 'id=1';
		$data['title'] = (isset($data['Заглавие']) and preg_match('/[^\\/]*/',$data['Заглавие'],$match)) ?
			preg_replace('/ : \\[[^\\]]*\\]$/','',trim($match[0])) : 'без заглавие';
		$data['year'] = isset($data['Година']) ?
			$data['Година'] : null;
		$data['ISBN'] = (isset($data['ISBN']) and preg_match_all('/[0-9]{13}|[0-9]{9}[0-9Xx]/',str_replace('-','',$data['ISBN']),$matches)) ?
			$matches[0][count($matches)-1] : null;
		$data['permaID'] = isset($data['COBISS.BG-ID']) ?
			intval($data['COBISS.BG-ID']) : null;
		$data['lang'] = isset($data['Език']) ?
			trim($data['Език']) : 'id=1';

		$data['service'] = 'cobiss';
		return $data;
	}

	function getData($ISBN=false,$cID=false,$toadd=false) {
		if ($cID) {$res = httpPost(permalink.$cID);}
		elseif ($ISBN) {$res = httpPost(searchURL, array('base' => '99999', 'command' => 'SEARCH', 'srch' => $ISBN));}
		else {throw new \RuntimeException('No input passed to service cobiss.');}

		if (!preg_match(tablePattern,$res,$match)) {return false;}
		else {
			$data = parseTable($match[1]);
			if (!$data) {return false;}

			$data = fixData($data);
			if ($cID) {$data['cobissID'] = $cID;}
			else {$data['ISBN'] = $ISBN;}

			if ($toadd) {
				$data['authorIDs'] = detAuth($data);
				$data['langIDs'] = detLang($data);
			} else {
				$data['authorString'] = detAuthorString($data);
			}

			return $data;
		}
	}

	const permalink = 'http://www.bg.cobiss.net/scripts/cobiss?command=DISPLAY&base=60000&rid=';
	const searchURL = 'http://www.bg.cobiss.net/scripts/cobiss';
	const tablePattern = '/<table[^>]*id="nolist-full"[^>]*>(.*)<\\/table>/s';
}

namespace inprint {
	function detAuthorString($data) {
		if ($data['auth']=='id=1' or !preg_match('/SearchCriteria=([^&]*)/',$data['auth'],$match)) {return 'неизвестен';}
		preg_match_all('/AuthorName:([^:]*)/',str_replace(' и др.','',urldecode($match[1])),$matches);

		if ($matches[1][0]=='колективен' or $matches[1][0]=='Колектив') {return 'колективен';}
		if ($matches[1][0]=='* * *') {return 'неизвестен';}

		$authorString = '';
		foreach($matches[1] as $auth) {
			$authorString.='; ';
			$s = strrpos($auth,' ');
			if ($s) {$authorString .= substr($auth,$s+1).', '.substr($auth,0,$s);}
			else {$authorString .= $auth;}
		}
		return $authorString ? substr($authorString,2) : 'неизвестен';
	}
	function detAuth($data) {
		if ($data['auth']=='id=1' or !preg_match('/SearchCriteria=([^&]*)/',$data['auth'],$match)) {return array(1);}
		preg_match_all('/AuthorName:([^:]*)/',str_replace(' и др.','',urldecode($match[1])),$matches);

		if ($matches[1][0]=='колективен' or $matches[1][0]=='Колектив') {return array(447);}
		if ($matches[1][0]=='* * *') {return array(1);}

		$authorIDs = array();
		foreach($matches[1] as $auth) {
			$s = strrpos($auth,' ');
			if ($s) {$names = array('"'.substr($auth,$s+1).'"', '"'.substr($auth,0,$s).'"');}
			else {$names = array('"'.$auth.'"','NULL');}

			$query = 'SELECT authorID FROM authors WHERE authFirst='.$names[1].' AND authLast='.$names[0];
			$q_result = \sendQuery($query);

			if (!$q_result) {
				$query = 'INSERT INTO authors VALUES (0,'.$names[1].','.$names[0].',NULL,NULL)';
				\sendQuery($query);
				$ID = $GLOBALS['con']->insert_id;
			}
			else {$ID = $q_result->fetch_row()[0];}
			$authorIDs[] = $ID;
		}
		return $authorIDs;
	}

	function fixYear($data) {
		if (!isset($data['permaID'])) {return null;}
		$str = httpPost(permalink.$data['permaID']);
		$doc = new \DOMDocument();
		@$doc->loadHTML($str);
		foreach ($doc->getElementsByTagName('table') as $table) {
			if ($table->getAttribute('class')=='issue') {
				if (preg_match('/(*UTF8)Планирана дата на издаване[\s]*[0-9]{2}.[0-9]{2}.([0-9]{4})/',$table->textContent,$match)) {
					return $match[1];
				} else {return null;}
			}
		}
		return null;
	}
	function fixLang($lang) {
		if ($lang=='Билингва') {return 'id=1';}
		$langs = explode('-',mb_strtolower($lang));
		$lang = '';
		foreach ($langs as $l) {
			if (preg_match('/(.*?)((.)ко|()о)$/',$l,$match)) {
				$lang.=$match[1].($match[3] ? $match[3] : 'с').'ки,';
			} else {$lang.=$l.',';}
		}
		return substr($lang,0,-1);
	}
	function fixData($data) {
		$data['auth'] = isset($data['author']) ?
			preg_replace('/\s/',' ',trim($data['author'][0][0])) : 'id=1';
		if (isset($data['title']) and preg_match('/<a.*href="\\/Publication\\/Details\\/([^"]*)".*>([^<]*)<\\/a>/',$data['title'][0][0],$match)) {
			$data['title'] = trim($match[2]);
			$data['permaID'] = $match[1];
		} else {
			$data['title'] = 'без заглавие';
		}
		$data['title'].= (isset($data['subtitle'])) ? ' : '.trim($data['subtitle'][0][0]) : '';
		$data['year'] = (isset($data['year']) and preg_match('/(*UTF8)година:[\s]*([0-9]+)/',$data['year'][0][0],$match)) ?
			$match[1] : fixYear($data);
//		$data['ISBN'] = (isset($data['isbn']) and preg_match_all('/[0-9]{13}|[0-9]{9}[0-9Xx]/',str_replace('-','',$data['isbn'][0][0]),$matches)) ?
//			$matches[0][count($matches)-1] : null;
		$data['lang'] = (isset($data['language']) and preg_match('/(*UTF8)език:[\s]*([А-я-]+)/',$data['language'][0][0],$match)) ?
			fixLang($match[1]) : 'id=1';

		$data['service'] = 'inprint';
		return $data;
	}

	function getData($ISBN,$toadd=false) {
		$varString = '';
		foreach (dashISBN(ISBNto13($ISBN)) as $var) {$varString.='ISBN:'.$var.':Or;';}
		foreach (dashISBN(ISBNto10($ISBN)) as $var) {$varString.='ISBN:'.$var.':Or;';}
		$url = searchURL.urlencode($varString);
		$doc = loadDoc($url);


		if ($res = $doc->getElementById('detailed-search-results') and preg_match('/Общ брой резултати: ([0-9]+)/',$res->textContent)) {
			$data = parseDivs($res)['book'][0]['info'][0];
			$data = fixData($data);
			$data['ISBN'] = $ISBN;
		} else {return false;}

		if ($toadd) {
			$data['authorIDs'] = detAuth($data);
			$data['langIDs'] = \cobiss\detLang($data);
		}
		else {$data['authorString'] = detAuthorString($data);}

		return $data;
	}

	const permalink = 'http://www.booksinprint.bg/Publication/Details/';
	const searchURL = 'http://www.booksinprint.bg/Publication/Search?SearchCriteria=';
}

namespace isbndb {
	function detAuthorString($data) {
		if ($data['auth']=='id=1') {return 'неизвестен';}

		$authorString = '';
		foreach ($data['auth'] as $auth) {
			$authorString.=$auth.'; ';
		}
		return substr($authorString,0,-2);
	}

	function fixData($res) {
		$data = array();

		foreach ($res['author_data'] as $auth_data) {
			$data['auth'][] = $auth_data['name'];
		}
		if (!isset($data['auth'])) {$data['auth'] = 'id=1';}
		$data['title'] = $res['title'] ? $res['title'] : 'без заглавие';
		$data['year'] = preg_match('/\[([0-9]{4})\]/',$res['publisher_text'],$match) ?
			$match[1] : preg_match('/([0-9]{4})(|\.)$/',$res['publisher_text'],$match) ?
			$match[1] : preg_match('/([0-9]{4})/',$res['edition_info'],$match) ?
			$match[1] : null;
		$data['lang'] = $res['language'] ? $res['language']=='eng' ? 'английски' : $res['language'] : null;
		$data['permaID'] = $res['book_id'];

		$data['service'] = 'isbndb';
		return $data;
	}

	function getData($ISBN,$toadd=false) {
		$res = json_decode(httpPost(searchURL.$ISBN),true);
		if (isset($res['error']) or !isset($res['data'])) {return false;}

		$data = fixData($res['data'][0]);
		$data['ISBN'] = $ISBN;

		if ($toadd) {
			$data['authorIDs'] = detAuth($data);
			$data['langIDs'] = detLang($data);
		}
		else {$data['authorString'] = detAuthorString($data);}
		return $data;
	}

	const searchURL = 'http://isbndb.com/api/v2/json/II0NLWK0/book/';
	const permalink = 'http://isbndb.com/book/';
}

namespace Amazon {
	function getData($ISBN,$toadd=false) {
		$doc = loadDoc(searchURL.$ISBN);
		if ($c = $doc->getElementById('s-result-count') and preg_match('/([0-9]+) result/',$c->textContent)) {

		} else {return false;}
	}

	const searchURL = 'https://www.amazon.com/gp/search/?field-isbn=';
}

namespace{
const SERVICES = array('cobiss','inprint','isbndb');
const SERVICES_PERMALINK = array('cobiss'=>cobiss\permalink, 'inprint'=>inprint\permalink, 'isbndb'=>isbndb\permalink);
const SERVICES_ID_RE  =array('cobiss'=>'/[0-9]+/','inprint'=>'/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/','isbndb'=>'/./');
}
?>