<?php
$pass = 'Kusko';

function go() {
    $_SESSION['login'] = true;

    $file = ($_SERVER['SCRIPT_NAME']=='/' or $_SERVER['SCRIPT_NAME']=='/login.php') ? 'index.php' : '.'.$_SERVER['SCRIPT_NAME'];
    $v = include($file);
    if($v===false) {
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
        echo '<h1>404: Page Not Found</h1>';
        echo 'The file '.$_SERVER['SCRIPT_NAME'].' was not found on this server.';
    } else {}
    exit();
}

require_once('functions.php');
$postKeys = array('exec','pass');
if (checkPostFor($postKeys)) {
    if ($_POST['pass']==$pass) {
        session_start();
        $_SESSION['login'] = true;
        if (isset($_POST['rem']) and $_POST['rem']=='1') {setcookie('pass',md5($pass),time()+365*86400);}
        ajax_return(1);
    } else {ajax_return(0);}
    exit();
}

session_start();
if (isset($_SESSION['login']) and $_SESSION['login']) {go();}
if (false and $ip = getFromArray($_SERVER,array('HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR'))) {
    require_once('connect.php'); $GLOBALS['con']->select_db('test_db');
    $res = sendQuery('SELECT CONCAT_WS(".",ip1,ip2,ip3,ip4) FROM ips ORDER BY ipID DESC LIMIT 1');
    $GLOBALS['con']->select_db('libcat');
    if ($ip == $res->fetch_row()[0]) {go();}
}
if (isset($_COOKIE['pass']) and $_COOKIE['pass']==md5($pass)) {go();}
?>
<html>
    <head>
        <title>Вписване - Каталог</title>
        <script type="text/javascript">
            function fSubmit(f) {
                var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
                http.open("POST","login.php",true);
                http.onreadystatechange = function() {login(http);};
                http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
                var varString = "exec=1&pass="+encodeURIComponent(f.elements["pass"].value)+"&rem="+(f.elements["rem"].checked*1);
                http.send(varString);
                return false;
            }

            function login(http) {
                if(http.readyState==4 && http.status==200) {
                    try {
                        var response = JSON.parse(http.responseText);
                        if (response.status) {
                            alert("Вписването бе успешно.");
                            var redir = document.getElementById("redir");
                            if (redir.nodeName=="DIV") {
                                window.location.assign(redir.innerHTML);
                            } else {redir.submit();}
                        } else {alert("Въведената парола е грешна.");}
                    }
                    catch (e) {alert(http.responseText);}
                }
            }
        </script>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <style>
            body {margin: 0; background-color: cornflowerblue;}
            #contentDiv {
                position: absolute; top: 4vh; left: 4vw;
                width: 92vw; height: 92vh;
            }
            #contentDiv form {
                position: absolute; top: 18%; left: 0;
                width: 100%; display: block;
                text-align: center;
            }
            #contentDiv form input[type=password] {
                font-size: 2.5vh;
                text-align: center;
                margin-bottom: 3%;
                padding: 0.5vh;
                border: none;
                box-shadow: 0 0 0.1vh 1px black inset;
            }
            #contentDiv form .checkbox {
                display: inline-block;
                width: 3vh; height: 3vh;
                background-color: white;
                cursor: pointer;
                box-shadow: 0 0 0.1vh 1px black inset;
            }
            #contentDiv form .checkbox span {
                height: 100%; width: 100%;
                font-size: 2.5vh;
                text-align: center;
            }
            #contentDiv svg {width: 100%; height: 100%;}
        </style>
    </head>
    <body>
        <noscript>Please enable Javascript.</noscript>
        <div id="contentDiv">
            <svg version="1.1" viewBox="0 0 100 200">
                <circle cx="50" cy="50" r="50"></circle>
                <polygon points="0,200 50,0 100,200"></polygon>
            </svg>
            <form method="post" action="login.php" onsubmit="fSubmit(this,login); return false;">
                <input name="exec" value="1" hidden>
                <input type="password" name="pass" onkeypress="if (event.keyCode==13) fSubmit(this.form,login);"><br>
                <div class="checkbox" onclick="this.firstElementChild.checked = !this.firstElementChild.checked; this.lastElementChild.hidden = ! this.lastElementChild.hidden;">
                    <input type="checkbox" name="rem" value="1" checked hidden>
                    <span>&#10003</span>
                </div>
            </form>
        </div>
        <?php
            if ($_SERVER['REQUEST_METHOD']=='GET') {
                echo '<div id="redir" hidden>'.$_SERVER['REQUEST_URI'].'</div>';
            }
            else {
                echo '<form id="redir" hidden method="post" action="'.$_SERVER['REQUEST_URI'].'">';
                foreach ($_POST as $var=>$val) {
                    if (gettype($val)=='array') {
                        foreach ($val as $key=>$arr_val) {echo '<input name="'.$var.'['.$key.']" value="'.$arr_val.'">"';}
                    } else { echo '<input name="'.$var.'" value="'.$val.'">"'; }
                }
                echo '</form>';
            }
        ?>
    </body>
</html>
<?php exit(); ?>