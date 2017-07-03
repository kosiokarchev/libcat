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

    var http = new XMLHttpRequest() || new ActiveXObject("Microsoft.XMLHTTP");
    http.open("POST",f.action,true);
    http.onreadystatechange = function() {func(http,onfail);};
    if (FormData) {http.send(new FormData(f));}
    else {
        http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        http.send(toVarString(f));
    }
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
            else {
                if (response.msg) alert(response.msg);
                window.location.replace(response.redir);
            }
        }
        catch (e) {alert(http.responseText);}
    }
}

function loadSubmit(f,img) {
    img = img ? img : document.getElementById("loadingImg");
    img.style.display = "flex";
    fSubmit(f,false,function() {img.style.display = "none";});
}