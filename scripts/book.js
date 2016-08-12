window.addEventListener("load",function() {
    var locID = document.getElementById("locID");
    var cWidth = (locID.parentNode.clientWidth || 480) - 16;
    var cHeight = (window.innerHeight || cWidth) - 16;

    var thumb = buildThumbnail(locID.value);
    if (thumb) {
        var width = parseInt(thumb.style.width.split("em")[0]);
        var height = parseInt(thumb.style.height.split("em")[0]);
        if (height * cWidth / width > cHeight) {
            if (cHeight > 480) {cHeight = 480;}
            thumb.style.fontSize = (cHeight/height) + "px";
        } else {
            if (cWidth > 640) {cWidth = 640;}
            thumb.style.fontSize = (cWidth/width) + "px";
        }
        thumb.style.backgroundColor = "cornflowerblue";
        thumb.style.marginBottom = "0.1in";
        locID.parentNode.style.flexDirection = "column";
        locID.parentNode.insertBefore(thumb,locID);
    } else {
        locID.parentNode.insertBefore(document.createTextNode("между другото"),locID);
    }

    document.getElementById("moveButton").onclick = function() {
        moveOne(this.firstElementChild.value);
    }
},false);