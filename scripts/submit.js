function syncGet(url) {
    var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    http.open("GET",url,false);
    http.send(null);
    return http.responseText;
}

function toVarString(form) {
    var varString = '';
    for (i=0; i<form.elements.length; i++) {
        var t=form.elements[i].type;

        if (t=="submit") {continue;}
        if (t=="select-multiple") {
            for (var j=0; j<form.elements[i].options.length; j++) {
                if (form.elements[i].options[j].selected) {
                    if(varString!='')  varString+='&';
                    varString += form.elements[i].name+'='+encodeURIComponent(form.elements[i].options[j].value);
                }
            }
        } else if (!(t=="radio" || t=="checkbox") || form.elements[i].checked) {
            if(varString!='')  varString+='&';
            varString += form.elements[i].name+'='+encodeURIComponent(form.elements[i].value);
        }
    }

    return varString;
}

function fSubmit(f,func,onfail) {
    func = func ? func : respond;
    var varString = toVarString(f);
    
    var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    http.open("POST",f.action,true);
    http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    http.onreadystatechange = function() {func(http,onfail);};
    http.send(varString);
    return false;
}
function respond(http,onfail) {
    if(http.readyState==4 && http.status==200) {
        try {
            var response = JSON.parse(http.responseText);
            if (response.redir==null) {
                if (response.status) {alert("Success: " + response.msg); window.location.reload();}
                else {
                    if (onfail) {onfail();}
                    alert("Failure: " + response.msg);
                }
            } else if (response.status==1) {window.location.assign(response.redir);}
            else {window.location.replace(response.redir);}
        }
        catch (e) {alert(http.responseText);}
    }
}