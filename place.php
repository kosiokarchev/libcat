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

$query = 'SELECT * FROM bookdata WHERE locID=1 ORDER BY author';
$res = sendQuery($query);
$num = $res->num_rows;
?>
<html>
    <head>
        <title>Неподредени книги - Каталог</title>
        <script type="text/javascript" src="scripts/header.js"></script>
        <script type="text/javascript" src="scripts/quagga.min.js"></script>
        <script type="text/javascript" src="scripts/submit.js"></script>
        <script type="text/javascript" src="scripts/multiple.js"></script>
        <script type="text/javascript" src="scripts/locs.js"></script>
        <script type="text/javascript" src="scripts/place.js"></script>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/header.css">
        <link rel="stylesheet" href="styles/multiple.css">
        <link rel="stylesheet" href="styles/place.css">
    </head>
    <body>
        <noscript>Please enable Javascript.</noscript>
        <div id="json_books" hidden><?php echo json_encode($res->fetch_all(),JSON_HEX_AMP); ?></div>
        <?php require('snippets/header.php'); ?>
    <form id="locForm" method="post" action="place.php" onsubmit="loadSubmit(this); return false;" style="margin: 0;">
        <input name="exec" value="1" hidden>
        <input name="bookIDs[]" hidden>
        <div id="locFormDiv" class="headerExtension flex">
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
            <div class="row title refineContainer fixed" style="width: 10in; max-width: 100%;">
                <div class="withIcon">
                    <input type="text" onclick="this.select()" id="refine">
                    <div class="icon mag_glass">&#128269;</div>
                </div>
            </div>
        </div>
    </form>
        <?php require('snippets/locChoice.php'); ?>
    </body>
</html>