function login(http) {
    if(http.readyState==4 && http.status==200) {
        try {
            var response = JSON.parse(http.responseText);
            if (response.status) {
                alert("Вписването бе успешно.");
                var redir = document.getElementById("redir");
                if (redir.nodeName=="DIV") {
                    window.location.assign(redir.innerHTML);
                } else {redir.submit();}
            } else {alert("Въведената парола е грешна.");}
        }
        catch (e) {alert(http.responseText);}
    }
}
