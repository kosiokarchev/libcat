<?php
require('functions.php');
require('connect.php');

function getIDsFrom($array) {
    $IDs = array();
    foreach ($array as $ID) {
        if (!(preg_match('/[^0-9]/', $ID) or in_array($ID, $IDs))) {$IDs[] = $ID;}
    }
    return $IDs;
}

function newAuth($data) {
    foreach ($data['newAuthors'] as $auth) {
        $auth = explode('=',$auth);
        if (count($auth)==1) {
            $names = parseName($auth[0]);
            if (preg_match('/[A-Za-z]/', $names[0])) {$namesL = $names; $namesC = array('NULL','NULL');}
            else {$namesL = array('NULL','NULL'); $namesC = $names;}
        } else {$namesC = parseName($auth[0]); $namesL = parseName($auth[1]);}

        $query = 'SELECT authorID FROM authors WHERE ';
        $query.= '(authFirst='.$namesC[1].' AND authLast='.$namesC[0].') OR ';
        $query.= '(authFirstLat='.$namesL[1].' AND authLastLat='.$namesL[0].')';
        $q_result = sendQuery($query);

        if ($q_result) {
            $newID = $q_result->fetch_row()[0];
            if ($namesC and $namesL) {sendQuery('UPDATE authors SET authFirstLat='.$namesL[1].', authLastLat='.$namesL[0].' WHERE authorID='.$newID);}
            if (!in_array($newID,$data['authorIDs'])) {$data['authorIDs'][] = $newID;}
        }
        else {
            $query = 'INSERT INTO authors VALUES ';
            $query.= '(NULL,'.$namesC[1].','.$namesC[0].','.$namesL[1].','.$namesL[0].')';
            sendQuery($query);
            $newID = $GLOBALS['con']->insert_id;
            if (!in_array($newID,$data['authorIDs'])) {$data['authorIDs'][] = $newID;}
        }
    }
    return $data;
}
function newLang($data) {
    foreach ($data['newLangs'] as $lang) {
        $lang = explode(' ',$lang);
        if (count($lang)>1) {$code = '"'.substr($lang[1],1,-1).'"';}
        else {$code = 'NULL';}
        $lang = '"'.$lang[0].'"';

        $query = 'SELECT langID FROM langs WHERE langName='.$lang;
        $q_result = sendQuery($query);
        if ($q_result) {
            $newID = $q_result->fetch_row()[0];
            if (!in_array($newID,$data['langIDs'])) {$data['authorIDs'][] = $newID;}
        }
        else {
            $query = 'INSERT INTO langs VALUES (NULL,'.$lang.', '.$code.')';
            sendQuery($query);
            $newID = $GLOBALS['con']->insert_id;
            if (!in_array($newID,$data['langIDs'])) {$data['langIDs'][] = $newID;}
        }
    }
    return $data;
}
function newSeries($data) {
    $res = sendQuery('SELECT seriesID FROM series WHERE seriesName LIKE "%'.$data['seriesName'].'%"');
    if ($res) {return $res->fetch_row()[0];}
}

$postKeys = array('exec','act');
if (checkPostFor($postKeys) and $_POST['exec']==1) {
    if ($_POST['act']=='searchAuthor' and isset($_POST['input'])) {
        if ($_POST['input']=='') {echo '{}';}
        else {
            echo (($q_result = authSearch($_POST['input'])) ? json_encode($q_result->fetch_all()) : '{}');
        }
    }
    elseif ($_POST['act']=='new') {
        $postKeys = array('title','year','author','langID','ISBN','locID');
        if(!(checkPostFor($postKeys) and gettype($_POST['langID'])=='array')) {ajax_return(0,'Form is corrupted.');}

        if (isset($_POST['authorID']) and gettype($_POST['authorID'])=='array') {
            $authorIDs = getIDsFrom($_POST['authorID']);
        } else {$authorIDs = array();}

        $newAuthors = array();
        if (isset($_POST['authorNew']) and gettype($_POST['authorNew'])=='array'){
            foreach ($_POST['authorNew'] as $new) {
                $new = preg_replace('/\s+/',' ',trim($new));
                if (!preg_match('/(*UTF8)^[А-яA-Za-z0-9 -=,()\'"]+$/',$new)) {ajax_return(0,'"'.$new.'" съдържа забранен символ или е празен.');}
                if (!in_array($new,$newAuthors)) {$newAuthors[]=$new;}
            }
        }
        if (!($authorIDs or $newAuthors)) {ajax_return(0,'Не сте избрали валидни автори.');}

        $title = preg_replace('/\s+/',' ',trim($_POST['title']));
        if ($title=='') {ajax_return(0,'Моля, въведете заглавие!');}

        if (!preg_match('/^[0-9]+$/',$_POST['year']) or $_POST['year']>date('Y')) {ajax_return(0,'Невалидна година "'.$_POST['year'].'"');}

        $langIDs = getIDsFrom($_POST['langID']);
        if (!$langIDs) {ajax_return(0,'Не сте избрали валидни езици.');}
        $langNames = array();
        if (in_array('0',$langIDs)) {
            if (isset($_POST['langName'])) {
                foreach(explode(',',$_POST['langName']) as $langName) {
                    $langName = preg_replace('/\s+/', ' ', trim($_POST['langName']));
                    if (!preg_match('/(*UTF8)[А-я]+( \\([A-Za-z]+\\)|$)/', $langName)) {ajax_return(0, '"' . $langName . '" не изглежда да е език.');}
                    else {$langNames[] = $langName;}
                }
            } else {ajax_return(0,'Form is corrupted.');}
        }

        $ISBN = cleanISBN($_POST['ISBN']);
        $sureISBN = isset($_POST['sureISBN']) and $_POST['sureISBN'];
        if (!($sureISBN or verifyISBN($ISBN))) {ajax_return(0,'"'.$ISBN.'" не е валиден ISBN код.');}
        else if ($q_result = existsISBN($ISBN)) {ajax_return(0,'Книгата с ISBN '.$ISBN.' вече присъства в каталога.','book.php?type=ID&code='.$q_result->fetch_row()[0]);}

        if (!($locID = intval($_POST['locID']))) {$locID = 1;}

        if ($zero = array_search(0,$authorIDs)) {unset($authorIDs[$zero]);}
        if ($zero = array_search(0,$langIDs)) {unset($langIDs[$zero]);}

        $data = array('authorIDs'=>$authorIDs,'newAuthors'=>$newAuthors,'title'=>$title,'year'=>$_POST['year'],'langIDs'=>$langIDs,'newLangs'=>$langNames,'ISBN'=>$ISBN, 'locID'=>$locID);

        $postKeys = array('addSeries','seriesID','seriesNum');
        if (checkPostFor($postKeys) and $_POST['addSeries']) {
            if ($seriesID = intval($_POST['seriesID'])) {
                $data['seriesID'] = $seriesID;
            } elseif (isset($_POST['seriesName']) and $_POST['seriesName']) {
                $data['seriesID'] = newSeries($data);
            } else {ajax_return(0,'Моля, въведете име на новата поредица.');}
            $data['seriesNum'] = ($seriesNum = intval($_POST['seriesNum'])) ? $seriesNum : null;
        }

        if (isset($_POST['service']) and isset($_POST['permaID']) and in_array($_POST['service'],SERVICES) and preg_match(SERVICES_ID_RE[$_POST['service']],$_POST['permaID'])) {
            $data['service'] = $_POST['service'];
            $data['permaID'] = $_POST['permaID'];
        }

        $data = newLang($data);
        $data = newAuth($data);

        $newID = add($data);
        ajax_return(1,'Книгата е добавена.','book.php?ID='.$newID);
    } else {ajax_return(0,$_POST['act'].' not implemented');}
    exit();
}

$q_result = sendQuery('SELECT langID,langName,langCode FROM langs ORDER BY langName');
$langs = '';
$getLangs = (isset($_GET['langID']) and gettype($_GET['langID'])=='array' and $_GET['langID']);
foreach($q_result as $lang) {
    $selected = ($getLangs ? in_array($lang['langID'],$_GET['langID']) : $lang['langID']==1) ? 'selected' : '';
    $langs .= '<option value="'.$lang['langID'].'" '.$selected.' title="'.$lang['langName'].'">'.$lang['langName'].' ('.$lang['langCode'].')</option>';
}
$langs .= '<option value="0" '.(($getLangs and in_array(0,$_GET['langID'])) ? 'selected' : '').'>друг</option>';

$q_result = sendQuery('SELECT * FROM locations');
$locs = '';
foreach ($q_result as $loc) {
    $locs.= '<option value="'.$loc['locID'].'">'.$loc['locName'].'</option>';
}

if (!(isset($_GET['langName']) and $_GET['langName'])) {$_GET['langName']='';}
if (!(isset($_GET['title']) and $_GET['title'])) {$_GET['title']='';}
if (!(isset($_GET['author']) and $_GET['author'])) {$_GET['author']='неизвестен';}
if (!(isset($_GET['year']) and $_GET['year'])) {$_GET['year']='';}
if (!(isset($_GET['ISBN']) and $_GET['ISBN'])) {$_GET['ISBN']='';}

?>

<html>
    <head>
        <title>Ръчно добавяне</title>

        <script type="text/javascript" src="scripts/header.js"></script>
        <script type="text/javascript" src="scripts/quagga.min.js"></script>
        <script type="text/javascript" src="scripts/manual.js"></script>
        <script type="text/javascript" src="scripts/dynamic.js"></script>
        <script type="text/javascript" src="scripts/submit.js"></script>
        <script type="text/javascript" src="scripts/locs.js"></script>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/manual.css">
        <link rel="stylesheet" href="styles/header.css">
    </head>
    <body>
        <?php require('snippets/header.php'); ?>
        <noscript>Please enable Javascript.</noscript>
        <form id="newForm" method="post" action="manual.php">
        <input name="exec" value="1" hidden>
        <input name="act" value="new" hidden>
        <?php if (isset($_GET['permaID'])) echo '<input name="service" value="'.$_GET['service'].'" hidden><input name="permaID" value="'.$_GET['permaID'].'" hidden>'; ?>
            
        <div id="contentDiv">
            <div><h1>Добавяне на книга</h1></div>
            <div class="row">
                <div class="infoLabel">Заглавие</div>
                <div class="dataDiv withButton"><input class="underlined" type="text" name="title" value="<?php echo $_GET['title'];?>" autofocus><div class="button" id="addSeriesButton"><input type="checkbox" name="addSeries" value="1" hidden></div></div>
            </div>
            <div class="row">
                <div class="infoLabel">Автор</div>
                <div  class="dataDiv">
                    <div class="withIcon">
                        <input class="search" type="text" name="author" value="<?php echo $_GET['author'];?>">
                        <div class="icon mag_glass">&#128269;</div>
                    </div>
                    <div class="dynamic">
                        <div id="authNew" class="new"></div>
                        <div id="authChoice" class="choice"></div>
                        <div id="authSugg" class="sugg"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="infoLabel">Година</div>
                <div class="dataDiv"><input type="number" name="year" <?php echo 'max="'.date('Y').'" value="'.$_GET['year'].'"'; ?>></div>
            </div>
            <div class="row">
                <div class="infoLabel">Език</div>
                <div class="dataDiv">
                    <select name="langID[]" multiple><?php echo $langs; ?></select>
                    <div id="langInputDiv"><input type="text" name="langName" value="<?php echo $_GET['langName']; ?>"></div>
                </div>
            </div>
            <div class="row">
                <div class="infoLabel">ISBN</div>
                <div class="dataDiv">
                    <div class="withIcon">
                        <input type="text" size="14" maxlength="13" name="ISBN" value="<?php echo $_GET['ISBN'] ?>">
                        <div class="icon"><input type="checkbox" name="sureISBN" value="1"></div>
                        <div class="icon tick">&#10003;</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="infoLabel">Местоположение</div>
                <div class="dataDiv flex">
                    <select name="locID"><?php echo $locs;?></select>
                    <div class="moveButton" onclick="chooseLocation(this.previousElementSibling); return false;"></div>
                </div>
            </div>
            <div><div colspan="2"><input type="submit" value="добави"></div></div>
        </table>
        </div>
        </form>
        <?php require('snippets/locChoice.php'); ?>
    </body>
</html>