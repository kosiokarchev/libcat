window.addEventListener("load",initPlace,false);

function initPlace() {
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
            moveOne(this.parentNode.firstChild.firstChild.value);
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
        refine.parentNode.parentNode.scrollIntoView();
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