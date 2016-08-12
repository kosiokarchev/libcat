<script>
    window.addEventListener("load",function() {
        var books = genBooks(false,true);
        if (books) {
            var booksHeading = document.createElement("H1"); booksHeading.innerHTML = "Книги";
            document.getElementById("contentDiv").appendChild(booksHeading);
            document.getElementById("contentDiv").appendChild(books);
        }

        var authors = genAuthors();
        if (authors) {
            authors.className="floating";
            var authorsHeading = document.createElement("H1"); authorsHeading.innerHTML = "Автори";
            document.getElementById("contentDiv").appendChild(authorsHeading);
            document.getElementById("contentDiv").appendChild(authors);
        }
    },false);
</script>
<div id="json_books" hidden><?php if (isset($books)) echo htmlentities(json_encode($books)); ?></div>
<div id="json_authors" hidden><?php if (isset($authors)) echo htmlentities(json_encode($authors)); ?></div>
<div id="contentDiv">
</div>