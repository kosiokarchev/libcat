function DynamicList(input,act,genLabel,genValue,compLabel,labelText,suggDiv,choiceDiv,choiceName,newDiv,newName) {
    var theList = this;
    var newInput;
    
    this.change = function() {
        theList.dynamicInput();
        if (newName) {
            deleteIn(newDiv);
            var vals = input.value.split(";");
            for (var i=0; i<vals.length; i++) {
                var val = vals[i].trim().replace(/,([^\s])/g, ', $1')
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
    input.onkeyup = this.change;
    
    this.dynamicInput = function() {
        if (input.nextElementSibling.classList.contains("mag_glass")) {
            input.nextElementSibling.innerHTML = "&#8635;"
        }
        var varString = "exec=1&act="+act+"&input="+input.value;

        var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
        http.open("POST",input.form.action,true);
        http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        http.send(varString);
        http.onreadystatechange = function() {
            if(http.readyState==4 && http.status==200) {
                if (input.nextElementSibling.classList.contains("mag_glass")) {
                    input.nextElementSibling.innerHTML = "&#128269;"
                }
                try {theList.output(JSON.parse(http.responseText));}
                catch (e) {alert(e); alert(http.responseText);}
            }
        };

        return false;
    };
    
    this.output = function(data) {
        suggDiv.innerHTML = '';
        for (var i=0; i < data.length; i++) {
            var label = genLabel(data[i]);
            var value = genValue(data[i]);
            if (!this.includedInChoice(label)) {
                var input = this.addSugg(value,label);

                input.onclick = function() {
                    var div,child;
                    if (this.checked) {div = choiceDiv;}
                    else {div = suggDiv;}
                    child = div.firstChild;
                    while (true) {
                        if (child) {
                            if ((this.checked && labelText(child.lastChild) > labelText(this.nextSibling)) ||
                                (!this.checked && compLabel(child.lastChild,this.nextSibling)==1)) {
                                div.insertBefore(this.parentNode, child); break;
                            }
                        } else {div.appendChild(this.parentNode); break;}
                        child = child.nextSibling;
                    }
                };
            }
            if (newName) {var dupl=this.includedNew(labelText(label)); if (dupl) {newDiv.removeChild(dupl);}}
        }
    };
    
    this.addNew = function(val) {
        var inputDiv = document.createElement("div");
        var input = document.createElement("input");
        input.type = "checkbox";
        input.name = newName+"[]";
        input.value = val;

        inputDiv.appendChild(input);
        inputDiv.appendChild(document.createTextNode(val));
        newDiv.insertBefore(inputDiv,newDiv.firstChild);

        return input;
    };
    
    this.addSugg = function(value,label) {
        var inputDiv = document.createElement("div");
        var input = document.createElement("input");
        input.type = "checkbox";
        input.name = choiceName+"[]";
        input.value = value;
        input.style.display = "none";

        inputDiv.appendChild(input);
        inputDiv.appendChild(label);
        suggDiv.appendChild(inputDiv);

        inputDiv.onclick = function() {this.firstChild.click();};
        inputDiv.style.cursor = "hand";

        return input;
    };

    this.includedInChoice = function(label) {
        var child = choiceDiv.firstChild;
        var incl = false;
        while (child) {
            if (!compLabel(child.lastChild,label)) {incl = true; break;}
            child = child.nextSibling;
        }
        return incl;
    };
    this.includedNew = function(text) {
        text = text.toLowerCase();
        var incl = false;
        var child = newDiv.firstChild;
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