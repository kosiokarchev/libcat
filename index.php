<html>
    <head>
        <title>Каталог</title>
        <script type="text/javascript" src="scripts/header.js"></script>
        <script type="text/javascript" src="scripts/quagga.min.js"></script>
        <script> window.onload = initHeader;</script>

        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="styles/header.css">
        <link rel="stylesheet" href="styles/index.css">
    </head>
    <body>
        <?php require('snippets/header.php'); ?>
        <div id="contentDiv">
            <div class="navContainer flex">
                <a href="manual.php" class="navButton add" title="Ръчно добавяне"></a>
                <a href="search.php" class="navButton search" title="Разширено търсене"></a>
                <a href="search.php?lended" class="navButton return" title="Връщане на книги"></a>
            </div>
        </div>
    </body>
</html>