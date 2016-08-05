window.addEventListener("load",function() {
    ISBNChecker(document.getElementsByName("ISBN")[0]);
},false);
window.addEventListener("load",function() {
    var input = document.getElementById("authorInput");
    var suggDiv = document.getElementById("authorOutput");

    function genValue(data) {return data[0];}
    function compLabel(label1,label2) {
        if (label1.lastChild.innerHTML < label2.lastChild.innerHTML) return 1;
        else if (label1.lastChild.innerHTML==label2.lastChild.innerHTML) return 0;
        else return -1;
    }
    function labelText(label) {return label.firstChild.innerHTML;}
    var dynAuthor = new DynamicList({"input":input, "action":"search.php", "genLabel":genAuthor, "suggDiv":suggDiv});
    document.getElementById("dynAuthor").onclick = function() {
        dynAuthor.input.focus();
        dynAuthor.on = this.checked;
        if (this.checked) {dynAuthor.change();}
        else {dynAuthor.suggDiv.innerHTML = '';}
        this.parentNode.style.backgroundColor = this.checked ? 'cornflowerblue' : 'grey';
    }
},false);

function newExt() {
    var img = document.createElement("IMG");
    img.src = "/Images/icons/ext.png";
    return img;
}

function genAuthor(data) {
    var div = document.createElement("DIV");
    div.className = "author row";
        var a = document.createElement("A");
        a.href = "search.php?authorID="+data[0];
        a.target = "_blank";
            var title = document.createElement("DIV");
            title.className = "title";
                title.appendChild(document.createTextNode(data[1]+" "));
                title.appendChild(newExt());
            a.appendChild(title);
        div.appendChild(a);
    return div;
}