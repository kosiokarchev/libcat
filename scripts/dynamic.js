function DynamicList(pars) {
    var input = pars["input"];
    var action = pars["action"] ? pars["action"] : input.form.action;
    var theList = this;
    var newInput;

    this.on=false;
    this.input = input;
    this.suggDiv = pars["suggDiv"];

    this.change = function() {
        theList.dynamicInput();
        if (pars["new"]) {
            deleteIn(pars["new"]["newDiv"]);
            var vals = input.value.split(";");
            for (var i=0; i<vals.length; i++) {
                var val = vals[i].trim().replace(/,([^\s])/g, ', $1');
                var sep = val.lastIndexOf(" ");
                var comma = val.indexOf(",");
                if (comma == val.length - 1) {
                    val = val.substr(0, val.length - 1);
                }
                else if (comma == -1 && sep != -1) {
                    val = val.substr(sep + 1) + ", " + val.substr(0, sep);
                }
                if (val != "" && !theList.includedNew(val)) {
                    newInput = theList.addNew(val);
                }
            }
        }
    };
    input.onkeyup = function () {if (theList.on) theList.change();};

    this.dynamicInput = function() {
        if (input.nextElementSibling.classList.contains("mag_glass")) {
            input.nextElementSibling.innerHTML = "&#8635;";
        }
        var varString = "exec=1&input="+input.value;
        varString+= pars["act"] ? "&act="+pars["act"] : "";

        var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
        http.open("POST",action,true);
        http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        http.onreadystatechange = function() {
            if(http.readyState==4 && http.status==200) {
                if (input.nextElementSibling.classList.contains("mag_glass")) {
                    input.nextElementSibling.innerHTML = "&#128269;"
                }
                try {theList.output(JSON.parse(http.responseText));}
                catch (e) {alert(e); alert(http.responseText);}
            }
        };
        http.send(varString);

        return false;
    };

    this.output = function(data) {
        pars["suggDiv"].innerHTML = '';
        for (var i=0; i < data.length; i++) {
            var label = pars["genLabel"](data[i]);
            var value = (pars["genValue"] && pars["choice"]) ? pars["genValue"](data[i]) : false;
            if (!(pars["choice"] && this.includedInChoice(label))) {
                var input = this.addSugg(value,label);
                if (input) {
                    input.onclick = function() {
                        var div,child;
                        if (this.checked) {div = pars["choice"]["choiceDiv"];}
                        else {div = pars["suggDiv"];}
                        child = div.firstChild;
                        if (!pars["compLabel"]) {
                            if (child) {div.insertBefore(this.parentNode, child);}
                            else {div.appendChild(this.parentNode);}
                            return;
                        }
                        while (true) {
                            if (child) {
                                if ((this.checked && pars["labelText"](child.lastChild) > pars["labelText"](this.nextSibling)) ||
                                    (!this.checked && pars["compLabel"](child.lastChild,this.nextSibling)==1)) {
                                    div.insertBefore(this.parentNode, child); break;
                                }
                            } else {div.appendChild(this.parentNode); break;}
                            child = child.nextSibling;
                        }
                    };
                }
            }
            if (pars["new"]) {var dupl=this.includedNew(pars["labelText"](label)); if (dupl) {pars["new"]["newDiv"].removeChild(dupl);}}
        }
    };

    this.addNew = function(val) {
        var inputDiv = document.createElement("div");
        var input = document.createElement("input");
        input.type = "checkbox";
        input.name = pars["new"]["newName"]+"[]";
        input.value = val;

        inputDiv.appendChild(input);
        inputDiv.appendChild(document.createTextNode(val));
        pars["new"]["newDiv"].insertBefore(inputDiv,pars["new"]["newDiv"].firstChild);

        return input;
    };

    this.addSugg = function(value,label) {
        var inputDiv = document.createElement("div");
        if (value!==false) {
            var input = document.createElement("input");
            input.type = "checkbox";
            input.name = pars["choice"]["choiceName"]+"[]";
            input.value = value;
            input.style.display = "none";
            inputDiv.appendChild(input);
            
            inputDiv.onclick = function() {this.firstChild.click();};
            inputDiv.style.cursor = "hand";
        }
        inputDiv.appendChild(label);
        pars["suggDiv"].appendChild(inputDiv);

        return input;
    };

    this.includedInChoice = function(label) {
        var child = pars["choice"]["choiceDiv"].firstChild;
        var incl = false;
        while (child) {
            if (pars["compLabel"] && !pars["compLabel"](child.lastChild,label)) {incl = true; break;}
            child = child.nextSibling;
        }
        return incl;
    };
    this.includedNew = function(text) {
        text = text.toLowerCase();
        var incl = false;
        var child = pars["new"]["newDiv"].firstChild;
        while (child) {
            if (child.lastChild.data.toLowerCase() == text) {incl = child; break;}
            child = child.nextSibling;
        }
        return incl;
    };

}

function deleteIn(div) {
    var children = div.children;
    for (var i=children.length-1; i>=0; i--) {
        if (!children[i].firstChild.checked) {div.removeChild(children[i]);}
    }
}