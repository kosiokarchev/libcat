function genBooks(books,check,action) {
    if (!books) {return false;}
    books = JSON.parse(books);
    var booksDiv = document.createElement("DIV");
    for (var i=0; i<books.length; i++) {
        booksDiv.appendChild(genBook(books[i],check,action));
    }
    return booksDiv;
}

function genBook(data,check,action) {
    var bookID = data[0];
    var title = data[3];
    var author = data[4];
    var year = data[5];
    var lang = data[14];
    var locID = data[6];
    var loc = data[7];
    var ISBN = data[1];
    var count = data[11]=="0" ? data[8] : data[8]+" - "+data[11]+" = "+(parseInt(data[8])-parseInt(data[11]));
    var comment = data[12];
    var bookDiv = divWithClass("book row"); bookDiv.id = "book_"+bookID;
        var titleDiv = divWithClass("title"); titleDiv.setAttribute("title",title);
            var titleContainer = divWithClass("titleContainer");
                titleContainer.innerHTML = title+" ";
                // titleContainer.appendChild(document.createElement("BR"));
                var link = document.createElement("A"); link.href = "book.php?ID="+bookID; link.target = "_blank";
                    link.appendChild(newExt());
                    // link.innerHTML = "виж още";
                link.onclick = function(event) {event.stopPropagation(); return true;};
                titleContainer.appendChild(link);
        if (check) {
            var checkContainer = divWithClass("checkContainer flex");
                var checkbox = document.createElement("INPUT");
                    checkbox.setAttribute("type","checkbox"); checkbox.setAttribute("name",check); checkbox.value = bookID;
                checkbox.addEventListener("click",function(event) {event.stopPropagation();},false);
                checkContainer.appendChild(checkbox);
            titleDiv.appendChild(checkContainer);
            titleContainer.style.paddingLeft = "1em";
        }
            titleDiv.appendChild(titleContainer);
        bookDiv.appendChild(titleDiv);

        var additionalDiv = divWithClass("additional");
            var info = divWithClass("info");
                info.appendChild(row("автор","author",author));
                if (year) info.appendChild(row("година","year",year));
                info.appendChild(row("език","lang",lang));
            if (locID==1) {
                info.appendChild(row("местоположение","loc",loc));
            } else {
                    var locCont = divWithClass("locCont");
                        var locA = document.createElement("A"); locA.innerHTML = loc;
                            locA.href = "thumbnail.html#"+locID; locA.target = "_blank";
                        locCont.appendChild(locA);
                        var thumb = buildThumbnail(locID);
                    if (thumb) {
                        var width = parseInt(thumb.style.width.split("em")[0]);
                        var height = parseInt(thumb.style.height.split("em")[0]);
                        var dim = height>width ? height : width;
                        thumb.style.fontSize = (2/dim) + "in";
                        thumb.style.backgroundColor = "cornflowerblue";

                        var thumbDiv = divWithClass("thumb");
                            var thumbCont = divWithClass("thumbCont"); thumbCont.appendChild(thumb);
                            thumbDiv.appendChild(thumbCont);
                        locCont.appendChild(thumbDiv);
                    }
                info.appendChild(row("", "loc", locCont));
            }
                if (ISBN) info.appendChild(row("ISBN","ISBN",ISBN));
                info.appendChild(row("екземпляри","copies",count));
                if (comment) info.appendChild(row("коментар","comment",comment));
            additionalDiv.appendChild(info);
        if (action) {
            additionalDiv.appendChild(genBookActions(bookID,parseInt(data[8]),parseInt(data[11]),data[12]));

        }
        bookDiv.appendChild(additionalDiv);


    if (typeof(Storage)!=="undefined" && window.sessionStorage.getItem(bookDiv.id)) {
        titleContainer.style.whiteSpace = "normal";
        titleContainer.style.height = "auto";
        additionalDiv.style.height = "auto";
    }
    titleDiv.onclick = function() {
        if (titleContainer.style.height=="auto") {
            titleContainer.style.whiteSpace = "nowrap";
            titleContainer.style.height = "1.2em";
            additionalDiv.style.height = 0;
            if (typeof(Storage)!=="undefined") {window.sessionStorage.removeItem(bookDiv.id);}
        } else {
            titleContainer.style.whiteSpace = "normal";
            titleContainer.style.height = "auto";
            additionalDiv.style.height = "auto";
            if (typeof(Storage)!=="undefined") {window.sessionStorage.setItem(bookDiv.id,1);}
            if (window.innerHeight - bookDiv.getBoundingClientRect().bottom < 0) {bookDiv.scrollIntoView(false);}
        }
    };
    var rowCont = document.createElement("DIV"); rowCont.style.position = "relative";
    rowCont.appendChild(bookDiv);
    return rowCont;
}
function row(title, label, data) {
    if (typeof(data)!="object") {
        var dummy = document.createElement("DIV");
        dummy.innerHTML = data;
        data = document.createTextNode(dummy.textContent);
    }
    var row = divWithClass("addrow"); row.setAttribute("title",title);
        var labelDiv = divWithClass("labelIcon label"+label); row.appendChild(labelDiv);
        var dataDiv = divWithClass("data"); dataDiv.appendChild(data); row.appendChild(dataDiv);
    return row;
}
function genBookActions(ID,count,lended,comment,c) {
    c = c ? c : "";
    var actions = divWithClass("actions "+c);
    var form = document.createElement("FORM"); form.action = "book.php";
        form.appendChild(newInput()); form.appendChild(newInput("act","")); form.appendChild(newInput("count","")); form.appendChild(newInput("msg",""));
        form.appendChild(newInput("ID",ID));
        var actionContainer = divWithClass("actionContainer");
            var add = divWithClass("bookIcon bookplus"); add.title = "Добави екземпляр";
                add.onclick = function() {setCountVal(form,"add");};
            actionContainer.appendChild(add);
            var rem= divWithClass("bookIcon bookminus"); rem.title = "Премахни екземпляр";
                rem.onclick = function() {setCountVal(form,"rem", count-lended);};
            actionContainer.appendChild(rem);
            var lend = divWithClass("bookIcon bookout"); lend.title = "Отдай екземпляр";
                lend.onclick = function() {setCountVal(form,"lend", count-lended, comment);};
            actionContainer.appendChild(lend);
        if (lended) {
            var ret = divWithClass("bookIcon bookin"); ret.title = "Добави екземпляр";
                ret.onclick = function () {setCountVal(form, "ret", lended, comment);};
            actionContainer.appendChild(ret);
        }
        actions.appendChild(actionContainer);
    return actions;
}

function genAuthors(authors) {
    if (!authors) {return false;}
    authors = JSON.parse(authors);
    var authorsDiv = document.createElement("DIV");
    for (var i=0; i<authors.length; i++) {
        authorsDiv.appendChild(genAuthor(authors[i]));
    }
    return authorsDiv;
}
function genAuthor(data) {
    var div = document.createElement("DIV");
    div.className = "author row";
    var a = document.createElement("A");
    a.href = "search.php?authorID="+data[0];
    a.target = "_blank";
    var title = document.createElement("DIV");
    title.className = "title";
    title.innerHTML = data[1]+" ";
    title.appendChild(newExt());
    a.appendChild(title);
    div.appendChild(a);
    return div;
}