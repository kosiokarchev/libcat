function setCountVal(form, val, max, msg) {
    max = max ? max : 10;
    msg = msg ? msg : "без коментар";
    
    if (max==0) {alert("Sorry, zero books are available"); return false;}
    form.elements["act"].value = val;
    var question = "Confirm ";
    question+= (val=="add") ? "adding copies:" :
               (val=="rem") ? "removing copies:" :
               (val=="lend") ? "lending copies:" :
               (val=="ret") ? "returning copies:" : "adding new book (# of copies):";
    var count = prompt(question,"1");
    while (true) {
        if (count==null) {break;}
        count = parseInt(count);
        if (count>=1 && count<=max) {
            form.elements["count"].value = count;
            if ((val=="ret" && count==max) || (val!="ret" && val!="lend")) {form.elements["msg"].value = "";}
            else {form.elements["msg"].value = prompt("Set the comment:",msg);}
            fSubmit(form);
            return true;
        }
        else {count = prompt(question+" (between 1 and "+max+")","1");}
    }
    return false;
}