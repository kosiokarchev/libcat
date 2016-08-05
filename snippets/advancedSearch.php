<script type="text/javascript" src="/scripts/dynamic.js"></script>
<script type="text/javascript" src="/scripts/search.js"></script>
<form action="/manual.php">
    <input name="exec" value="1" hidden>
    <div class="searchContainer row">
<!--        <h1>Разширено търсене</h1>-->
        <div class="searchrow" title="Заглавие">
            <input type="text" name="title" class="data">
            <div class="labelText">Заглавие:</div>
            <div class="labelIcon labeltitle"></div>
        </div>
        <div class="searchrow withIcon"  title="Автор">
            <input type="text" name="author" class="data" id="authorInput">
            <div class="labelText">Автор:</div>
            <div class="labelIcon labelauthor"></div>
            <div class="icon sync labelIcon labeldyn" onclick="this.firstElementChild.click();">
                <input id="dynAuthor" type="checkbox">
            </div>

        </div>
        <div class="sugg" id="authorOutput"></div>
        <div class="searchrow withIcon" title="ISBN">
            <input type="text" name="ISBN" class="data">
            <div class="icon"><input type="checkbox" name="sureISBN" value="1"></div>
            <div class="icon tick">&#10003;</div>
            <div class="labelText">ISBN:</div>
            <div class="labelIcon labelisbn"></div>
        </div>
        <input type="submit">
    </div>
</form>
