window.addEventListener("load",initHeader,false);

function initHeader() {
    var headerDiv = document.getElementById("header");
    var searchDiv = document.getElementById("searchDiv");
    var menuDiv = document.getElementById("menuDiv");

    if (document.getElementsByClassName("headerExtension").length) {headerDiv.style.boxShadow = "none";}

    searchDiv.hid = true;
    menuDiv.hid = true;

    document.getElementById("showSearch").onclick = function() {showhide(searchDiv,menuDiv);};
    document.getElementById("showMenu").onclick = function() {
        window.location = "/";
        // showhide(menuDiv,searchDiv);
    };

    function showhide(div,otherdiv) {
        if (div.hid) {hide(otherdiv); show(div);}
        else {hide(div);}
    }
    function show(div) {div.style.top = "50px"; div.hid=false; headerDiv.style.height = "100px";}
    function hide(div) {div.style.top = "0"; div.hid = true;
        if (searchDiv.hid && menuDiv.hid) {headerDiv.style.height = "50px";}
    }

    initCam();
}

function initCam() {
    var cameraButton = document.getElementById("cameraButton");
    var imgInput = document.getElementById("imgInput");
    var ISBNinput = document.getElementById("ISBNinput");
    
    imgInput.onchange = scanImgBarcode;
    cameraButton.onclick = function () {imgInput.click();};

    function scanImgBarcode() {
        if(FileReader) {
            var fr = new FileReader();
            fr.onload = function() {getBarcodeData(fr.result);};
            fr.readAsDataURL(this.files[0]);
        }
        else {alert("FileReader not supported.");}
    }

    function getBarcodeData(b64) {
        var config = {decoder: {readers:["ean_reader"]},
                      locate: false,
                      src: b64};
        Quagga.decodeSingle(config,function(result){
            if (result.codeResult) {
                if (confirm("Потърсете ISBN "+result.codeResult.code+"?")) {
                    ISBNinput.value = result.codeResult.code;
                    ISBNinput.name = "ISBN";
                    ISBNinput.form.submit();
                }
            }
            else {
                alert("Баркод не бе намерен.");
                while (true) {
                    var ISBN = prompt("Въведете ISBN ръчно:");
                    if (ISBN) {
                        if (verifyISBN(ISBN) || confirm("Въведеният ISBN "+ISBN+ " не е валиден. Желаете ли все пак да го използвате?")) {
                            ISBNinput.value = ISBN;
                            ISBNinput.name = "ISBN";
                            ISBNinput.form.submit();
                            break;
                        }
                    } else {break;}
                }
            }
        });
    }
}

function ISBNChecker(element) {
    checkISBN(element);
    element.nextElementSibling.onclick = function() {checkISBN(element);}
    element.onkeyup = function() {checkISBN(this)};
    element.onchange = function() {checkISBN(this)};
}

function checkISBN(element) {
    element.value = element.value.replace(/[^0-9](.)/,'$1').replace(/[^0-9Xx]$/,'');
    if (!verifyISBN(element.value)) {
        element.nextElementSibling.style.display = "flex";
        element.nextElementSibling.nextElementSibling.style.display = 'none';
        if (element.nextElementSibling.firstElementChild.checked) {
            element.style.backgroundColor = "lightgreen";
            return true;
        }
        else {element.style.backgroundColor = "lightcoral"; return false;}
    } else {
        element.nextElementSibling.style.display = "none";
        element.nextElementSibling.firstElementChild.checked = false;
        element.style.backgroundColor = "lightgreen";
        element.nextElementSibling.nextElementSibling.style.display = 'flex';
        return true;
    }
}

function checksumISBN10(ISBN) {
    var sum = 0;
    for (var i=0; i<9; i++) {
        sum += parseInt(ISBN[i])*(10-i);
    }
    var ch = 11-sum%11;
    if (ch==11) {ch='0';}
    else if (ch==10) {ch='X';}
    else {ch = ch.toString();}
    return ch;
}
function checksumISBN13(ISBN) {
    var sum = 0;
    for (var i=0;i<12;i++) {
        sum += parseInt(ISBN[i])*((i%2)*2+1);
    }
    var ch = 10-sum%10;
    if (ch==10) {ch='0';}
    else {ch=ch.toString();}
    return ch;
}
function verifyISBN(ISBN) {
    if (/^[0-9]{9}[0-9Xx]$/.exec(ISBN)) {return checksumISBN10(ISBN)==ISBN[9].toUpperCase();}
    else if (/^[0-9]{13}$/.exec(ISBN)) {return checksumISBN13(ISBN)==ISBN[12];}
    else {return false;}
}

function fire(event,element) {
    if (document.createEvent) {
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent(event, false, true);
        element.dispatchEvent(evt);
    } else {element.fireEvent("on"+event);}
}
function newExt() {
    var img = document.createElement("IMG");
    img.src = "/Images/icons/ext.png";
    return img;
}
function newInput(name,value) {
    name = name ? name : "exec";
    value = value ? value : "1";
    var exec = document.createElement("INPUT");
    exec.name = name; exec.value = value; exec.hidden = true;
    return exec;
}
function divWithClass(c) {
    var div = document.createElement("DIV");
    div.className = c;
    return div;
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