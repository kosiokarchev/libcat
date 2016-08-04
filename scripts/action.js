function setCountVal(but, max, msg) {
    max = (typeof(max)!=='undefined') ? max : 10;
    msg = (typeof(msg)!=='undefined') ? msg : "без коментар";
    
    if (max==0) {alert("Sorry, zero books are available"); return false;}
    var form = but.form;
    form.elements["act"].value = but.value;
    var question = "Confirm ";
    question+= (but.value=="add") ? "adding copies:" :
               (but.value=="rem") ? "removing copies:" :
               (but.value=="lend") ? "lending copies:" :
               (but.value=="ret") ? "returning copies:" : "adding new book (# of copies):";
    var count = prompt(question,"1");
    while (true) {
        if (count==null) {break;}
        count = parseInt(count);
        if (count>=1 && count<=max) {
            form.elements["count"].value = count;
            if ((but.value=="ret" && count==max) || (but.value!="ret" && but.value!="lend")) {form.elements.msg.value = "";}
            else {form.elements["msg"].value = prompt("Set the comment:",msg);}
            fSubmit(form);
            return true;
        }
        else {count = prompt(question+" (between 1 and "+max+")","1");}
    }
    return false;
}