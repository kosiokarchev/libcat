<div id="header">
    <div id="menuDiv"></div>
    <div id="searchDiv">
    <form method="get" action="../search.php">
        <a class="extSearch flex" href="../search.php">още</a>
        <input type="text" name="search" id="searchField" onkeyup="if(event.keyCode==13) this.form.submit();">
        <div class="mag_glass" onclick="this.parentNode.parentNode.submit();">&#128269;</div>
        <div id="cameraButton" class="menuIcon iconcam"><input id="imgInput" type="file" accept="image/*;capture=camera" hidden><input id="ISBNinput" hidden></div>
    </form>
    </div>
    <div id="headerBG"></div>
    <div id="showSearch" class="menuIcon iconsearch"></div>
    <div id="showMenu"  class="menuIcon iconhome"></div>
</div>