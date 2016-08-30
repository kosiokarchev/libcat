var lastUpdate = 1471002010778;
var locdivs;
if (typeof(Storage)=="undefined") {
    locdivs = JSON.parse(loadLocs());
    console.log("Getting locs");
} else {
    if (!window.localStorage.locLoadDate || parseInt(window.localStorage.locLoadDate) < lastUpdate) {
        window.localStorage.locdivs = loadLocs();
        window.localStorage.locLoadDate = (new Date()).getTime();
    }
    locdivs = JSON.parse(window.localStorage.locdivs);
}

function loadLocs() {
    var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    http.open("GET","/locs.php",false);
    http.send(null);
    return http.responseText;
}

function moveOne(ID) {
    var moveForm = document.createElement("FORM");
    moveForm.method = "post";
    moveForm.action = "place.php";

    moveForm.appendChild(newInput());

    var IDinput = document.createElement("INPUT");
    IDinput.name = "bookIDs[]";
    IDinput.value = ID;
    moveForm.appendChild(IDinput);

    var moveTo = document.createElement("INPUT");
    moveTo.name = "locID";
    moveForm.appendChild(moveTo);
    moveTo.onchange = function () {
        fSubmit(this.form);
    };

    chooseLocation(moveTo,true);
}

function buildPlace(locdivID, radio, thumbnail) {
    var pars = locdivs[locdivID]; if (!pars) {return false;}
    var locdiv = document.createElement("DIV");
    locdiv.style.position = "relative";
    locdiv.style.boxSizing = "content-box";
    locdiv.style.width = pars[0]+"em";
    locdiv.style.height = pars[1]+"em";
    locdiv.style.border = "1px solid gray";

    var border = pars[2]+'em solid black';
    for (var i=0; i<pars[3].length; i++) {
        var loc = document.createElement("DIV");
        var ID = pars[3][i][0];
        var locpars = pars[3][i][1];
        loc.style.boxSizing = "border-box";
        loc.style.position = "absolute";
        loc.style.top = locpars[1]+"em";
        loc.style.left = locpars[2]+"em";
        loc.style.width = locpars[3]+"em";
        loc.style.height = locpars[4]+"em";
        if (locpars[5]=="1") loc.style.borderTop = border;
        if (locpars[6]=="1") loc.style.borderLeft = border;
        if (locpars[7]=="1") loc.style.borderBottom = border;
        if (locpars[8]=="1") loc.style.borderRight = border;

        var locID = document.createElement("input");
        if (radio) {locID.type = 'radio';}
        else {locID.type = 'checkbox';}
        locID.checked = false;
        locID.name = "locIDs[]";
        locID.value = ID;
        locID.style.display = "none";

        loc.appendChild(locID);
        // loc.appendChild(document.createTextNode(locpars[0]));

        if (!thumbnail) {
            loc.onclick = function() {
                this.firstChild.checked = !this.firstChild.checked;
                var subdivs = document.getElementsByName("locIDs[]");
                for (var i=0; i<subdivs.length; i++) {
                    if (subdivs[i].checked) {subdivs[i].parentNode.style.backgroundColor = "rgba(0,255,0,0.25)";}
                    else {subdivs[i].parentNode.style.backgroundColor = "transparent";}
                }
            };
        }
        locdiv.appendChild(loc);
    }
    // locdiv.style.fontSize = "1px";
    return locdiv;
}

function buildThumbnail(id) {
    var locdivID = locdivById(id);
    if (locdivID) {
        var locdiv = buildPlace(locdivID[0],true,true);
        locdiv.childNodes[locdivID[1]].style.backgroundColor = "lime";
        return locdiv;
    } else {return false;}
}

function locdivById(id) {
    if (id==1) {return false;}
    for (var locdivID in locdivs) {
        var subdivs = locdivs[locdivID][3];
        for (var i=0; i<subdivs.length; i++) {
            if (subdivs[i][0]==id) {return [locdivID,i];}
        }
    }
    return false;
}