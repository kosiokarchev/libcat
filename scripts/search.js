window.addEventListener("load",function() {
    ISBNChecker(document.getElementsByName("ISBN")[0]);
},false);
window.addEventListener("load",function() {
    var input = document.getElementById("authorInput");
    var suggDiv = document.getElementById("authorOutput");
    
    var dynAuthor = new DynamicList({"input":input, "action":"search.php", "genLabel":genAuthor, "suggDiv":suggDiv});
    document.getElementById("dynAuthor").onclick = function() {
        dynAuthor.input.focus();
        dynAuthor.on = this.checked;
        if (this.checked) {dynAuthor.change();}
        else {dynAuthor.suggDiv.innerHTML = '';}
        this.parentNode.style.backgroundColor = this.checked ? 'cornflowerblue' : 'grey';
    }
},false);