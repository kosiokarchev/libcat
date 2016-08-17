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
        width: 100vw; height: 100vh;
        max-width: 100%;
        padding-top: 50px;
        margin-top: 10px;
        overflow: hidden;
        border-top-left-radius: 25px; border-top-right-radius: 25px;
        box-shadow: 0 0 10px 0 black;
    }
    #locChoiceHeader {
        position: absolute; top: 0; left: 0;
        width: 100%;  height: 50px;
        box-shadow: 0 0 10px 0 black;
        border-radius: 25px;
        background-color: cornflowerblue;
    }
    #locChoiceContent {  position: relative; height: 100%; padding-top: 0.1in; overflow: scroll;  }

    #locChoiceHeader .submit {margin: 0; width: 50%; float: left; box-shadow: 0 0 5px 0 black;}
    #locChoiceSelectDiv {
        width: 100%; height: 2in;
        overflow-x: scroll;
        white-space: nowrap; text-align: center;
        background-color: aliceblue;
        border-bottom: 2px solid black;
    }
    #locChoiceSelectDiv>div {
        display: inline-block; vertical-align: top;
        height: 100%;
        padding-left: 0.1in; padding-right: 0.1in;
        border-left: 1px dashed gray;
        font-size: 0.008in;
        cursor: pointer;
    } #locChoiceSelectDiv>div:last-child {  border-right: 1px dashed gray; }
    #locChoiceSelectDiv>div>div {height: 100%;}
    #locChoiceSelectDiv>div:hover>div>div {background-color: cornflowerblue;}

    #locChoiceContainer {  margin-top: 0.1in; overflow: scroll;  }
    #locChoiceContainer>div {  margin: auto;  }
</style>

<script type="text/javascript">
    var locChoiceSelectDiv, locChoiceID, locChoiceSelect, locChoiceContainer;
    window.addEventListener("load",function () {
        locChoiceSelectDiv = document.getElementById("locChoiceSelectDiv");
        locChoiceID = document.getElementById("locChoiceID");
        locChoiceSelect = document.getElementById("locChoiceSelect");
        locChoiceContainer = document.getElementById("locChoiceContainer");

        for (var i=0; i < locChoiceSelect.options.length; i++) {
            var locdiv = buildPlace(locChoiceSelect.options[i].value,true,true); if (!locdiv) {continue;}

            var locdivCont = document.createElement("DIV");
            locdivCont.ID = locChoiceSelect.options[i].value;
            locdivCont.onclick = function () {
                locChoiceSelect.value = this.ID;
                fire("change",locChoiceSelect);
            };
                var locdivFlexCont = divWithClass("flex"); locdivFlexCont.appendChild(locdiv);

            locdivCont.appendChild(locdivFlexCont);
            locChoiceSelectDiv.appendChild(locdivCont);
        }
    },false);

    function chooseLocation(output,doNotUpdate) {
        var locdiv;

        locChoiceSelect.onchange = function() {
            if (this.value == 1) {
                locChoiceID.value=1;
                locChoiceContainer.innerHTML = "";
                return;
            }
            locdiv = buildPlace(this.value,true);
            locdiv.style.fontSize = "3px";
            locdiv.style.backgroundColor = "cornflowerblue";
            locdiv.style.cursor = "pointer";
            locdiv.onclick = function(e) {
                if (e.target.firstChild.checked) {locChoiceID.value = e.target.firstChild.value;}
                fire("change",locChoiceID);
            };
            locChoiceContainer.innerHTML = "";
            locChoiceContainer.appendChild(locdiv);
            locdiv.scrollIntoView();
        };
        locChoiceID.onchange = function() {
            output.value = locChoiceID.value;
            if (!doNotUpdate) {fire("change",output);}
        };
        document.getElementById("locClose").onclick = function () {
            locChoiceSelect.onchange = null;
            document.getElementById("locChoice").style.display = "none";
            document.getElementById("contentDiv").style.display = "block";
            if (document.getElementById("locFormDiv")) {document.getElementById("locFormDiv").scrollIntoView();}
            return false;
        };
        document.getElementById("locAccept").onclick = function() {
            fire("change", output);
            document.getElementById("locClose").click();
        };

        locChoiceID.value = output.value;
        fire("change",locChoiceID);

        document.getElementById("contentDiv").style.display = "none";
        document.getElementById("locChoice").style.display = "block";
        document.getElementById("locChoice").scrollIntoView();
    }
</script>

<div id="locChoice">
    <div id="locChoiceContent">
        <div id="locChoiceSelectDiv">
            <select id="locChoiceSelect" hidden><?php echo $locdivs;?></select>
            <select id="locChoiceID" hidden><?php echo $locs; ?></select>
        </div>
        <div id="locChoiceContainer"></div>
    </div>
    <div id="locChoiceHeader">
        <div class="divForm submit flex" onclick="this.firstElementChild.click();">X<button id="locClose">Close</button></div>
        <div class="divForm submit flex" onclick="this.firstElementChild.click();">&#x2714;<button id="locAccept">Close</button></div>
    </div>
</div>