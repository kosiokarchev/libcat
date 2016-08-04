<?php
require('functions.php');
require('connect.php');
$body = '';

function displayBook($ID) {
    $q_result = sendQuery('SELECT * FROM bookData WHERE bookID='.$ID);
    if (!$q_result) {return false;}
    $data = $q_result->fetch_assoc();

    $count = $data['count'];
    if ($data['lended']) {
        $count .= ' - ' . $data['lended'] . ' (отдадени) = ' . ($count - $data['lended']) . ' (в наличност)';
    }

    global $body;
    $body .= str_replace('"actions"','"actions headerExtension"',bookActions($data));

    $body .= '<div id="contentDiv">';
    $body .= singleBookTable($data);

    $body.= '<div class="row"><div class="infoLabel">Екземпляри</div><div class="dataDiv">'.$count.'</div></div>';
    $body.= ($data['lendedComment']) ?
        '<div class="row"><div class="infoLabel">Коментар</div><div class="dataDiv">'.$data['lendedComment'].'</div></div>' : '';
    return true;
}


$postKeys = array('exec','act');
if (checkPostFor($postKeys) and $_POST['exec']==1) {
    $postKeys = array('count', 'ID');
    if (checkPostFor($postKeys)) {
        $ID = intval($_POST['ID']);
        $count = intval($_POST['count']);
        if ($ID <= 0 or $count <= 0) {ajax_return(0, 'Моля, въвеждайте положителни стойности!');}

        $msg = (isset($_POST['msg'])) ? '"' . addslashes($_POST['msg']) . '"' : 'NULL';

        $q_result = sendQuery('SELECT count,lended,title FROM books WHERE bookID=' . $ID);
        $res = $q_result->fetch_assoc();
        if ($_POST['act'] == 'add') {
            sendQuery('UPDATE books SET count=count+' . $count . ' WHERE bookID=' . $ID);
            ajax_return(1, 'Added ' . nCopiesOf($count, $res['title']));
        } else if ($_POST['act'] == 'rem') {
            if ($count > $res['count'] - $res['lended']) {
                ajax_return(0, 'Cannot remove more than ' . nCopiesOf($res['count'] - $res['lended'], $res['title']));
            } else {
                sendQuery('UPDATE books SET count=count-' . $count . ' WHERE bookID=' . $ID);
                ajax_return(1, 'Removed ' . nCopiesOf($count, $res['title']));
            }
        } else if ($_POST['act'] == 'lend') {
            if ($count > $res['count'] - $res['lended']) {
                ajax_return(0, 'Cannot lend more than ' . nCopiesOf($res['count'] - $res['lended'], $res['title']));
            } else {
                sendQuery('UPDATE books SET lended=lended+' . $count . ', lendedComment=' . $msg . ' WHERE bookID=' . $ID);
                ajax_return(1, 'Lended ' . nCopiesOf($count, $res['title']));
            }
        } else if ($_POST['act'] == 'ret') {
            if ($count > $res['lended']) {
                ajax_return(0, 'Cannot return more than ' . nCopiesOf($res['lended'], $res['title']));
            } else {
                sendQuery('UPDATE books SET lended=lended-' . $count . ', lendedComment=' . $msg . ' WHERE bookID=' . $ID);
                ajax_return(1, 'Returned ' . nCopiesOf($count, $res['title']));
            }
        } else {ajax_return(0, 'Command not recognised.');}
    }
    else {ajax_return(0, 'Command not recognised');}
} else if (!(isset($_GET['ID']) and $_GET['ID'] and $ID=intval($_GET['ID']) and displayBook($_GET['ID']))) {header('Location: /');}
?>
<html>
<head>
    <title>Book -- Catalogue</title>
    <script type="text/javascript" src="scripts/header.js"></script>
    <script type="text/javascript" src="scripts/quagga.min.js"></script>
    <script type="text/javascript" src="scripts/submit.js"></script>
    <script type="text/javascript" src="scripts/action.js"></script>
    <script type="text/javascript"> window.onload = initHeader;</script>

    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="styles/header.css">
<!--    <style>-->
<!--        @media screen and (max-height: 16.5cm) {-->
<!--            .actions {-->
<!--                position: fixed;-->
<!--                bottom: 0;-->
<!--                z-index: 1;-->
<!--            }-->
<!--            #contentDiv {margin-bottom: 0.6in;}-->
<!--        }-->
<!--    </style>-->
</head>
<body>
    <? require('snippets/header.php'); ?>
    <noscript>Please enable Javascript.</noscript>
    <?php echo $body.'</div>'; ?>
</body>
</html>
