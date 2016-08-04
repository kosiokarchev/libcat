function syncGet(url) {
    var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    http.open("GET",url,false);
    http.send(null);
    return http.responseText;
}

function fSubmit(f) {
    var varString = '';
    for (i=0; i<f.elements.length; i++) {
        var t=f.elements[i].type;

        if (t=="submit") {continue;}
        if (t=="select-multiple") {
            for (var j=0; j<f.elements[i].options.length; j++) {
                if (f.elements[i].options[j].selected) {
                    if(varString!='')  varString+='&';
                    varString += f.elements[i].name+'='+encodeURIComponent(f.elements[i].options[j].value);
                }
            }
        } else if (!(t=="radio" || t=="checkbox") || f.elements[i].checked) {
            if(varString!='')  varString+='&';
            varString += f.elements[i].name+'='+encodeURIComponent(f.elements[i].value);
        }
    }
    
    var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    http.open("POST",f.action,true);
    http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    http.onreadystatechange = function() {respond(http);};
    http.send(varString);
    return false;
}
function respond(http) {
    if(http.readyState==4 && http.status==200) {
        try {
            var response = JSON.parse(http.responseText);
            if (response.redir==null) {
                if (response.status) {alert("Success: " + response.msg); window.location.reload();}
                else {alert("Failure: " + response.msg);}
            } else if (response.status==1) {window.location.assign(response.redir);}
            else {window.location.replace(response.redir);}
        }
        catch (e) {alert(http.responseText);}
    }
}