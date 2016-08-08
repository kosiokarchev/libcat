<?php
require('functions.php');
require('connect.php');

if (isset($_POST['exec']) and $_POST['exec']==1) {
    $postKeys = array('locID','bookIDs');
    if (checkPostFor($postKeys) and gettype($_POST['bookIDs'])=='array') {
        if ($locID=intval($_POST['locID'])) {
            $in = '';
            foreach ($_POST['bookIDs'] as $ID) { if ($ID=intval($ID)) {$in.=intval($ID).',';} }
            if ($in) {$in = '('.substr($in,0,-1).')';}
            else {ajax_return(0,'Моля, изберете книги.');}
            sendQuery('UPDATE books SET locID='.$locID.' WHERE bookID in '.$in);;
            ajax_return(1,'Книгите са преместени на '.sendQuery('SELECT locName FROM locations WHERE locID='.$locID)->fetch_row()[0].'.');
        } else {ajax_return(0,'Моля, изберете място за поставянето на книгите.');}
    } else {ajax_return(0,'Insufficient data posted.');}
    exit();
}

$res = sendQuery('SELECT * FROM locations');
$locs = '';
foreach ($res as $loc) {
    $locs.= '<option value="'.$loc['locID'].'">'.$loc['locName'].'</option>';
}

$res = sendQuery('SELECT * FROM locdivs');
$locdivs = '';
foreach ($res as $loc) {
    $locdivs.= '<option value="'.$loc['locdivID'].'">'.$loc['locdivName'].'</option>';
}

$query = 'SELECT * FROM bookdata WHERE locID=1 ORDER BY author';
$res = sendQuery($query);
$books = multipleBooks($res,false);
$num = $res->num_rows;
?>
<html>
    <head>
        <title>Неподредени книги - Каталог</title>
        <script type="text/javascript" src="scripts/header.js"></script>
        <script type="text/javascript" src="scripts/quagga.min.js"></script>
        <script type="text/javascript" src="scripts/submit.js"></script>
        <script type="text/javascript" src="scripts/multiple.js"></script>
        <script type="text/javascript" src="scripts/place.js"></script>
        <script>
            if (window.addEventListener) {
                window.addEventListener("load",initHeader,false);
                window.addEventListener("load",function () {initMultiple("bookSugg");},false);
                window.addEventListener("load",initPlace,false);
            } else {window.onload = function () {initHeader(); initMultiple("bookSugg"); initPlace();}}
        </script>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/header.css">
        <link rel="stylesheet" href="styles/multiple.css">
        <link rel="stylesheet" href="styles/place.css">
    </head>
    <body>
        <?php require('snippets/header.php'); ?>
        <noscript>Please enable Javascript.</noscript>
    <form id="locForm" method="post" action="place.php" onsubmit="locSubmit(this); return false;">
        <input name="exec" value="1" hidden>
        <input name="bookIDs[]" hidden>
        <div id="locFormDiv" class="headerExtension flex" style="display: none;">
            <div id="choiceCount" class="bookIcon booktitle flex">0</div>
            <div class="labelIcon labelarrow"></div>
            <div class="divForm thumbnailContainer" onclick="this.firstElementChild.click();">
                <button onclick="chooseLocation(this.nextElementSibling); return false;">Choose location</button>
                <select id="locID" name="locID"><?php echo $locs; ?></select>
                <div class="thumbnail flex"><div class="labelIcon labelloc question">?</div></div>
            </div>
            <div class="divForm submit flex" onclick="this.firstElementChild.click();">
                <input type="submit"><div id="loadingImg" class="flex"><img src="/Images/icons/loading.gif"></div>Go
            </div>
        </div>
        <div id="contentDiv">
            <p><?php echo $num; ?> неподредени книги</p>
            <div id="bookChoice" class="choice"></div>
            <div class="row title refineContainer">
                <div class="withIcon">
                    <input type="text" onclick="this.select()" id="refine">
                    <div class="icon mag_glass">&#128269;</div>
                </div>
            </div>
            <div id="bookSugg" class="sugg"><?php echo $books; ?></div>
        </div>
        <div id="locChoice">
            <div class="flex">
                <table>
                    <tr>
                        <td>Участък:</td><td><select id="locdivSelect"><?php echo $locdivs;?></select></td>
                        <td rowspan="2"><div class="divForm submit flex" onclick="this.firstElementChild.click();">&#x2714;<button id="locCloseBut">Close</button></div></td>
                    </tr>
                    <tr><td>Рафт:</td><td><select id="choiceID"><?php echo $locs; ?></select></td></tr>
                </table>
            </div>
            <div id="locdivContainter"></div>
        </div>
    </form>
    </body>
</html>