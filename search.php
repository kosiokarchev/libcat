<?php
require_once('functions.php');
require_once('connect.php');

$postKeys = array('exec','input');
if (checkPostFor($postKeys) and $_POST['exec']==1) {
    if ($_POST['input'] == '') {echo '{}';}
    else {echo(($q_result = authSearch($_POST['input'])) ? json_encode($q_result->fetch_all()) : '{}');}
    exit();
} else {
    if (isset($_GET['ISBN']) and $_GET['ISBN']) {
        $ISBN = cleanISBN($_GET['ISBN']);
        $q_result = existsISBN($ISBN);
        if ($q_result) {header('Location: book.php?ID='.($q_result->fetch_row()[0]));}
        else {
            $data = getData(array('ISBN'=>$ISBN));
            if ($data) {displayNewBook($data);}
            else {$error = error('Грешка 404: ISBN '.$_GET['ISBN'].' не бе намерен в никой от източниците.<br><a href="manual.php?ISBN='.$_GET['ISBN'].'">Ръчно добавяне.</a>');}
        }
    } elseif (isset($_GET['title']) and $_GET['title']) {
        $_GET['title'] = preg_replace('/\s+/',' ',trim($_GET['title']));
        $_GET['title'] = htmlentities($_GET['title']);
        $words = explode(' ',$_GET['title']);

        if ($res=search($words,'bookdata','title')) {$books = $res->fetch_all();}
    } elseif (isset($_GET['author']) and $_GET['author']) {
        if ($res = authSearch($_GET['author'], 0)) {$authors = $res->fetch_all();}
    } else {
        if (isset($_GET['search']) and $_GET['search']) {
            if(matchISBN($_GET['search'])) {header('Location: search.php?ISBN='.$_GET['search']);}

            $_GET['search'] = preg_replace('/\s+/',' ',trim($_GET['search']));
            $_GET['search'] = htmlentities($_GET['search']);
            $words = explode(' ',$_GET['search']);

            if ($res=search($words,'bookdata','title')) {$books = $res->fetch_all();}
            if ($res=authSearch($words)) {$authors = $res->fetch_all();}
            if (!(isset($books) or isset($authors))) {$error = error('Няма намерени резултати за "'.$_GET['search'].'"');}
        } elseif (isset($_GET['lended'])) {
            $res = sendQuery('SELECT * FROM bookdata WHERE lended ORDER BY title');
            if ($res) {$books = $res->fetch_all();}
            else {$error = error('В момента няма отдадени книги.');}
        } elseif (isset($_GET['authorID']) and $authorID=intval($_GET['authorID'])) {
            $res = sendQuery('SELECT * FROM bookdata WHERE EXISTS (SELECT * FROM authorship WHERE authorID='.$authorID.' AND bookID=bookdata.bookID) ORDER BY title');
            if ($res) {$books = $res->fetch_all();}
            else {$error = error('Няма намерени резултати');}
        }
    }
}
?>
<html>
    <head>
        <title>Търсене</title>
        <script type="text/javascript" src="scripts/header.js"></script>
        <script type="text/javascript" src="scripts/quagga.min.js"></script>
        <script type="text/javascript" src="scripts/submit.js"></script>
        <script type="text/javascript" src="scripts/action.js"></script>
        <script type="text/javascript" src="scripts/locs.js"></script>
        <script type="text/javascript" src="scripts/multiple.js"></script>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/header.css">
        <link rel="stylesheet" href="styles/multiple.css">
        <link rel="stylesheet" href="styles/search.css">
    </head>
    <body>
        <noscript>Please enable Javascript.</noscript>
        <?php
        require('snippets/header.php');
        echo isset($error) ? $error : '';
        if (isset($books) or isset($authors)) { require('snippets/searchResults.php');}
        else {require('snippets/advancedSearch.php');}
        ?>
    </body>
</html>