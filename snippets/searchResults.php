<script>
    var json_books = '<?php if (isset($books)) echo json_encode($books,JSON_HEX_APOS); ?>';
    var json_authors = '<?php if (isset($authors)) echo json_encode($authors,JSON_HEX_APOS); ?>';
    
    window.addEventListener("load",function() {
        var books = genBooks(json_books,false,true);
        if (books) {
            var booksHeading = document.createElement("H1"); booksHeading.innerHTML = "Книги";
            document.getElementById("contentDiv").appendChild(booksHeading);
            document.getElementById("contentDiv").appendChild(books);
        }

        var authors = genAuthors(json_authors);
        if (authors) {
            authors.className="floating";
            var authorsHeading = document.createElement("H1"); authorsHeading.innerHTML = "Автори";
            document.getElementById("contentDiv").appendChild(authorsHeading);
            document.getElementById("contentDiv").appendChild(authors);
        }
    },false);
</script>
<div id="contentDiv">
</div>