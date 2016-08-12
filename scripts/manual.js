var form;
var langInputDiv;
var authNew,authChoice,authSugg;
var authList;
var addSeriesButton;
var seriesRow = null;
window.addEventListener("load",initManual,false);

function initManual() {
    form = document.getElementById("newForm");
    authNew = document.getElementById("authNew");
    authChoice = document.getElementById("authChoice");
    authSugg = document.getElementById("authSugg");
    langInputDiv = document.getElementById("langInputDiv");
    addSeriesButton = document.getElementById("addSeriesButton");
    
    form.onsubmit = function () {
        if (checkISBN(this.elements["ISBN"])) {loadSubmit(this);}
        else {alert('ISBN is invalid. Check the box next to it to allow it anyway.');}
        return false;
    };

    ISBNChecker(form.elements["ISBN"]);

    selectChange(form.elements["langID[]"],langInputDiv);
    form.elements["langID[]"].onchange = function() {selectChange(this,langInputDiv);};
    form.elements["author"].onclick = function() {this.select();};

    function genLabel(data) {
        var text = document.createElement("SPAN");
        text.appendChild(document.createTextNode(data[1]));
        var weight = document.createElement("SPAN");
        weight.style.display = "none";
        weight.innerHTML = data[2];
        var span = document.createElement("SPAN");
        span.appendChild(text); span.appendChild(weight);
        return span;
    }
    function genValue(data) {return data[0];}
    function compLabel(label1,label2) {
        if (label1.lastChild.innerHTML < label2.lastChild.innerHTML) return 1;
        else if (label1.firstChild.innerHTML==label2.firstChild.innerHTML) return 0;
        else return -1;
    }
    function labelText(label) {return label.firstChild.innerHTML;}
    authList = new DynamicList({"input":form.elements["author"], "act":"searchAuthor", "genLabel":genLabel, "genValue":genValue, "compLabel":compLabel, "labelText":labelText, "suggDiv":authSugg, "choice":{"choiceDiv":authChoice, "choiceName":"authorID"}, "new":{"newDiv":authNew, "newName":"authorNew"}});
    // authList = new DynamicList(form.elements["author"],"searchAuthor",genLabel,genValue,compLabel,labelText,authSugg,authChoice,"authorID",authNew,"authorNew");
    authList.change();
    authList.on = true;

    addSeriesButton.onclick = function() {
        this.firstChild.checked = !this.firstChild.checked;
        if (this.firstChild.checked) {
            series(true);
            this.style.backgroundColor = "lightgreen";
        } else {
            series(false);
            this.style.backgroundColor = "";
        }
    }
}

function series(show) {
    if (show) {
        if (!seriesRow) {
            seriesRow = document.createElement("DIV");
            seriesRow.className = "row";

                var infoLabel = document.createElement("DIV");
                infoLabel.className = "infoLabel";
                infoLabel.innerHTML = "Поредица";
                seriesRow.appendChild(infoLabel);

                var dataDiv = document.createElement("DIV");
                dataDiv.className = "dataDiv";

                    var select = document.  createElement("SELECT");
                    select.name = "seriesID";
                        var seriesData = JSON.parse(syncGet("snippets/seriesData.php"));
                        var option;
                        for (var i=0; i<seriesData.length; i++) {
                            option = document.createElement("OPTION");
                            option.value = seriesData[i][0];
                            option.innerHTML = seriesData[i][1];
                            select.appendChild(option);
                        }
                        option = document.createElement("OPTION");
                        option.value = "0"; option.innerHTML = "--друга--";
                        select.appendChild(option);
                    dataDiv.appendChild(select);
                    dataDiv.appendChild(document.createElement("BR"));

                    var inputDiv = document.createElement("DIV");
                        var input = document.createElement("INPUT");
                        input.name = "seriesName";
                        input.type = "text";
                        inputDiv.appendChild(input);
                        inputDiv.appendChild(document.createElement("BR"));
                    dataDiv.appendChild(inputDiv);

                    var seriesNumDiv = document.createElement("DIV");
                        seriesNumDiv.appendChild(document.createTextNode("#"));
                        var seriesNum = document.createElement("INPUT");
                        seriesNum.name = "seriesNum";
                        seriesNum.type = "number";
                        seriesNum.style.width = "3em";
                        seriesNumDiv.appendChild(seriesNum);
                    dataDiv.appendChild(seriesNumDiv);

                seriesRow.appendChild(dataDiv);

            selectChange(select,inputDiv);
            select.onchange = function() {selectChange(this,inputDiv);};

            var nextNode = addSeriesButton.parentNode.parentNode.nextSibling;
            nextNode.parentNode.insertBefore(seriesRow,nextNode);
        } else {seriesRow.style.display = "block";}
    } else {seriesRow.style.display = "none";}
    return true;
}

function selectChange(select,input) {
    if (select.lastChild.selected) {input.style.display = 'block';}
    else {input.style.display = 'none';}
}