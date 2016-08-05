<?php
require('connect.php');
require('functions.php');

function displayNewBook($data) {
    $where = '0 ';
    $langs = explode(',',$data['lang']);
    foreach ($langs as $lang) {
        $lang = trim($lang);
        $where .= 'OR langName="'.$lang.'" ';
    }
    $query = 'SELECT langID,langName FROM langs WHERE '.substr($where,5);
    $langIDs = '';
    if ($q_result = sendQuery($query)) {
        foreach ($q_result as $row) {
            $langIDs.='&langID[]='.$row['langID'];
            $keys = array_keys($langs,$row['langName']);
            foreach($keys as $i) {unset($langs[$i]);}
        }
    }
    $langName = '';
    if ($langs) {
        $langIDs.='&langID[]=0';
        foreach ($langs as $lang) {
            $langName.=$lang.', ';
        }
    }
    $langName = substr($langName,0,-2);

    $varString = '';
    $varString.= 'title='.urlencode($data['title']);
    $varString.='&author='.urlencode($data['authorString']);
    $varString.='&year='.urlencode($data['year']);
    $varString.='&ISBN='.urlencode($data['ISBN']);
    $varString.=$langIDs;
    $varString.='&langName='.$langName;

    if (isset($data['service'])) {
        $varString.='&service='.urlencode($data['service']);
        $varString.='&permaID='.urlencode($data['permaID']);
    }

    header('Location: manual.php?'.$varString);
}

function search($words,$table,$fields,$ret=false) {
    if ($table=='authors') {
        $weight1 = substr(searchWeight($words,[$fields[0],$fields[1]]),0,-10);
        $weight2 = substr(searchWeight($words,[$fields[2],$fields[3]]),0,-10);
        $weight = $weight1.' + '.$weight2.' AS weight';
    }
    else {$weight = searchWeight($words,$fields);}
    $where = buildWhere($words,$fields);
    $query = 'SELECT '.($ret ? $ret : '*').','.$weight.' FROM '.$table.' WHERE '.$where.' ORDER BY weight DESC';
    return sendQuery($query);
}
function searchResults() {
    if(matchISBN($_GET['search'])) {header('Location: search.php?ISBN='.$_GET['search']);}
    
    $_GET['search'] = preg_replace('/\s+/',' ',trim($_GET['search']));
    $_GET['search'] = htmlentities($_GET['search']);
    $words = explode(' ',$_GET['search']);
    $content = '';
    $results = false;

    $res = search($words,'bookdata','title');
    if ($res) {
        $results = true;
        $content.='<h1>Книги</h1>';
        $content.= '<div id="books">'.multipleBooks($res).'</div>';
    }

    $res = search($words,'authors',array('authFirst','authLast','authFirstLat','authLastLat'));
    if ($res) {
        $results = true;
        $content.='<h1>Автори</h1>';
        $content.= multipleAuthors($res);
    }

    return $results ? $content : false;
}


function error($text) {return '<div class="headerExtension flex error">'.$text.'</div>';}

$postKeys = array('exec','input');
if (checkPostFor($postKeys) and $_POST['exec']==1) {
    if ($_POST['input'] == '') {
        echo '{}';
    } else {
        echo(($q_result = authSearch($_POST['input'])) ? json_encode($q_result->fetch_all()) : '{}');
    }
    exit();
}

if (isset($_GET['ISBN']) and $_GET['ISBN']) {
    $ISBN = cleanISBN($_GET['ISBN']);
    $q_result = existsISBN($ISBN);
    if ($q_result) {
        header('Location: book.php?ID='.($q_result->fetch_row()[0]));
    } else {
        $data = getData(array('ISBN'=>$ISBN));
        if ($data) {displayNewBook($data);}
        else {
            $error = '<div class="headerExtension flex error">Грешка 404: ISBN '.$_GET['ISBN'].' не бе намерен в никой от източниците.<br><a href="manual.php?ISBN='.$_GET['ISBN'].'">Ръчно добавяне.</a></div>';
        }
    }
}
elseif (isset($_GET['search']) and $_GET['search']) {
    if ($res = searchResults()) {$books = $res;}
    else {$error = error('Няма намерени резултати за "'.$_GET['search'].'"');}
}
elseif (isset($_GET['lended'])) {
    $res = sendQuery('SELECT * FROM bookdata WHERE lended ORDER BY title');
    if ($res) {$books = '<div id="books">'.multipleBooks($res).'</div>';}
    else {$error = error('В момента няма отдадени книги.');}
} elseif (isset($_GET['authorID']) and $authorID=intval($_GET['authorID'])) {
    $res = sendQuery('SELECT * FROM bookdata WHERE EXISTS (SELECT * FROM authorship WHERE authorID='.$authorID.' AND bookID=bookdata.bookID) ORDER BY title');
    if ($res) {$books = '<div id="books">'.multipleBooks($res).'</div>';}
    else {$error = error('Няма намерени резултати');}
}
?>
<html>
    <head>
        <title>Търсене</title>
        <script type="text/javascript" src="scripts/header.js"></script>
        <script type="text/javascript" src="scripts/quagga.min.js"></script>
        <script type="text/javascript" src="scripts/multiple.js"></script>
        <script type="text/javascript" src="scripts/submit.js"></script>
        <script type="text/javascript" src="scripts/action.js"></script>
        <script>
            if (window.addEventListener) {
                window.addEventListener("load",initHeader,false);
                window.addEventListener("load",function () {initMultiple();},false);
            } else {window.onload = function () {initHeader(); initMultiple();}}
        </script>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/header.css">
        <link rel="stylesheet" href="styles/multiple.css">
        <link rel="stylesheet" href="styles/search.css">
    </head>
    <body>
        <?php require('snippets/header.php');
              echo isset($error) ? $error : ''; ?>
        <div id="contentDiv">
            <?php if(isset($books)) echo $books; else require('snippets/advancedSearch.php'); ?>
        </div>
    </body>
</html>
