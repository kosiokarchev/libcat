var locdivs;
function initPlace() {
    locdivs = JSON.parse(syncGet("snippets/locs.php"));
    var locID = document.getElementById("locID");
    var choiceCount = document.getElementById("choiceCount");
    var bookSugg = document.getElementById("bookSugg");
    var bookChoice = document.getElementById("bookChoice");

    var button;
    var bookDivs = document.getElementsByClassName("book");
    for (var i=0; i<bookDivs.length; i++) {
        var input = bookDivs[i].firstChild.firstChild.firstChild;
        input.onclick = function(event) {
            if (this.checked) {bookChoice.appendChild(this.parentNode.parentNode.parentNode);}
            else {bookSugg.insertBefore(this.parentNode.parentNode.parentNode,bookSugg.firstChild)}
            choiceCount.innerHTML = bookChoice.childNodes.length;
            event.stopPropagation();
        };

        button = document.createElement("DIV");
        button.className = "moveButton";
        button.onclick = function(event) {
            event.stopPropagation();

            var moveForm = document.createElement("FORM");
            moveForm.method = "post";
            moveForm.action = "place.php";

            moveForm.appendChild(newExec());
            moveForm.appendChild(this.parentNode.firstChild.firstChild);

            var moveTo = document.createElement("INPUT");
            moveTo.name = "locID";
            moveForm.appendChild(moveTo);
            moveTo.onchange = function () {
                this.form.elements["bookIDs[]"].checked = true;
                fSubmit(this.form);
            };

            chooseLocation(moveTo,true);
        };
        bookDivs[i].firstChild.appendChild(button);
    }

    var locFormDiv = document.getElementById("locFormDiv");
    locID.onchange = function () {
        this.nextElementSibling.innerHTML = "";
        var thumbnail = buildThumbnail(this.value);
        if (thumbnail) {
            var width = parseInt(thumbnail.style.width.split("em")[0]);
            var height = parseInt(thumbnail.style.height.split("em")[0]);
            var dim = height>width ? height : width;
            thumbnail.style.fontSize = (23/(24*dim)) + "in";
            // thumbnail.style.border = "none";
            this.nextElementSibling.appendChild(thumbnail,0.5);
        }
        else {
            var mess = document.createElement("DIV");
            mess.className = "labelIcon labelloc question";
            mess.innerHTML = "?";
            this.nextElementSibling.appendChild(mess);
        }
    };
    locFormDiv.style.display = "flex";

    var refine = document.getElementById("refine");
    refine.onkeyup = function () {
        var books = bookSugg.childNodes;
        if (this.value.length < 3) {
            for (var i=0; i<books.length; i++) {books[i].style.display = "block";}
        } else {
            for (var i=0; i<books.length; i++) {
                if (books[i].textContent.toLowerCase().indexOf(this.value.toLowerCase())==-1) {books[i].style.display = "none";}
                else {books[i].style.display = "block";}
            }
        }
    };
    function searchOnTop() {
        var refinePos = bookChoice.getBoundingClientRect();
        refinePos = refinePos.top + refinePos.height;
        if (refinePos < 0) {
            refine.parentNode.parentNode.style.position = "fixed";
            refine.parentNode.parentNode.style.top = "0";
            refine.parentNode.parentNode.style.left = document.getElementsByTagName("body")[0].getBoundingClientRect().left+"px";
        } else {refine.parentNode.parentNode.style.position = "initial";}
    }
    window.onscroll = searchOnTop;
    searchOnTop();
}

function newExec() {
    var exec = document.createElement("INPUT");
    exec.name = "exec"; exec.value = "1";
    return exec;
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
    var thisLocdivID = false;
    for (locdivID in locdivs) {
        var subdivs = locdivs[locdivID][3];
        for (var i=0; i<subdivs.length; i++) {
            if (subdivs[i][0]==id) {thisLocdivID=locdivID; break;}
        }
        if (thisLocdivID) {break;}
    }
    return [thisLocdivID,i];
}

function buildPlace(locdivID, radio, thumbnail) {
    var pars = locdivs[locdivID];
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
        loc.appendChild(document.createTextNode(locpars[0]));

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

    return locdiv;
}

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