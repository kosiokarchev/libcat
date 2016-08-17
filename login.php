<?php
$pass = 'Kusko';

require_once('functions.php');
$postKeys = array('exec','pass');
if (checkPostFor($postKeys)) {
    if ($_POST['pass']==$pass) {
        session_start();
        $_SESSION['login'] = true;
        if (isset($_POST['rem'])) {setcookie('pass',md5($pass),time()+365*86400);}
        ajax_return(1);
    } else {ajax_return(0);}
    exit();
}

session_start();
if (isset($_SESSION['login']) and $_SESSION['login']) {return;}
elseif (isset($_SERVER['REMOTE_ADDR'])) {
    require('connect.php'); $GLOBALS['con']->select_db('test_db');
    $res = sendQuery('SELECT CONCAT_WS(".",ip1,ip2,ip3,ip4) ORDER BY ipID DESC LIMIT 1');
    var_dump($res->fetch_row()[0]); exit();
} elseif (isset($_COOKIE['pass']) and $_COOKIE['pass']==md5($pass)) {
    $_SESSION['login']=true;
    return;
}
?>
<html>
    <head>
        <title>Вписване - Каталог</title>
        <script type="text/javascript" src="scripts/submit.js"></script>
        <script type="text/javascript" src="scripts/login.js"></script>
    </head>
    <body>
        <noscript>Моля, </noscript>
        <form method="post" action="login.php" onsubmit="fSubmit(this,login); return false;">
            <input name="exec" value="1" hidden>
            Парола : <input type="password" name="pass">
            <input type="checkbox" name="rem" value="1" checked><br>
            <input type="submit">
        </form>
        <?php
            if ($_SERVER['REQUEST_METHOD']=='GET') {
                echo '<div id="redir" hidden>'.$_SERVER['REQUEST_URI'].'</div>';
            }
            else {
                echo '<form id="redir" hidden method="post" action="'.$_SERVER['REQUEST_URI'].'">';
                foreach ($_POST as $var=>$val) {
                    if (gettype($val)=='array') {
                        foreach ($val as $key=>$arr_val) {
                            echo '<input name="'.$var.'['.$key.']" value="'.$arr_val.'">"';
                        }
                    } else { echo '<input name="'.$var.'" value="'.$val.'">"'; }
                }
                echo '</form>';
            }
        ?>
    </body>
</html>
<?php exit(); ?>