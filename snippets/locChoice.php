<?php
if (!isset($locs)) {
    $res = sendQuery('SELECT * FROM locations');
    $locs = '';
    foreach ($res as $loc) {
        $locs.= '<option value="'.$loc['locID'].'">'.$loc['locName'].'</option>';
    }
}

if (!isset($locdivs)) {
    $res = sendQuery('SELECT * FROM locdivs');
    $locdivs = '';
    foreach ($res as $loc) {
        $locdivs.= '<option value="'.$loc['locdivID'].'">'.$loc['locdivName'].'</option>';
    }
}
?>

<style>
    #locChoice {
        display: none;
        position: relative;
        width: 100vw;  height: 100vh;
        background-color: cornflowerblue;
    }
    #locChoice table {  font-size: 21pt;  }
    #locChoice td {  vertical-align: middle;  }
    #locdivContainter {  overflow: scroll; background-color: cornflowerblue;  }
    #locdivContainter>div {  margin: auto;  }
</style>

<script type="text/javascript">
    function chooseLocation(output,doNotUpdate) {
        var choiceID = document.getElementById("choiceID");
        var locdivSelect = document.getElementById("locdivSelect");
        var locdivContainter = document.getElementById("locdivContainter");
        var locdiv;

        locdivSelect.onchange = function() {
            if (this.value == 1) {
                choiceID.value=1;
                locdivContainter.innerHTML = "";
                return;
            }
            locdiv = buildPlace(this.value,true);
            locdiv.style.fontSize = "3px";
            locdiv.onclick = function(e) {
                if (e.target.firstChild.checked) {choiceID.value = e.target.firstChild.value;}
                fire("change",choiceID);
            };
            locdivContainter.innerHTML = "";
            locdivContainter.appendChild(locdiv);
        };
        choiceID.onchange = function() {
            output.value = choiceID.value;
            if (!doNotUpdate) {fire("change",output);}
        };
        document.getElementById("locCloseBut").onclick = function() {
            fire("change",output);
            locdivSelect.onchange = null;
            document.getElementById("locChoice").style.display = "none";
            document.getElementById("contentDiv").style.display = "block";
            document.getElementById("locFormDiv").scrollIntoView();

            return false;
        };

        choiceID.value = output.value;
        fire("change",choiceID);

        document.getElementById("contentDiv").style.display = "none";
        document.getElementById("locChoice").style.display = "block";
        document.getElementById("locChoice").scrollIntoView();
    }
</script>

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