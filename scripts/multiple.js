function initMultiple(booksDivId) {
    booksDivId = booksDivId ? booksDivId : "books";
    var books = document.getElementById(booksDivId);
    if (!books) {return;}
    var bookDivs = books.getElementsByTagName("div");
    for (var i=0; i<bookDivs.length; i++) {
        if (bookDivs[i].className != "book row") {continue;}
        var ID = bookDivs[i].id.split("_")[1];
        if (getCookie('ID_'+ID)) {
            bookDivs[i].children[0].children[1].style.whiteSpace = "normal";
            bookDivs[i].children[0].children[1].style.height = "auto";
            bookDivs[i].children[1].style.height = "auto";
        }

        bookDivs[i].firstChild.onclick = _open;
    }
}

function _open(event) {openAdditional(this);}

function openAdditional(title) {
    var ID = title.parentNode.id.split("_")[1];

    if (title.children[1].style.height=="auto") {
        title.children[1].style.whiteSpace = "nowrap";
        title.children[1].style.height = "1.2em";
        title.nextElementSibling.style.height = 0;
        setCookie('ID_'+ID,false);
    } else {
        title.children[1].style.whiteSpace = "normal";
        title.children[1].style.height = "auto";
        title.nextElementSibling.style.height = "auto";
        setCookie('ID_'+ID,1);
    }
}

function setCookie(name,val) {
    if (val) {document.cookie = name+"="+val;}
    else {document.cookie = name+"=; expires=Thu, 01 Jan 1970 00:00:00 UTC"}
}
function getCookie(name) {
    var cookies = document.cookie.split(";");
    for (var i=0; i<cookies.length; i++) {
        var index = cookies[i].indexOf(name+"=");
        if (index >- 1) {return cookies[i].substr(index+name.length+1);}
    }
}