/**
 * A very basic implementaion of a JS module to hold all functions
 * used for setting up the Daily Sheet page.
 *
 * @module
 */
var DailySheet = function() {
    var myPublic = {};
    var launchTypes = {};
    var membersFields = [];
    var chargesFields = [];
    var today = {};
    var location;

    myPublic.init = function(launchTypeTowId, launchTypeSelfId, launchTypeWinchId, strTodayYear, strTodayMonth, strTodayDay, locat) {
        launchTypes.tow = launchTypeTowId;
        launchTypes.self = launchTypeSelfId;
        launchTypes.winch = launchTypeWinchId;
        today.year  = strTodayYear;
        today.month = strTodayMonth;
        today.day   = strTodayDay;
        location = locat;
    }

    myPublic.landbutton = function(what) {
        var stid = what.id;
        var iRow = what.id; // h rownumber
        iRow = iRow.substring(1, iRow.length);
        var n = document.getElementById("g" + iRow);
        if (n.getAttribute("timedata") != "0") {
            var parent = what.parentNode;
            parent.removeChild(what);

            var now = new Date();
            var para = getTimeTextBox(new Date(today.year, today.month, today.day, now.getHours(), now.getMinutes(), 0));
            para.id = stid;
            parent.appendChild(para);
            buildClockPicker(para);

            calcFlightTime(iRow);
            fieldchange(what);
        }
    }

    myPublic.startbutton = function(what) {
        var stid = what.id;
        var iRow = what.id; // h rownumber
        iRow = iRow.substring(1, iRow.length);
        var parent = what.parentNode;
        parent.removeChild(what);
        var para = document.createElement("input");
        para.setAttribute('class', 'textbox');
        para.setAttribute("onchange", "timechange(this)");

        var now = new Date();
        var d = new Date(today.year, today.month, today.day, now.getHours(), now.getMinutes(), 0);

        para.setAttribute("timedata", d.getTime());
        para.value = pad(d.getHours(), 2) + ":" + pad(d.getMinutes(), 2);
        para.setAttribute("prevval", para.value);
        para.id = stid;
        para.size = 1;
        parent.appendChild(para);

        buildClockPicker(para);

        //Get the value of P2
        var p2 = document.getElementById("f" + iRow).value;
        if (p2 == "0") {
            //now check if k = set to P2 change to PIC
            var ch1 = document.getElementById("k" + iRow).value;
            if (ch1 == "c1") {
                var ch = document.getElementById("k" + iRow).childNodes;
                for (mm = 0; mm < ch.length; mm++) {
                    ch[mm].selected = false;
                    if (ch[mm].value == "c2")
                        ch[mm].selected = true;
                }
            }
        }

        fieldchange(what);

        //Create a new row in the table
        var nextrow = parseInt(iRow) + 1;
        var check = document.getElementById("b" + nextrow);
        if (null == check) {
            var tp = "d" + iRow;
            var strtp = document.getElementById(tp).value;
            var vector = document.getElementById(`vector-${iRow}`).value;
            myPublic.addrowdata(nextRow, 'l' + launchTypes.winch, "", vector, strtp, "", "", "0", "0", "0", "", "", "", location, "0");
            nextRow++;
        }
    }

    myPublic.towlandbutton= function(what)
    {
      var stid = what.id;
      var iRow = what.id;   // n rownumber
      iRow = iRow.substring(1,iRow.length);
      var n = document.getElementById("g" + iRow);
      if (n.getAttribute("timedata") != "0")
      {
        var parent = what.parentNode;
        parent.removeChild(what);
        var para = getTimeTextBox(new Date());
        para.id = stid;
        parent.appendChild(para);
    
        calcFlightTime(iRow);
        fieldchange(what);
      }
    }

    myPublic.refreshMembers = function() {
        $('#loading-spinner').show()
        setTimeout(function(){
            try{
                MemberSelect.MemberSelectTemplate = null;
                $.each(membersFields, function(index, field) {
                    field.refresh()
                })
                ChargesSelect.ChargesSelectTemplate = null;
                $.each(chargesFields, function(index, field) {
                    field.refresh()
                })
            } finally {
                $('#loading-spinner').hide()
            }
        }, 0);
    }

    myPublic.addrowdata = function(id, plane, glider, vector, towy, p1, p2, start, towland, land, height, charges, comments, location, del) {
        console.log("Add row data plane = " + plane);
        var sel;
        var table = document.getElementById("t1");
        var row = table.insertRow(-1);

        row.insertCell(0).innerHTML = id;


        var r1 = row.insertCell(1);
        var entryTypeSelect = new DailySheetEntryType(towplanes, nextRow, plane, launchTypes)
        r1.appendChild(entryTypeSelect.domNode)

        var r2 = row.insertCell(2);
        r2.innerHTML = "<input type='text' name='glider[]' class='textbox' maxlength='3' size='1' onchange='fieldchange(this)' autocomplete='off'>";
        r2.firstChild.setAttribute("id", "c" + nextRow);
        r2.firstChild.setAttribute("value", glider);

        var vectorCell = row.insertCell(3);
        var id = `vector-${nextRow}`

        vectorCell.innerHTML =
            `<input type='text' id='${id}' class='textbox' maxlength='2' size='1' name='vector[]' onchange='fieldchange(this, ${nextRow})' value='${vector}' autocomplete='off'>`

        //New towpilot code

        var isWinch = (plane == 'l' + launchTypes.winch)
        var xml = isWinch ? winchdriverxml : towpilotxml
        var rootTag = isWinch ? 'wdrivers' : 'tpilots'

        var launchOperatorSelect = new LaunchOperator("towpilot", "d" + nextRow, xml, rootTag, towy, "new")
        addComboCell(row, 4, launchOperatorSelect, {classes: 'wide'});

        pic = new MemberSelect("pic", "e" + nextRow, p1, "new");
        addComboCell(row, 5, pic, {classes: 'wide'});
        membersFields.push(pic)

        p2  = new MemberSelect("p2",  "f" + nextRow, p2, "Trial");
        addComboCell(row, 6, p2,  {classes: 'wide'});
        membersFields.push(p2)

        var r6 = row.insertCell(7);
        if (parseInt(start) == 0) {
            r6.innerHTML = "<button name='start[]' class='ui-button ui-corner-all ui-widget' type='button' onclick='DailySheet.startbutton(this)'>Start</button>";
            r6.firstChild.setAttribute("id", "g" + nextRow);
            r6.firstChild.setAttribute("timedata", "0");
        } else {
            var para = getTimeTextBox(new Date(parseInt(start)));
            r6.appendChild(para);
            buildClockPicker(para);
            r6.firstChild.setAttribute("id", "g" + nextRow);
        }

        var nextCol = 8;
        //Tow charging based on time code follows
        if (towChargeType == 2) {
            var r13 = row.insertCell(nextCol);
            nextCol++;
            if (parseInt(towland) == 0) {
                r13.innerHTML = "<button name='towland[]' class='ui-button ui-corner-all ui-widget' type='button' onclick='DailySheet.towlandbutton(this)'>Tow Land</button>";
                r13.firstChild.setAttribute("id", "n" + nextRow);
                r13.firstChild.setAttribute("timedata", "0");
            } else {
                var para = getTimeTextBox(new Date(parseInt(towland)));
                r13.appendChild(para);
                r13.firstChild.setAttribute("id", "n" + nextRow);
            }
        }

        var r7 = row.insertCell(nextCol);
        nextCol++;
        if (parseInt(land) == 0) {
            r7.innerHTML = "<button name='land[]' class='ui-button ui-corner-all ui-widget' type='button' onclick='DailySheet.landbutton(this)'>Land</button>";
            r7.firstChild.setAttribute("id", "h" + nextRow);
            r7.firstChild.setAttribute("timedata", "0");
        } else {
            var para = getTimeTextBox(new Date(parseInt(land)));
            r7.appendChild(para);
            buildClockPicker(para);
            
            r7.firstChild.setAttribute("id", "h" + nextRow);
        }

        if (towChargeType == 1) {
            sel = "<select data-width='100%' onchange='fieldchange(this)' class='combo'></select>";
            var r8 = row.insertCell(nextCol);
            nextCol++;
            r8.innerHTML = sel;
            r8.firstChild.setAttribute("id", "i" + nextRow);
            var selnode = r8.firstChild;

            //Create an empty node

            var opt = document.createElement("option");
            opt.value = "0";
            opt.innerHTML = "--";
            selnode.appendChild(opt);

            for (h = 500; h < 6000; h += 500) {
                opt = document.createElement("option");
                opt.value = h.toString();
                opt.innerHTML = h.toString();
                if (h == parseInt(height))
                    opt.setAttribute("selected", "");
                selnode.appendChild(opt);
            }

            opt = document.createElement("option");
            opt.value = "-1";
            opt.innerHTML = "Check Flight";
            if (-1 == parseInt(height))
                opt.setAttribute("selected", "");
            selnode.appendChild(opt);

            opt = document.createElement("option");
            opt.value = "-2";
            opt.innerHTML = "Retrieve";
            if (-2 == parseInt(height))
                opt.setAttribute("selected", "");
            selnode.appendChild(opt);
        }

        //Time fields
        //If tow time option then we need tiem for tow
        if (towChargeType == 2) {
            r14 = row.insertCell(nextCol);
            nextCol++;
            r14.id = "o" + nextRow;

            if (parseInt(start) != 0 && parseInt(towland) != 0) {
                //We need to update the flight time.
                var dest = document.getElementById("o" + nextRow);
                var e = parseInt(towland) - parseInt(start);
                mins = Math.floor((e / 60000) % 60);
                var n = document.createTextNode(pad(Math.floor(e / (3600 * 1000)), 2) + ":" + pad(mins, 2));
                dest.appendChild(n);
            }


        }

        r9 = row.insertCell(nextCol);
        nextCol++;
        r9.id = "j" + nextRow;

        if (parseInt(start) != 0 && parseInt(land) != 0) {
            //We need to update the flight time.
            var dest = document.getElementById("j" + nextRow);
            var e = parseInt(land) - parseInt(start);
            mins = Math.floor((e / 60000) % 60);
            var n = document.createTextNode(pad(Math.floor(e / (3600 * 1000)), 2) + ":" + pad(mins, 2));
            dest.appendChild(n);
        }

        var chargesField = new ChargesSelect("charge", "k" + nextRow, charges)
        addComboCell(row, nextCol, chargesField, {classes: 'wide'});
        chargesFields.push(chargesField);
        nextCol++;

        r11 = row.insertCell(nextCol);
        nextCol++;
        r11.innerHTML = "<input type='text' class='ui-corner-all ui-widget ui-widget-content' style='padding: 4px;' name='comment[]' size='12' onchange='fieldchange(this)' autocomplete='off'>";
        r11.firstChild.setAttribute("value", unescape(comments));
        r11.firstChild.setAttribute("id", "l" + nextRow);

        r12 = row.insertCell(nextCol)
        nextCol++;
        r12.innerHTML = "<input type='text' class='ui-corner-all ui-widget ui-widget-content' style='padding: 4px;' name='location[]' size='10' onchange='fieldchange(this)' autocomplete='off'>";
        r12.firstChild.setAttribute("value", unescape(location));
        r12.firstChild.setAttribute("id", "n" + nextRow);

        r13 = row.insertCell(nextCol);
        nextCol++;

        const imgUrls = [
            {
                src: 'img/delete.png',
                alt: "delete"
            },
            {
                src: 'img/restore.ico',
                alt: "undelete"
            }
        ]
        var btn = document.createElement('input')
        btn.setAttribute("src", imgUrls[del].src);
        btn.setAttribute("alt", imgUrls[del].alt);
        btn.setAttribute("title", imgUrls[del].alt);
        btn.setAttribute("style", "width:2em");
        btn.type="image";
        btn.name = 'delete[]';
        btn.onclick = function() {
            toggleDelete(this, row, imgUrls)
        }
        r13.appendChild(btn)

        r13.firstChild.setAttribute("id", "m" + nextRow);
        r13.firstChild.setAttribute("value", del);

        // Configure update events between columns
        entryTypeSelect.onValueSelected = function(value) {
            console.log('Entry type ' + value)
            if (value == 'l' + launchTypes.winch) {
                launchOperatorSelect.setXml(winchdriverxml, 'wdrivers', launchOperatorSelect.value())
            } else if(value.startsWith('t')){
                launchOperatorSelect.setXml(towpilotxml, 'tpilots', launchOperatorSelect.value())
            } else {
                launchOperatorSelect.clear();
            }
        }

        $(row).find('.combo').selectpicker({
            width: '100%',
        })
        if (del != "0")
            renderDeletedRow(row);
    }

    // ===========================================
    // private section
    // ===========================================
    function getTimeTextBox(date)
    {
        var para = document.createElement("input");
        para.setAttribute('class', 'textbox');
        para.setAttribute("onchange", "timechange(this)");
        para.setAttribute("timedata", date.getTime());
        para.value = pad(date.getHours(), 2) + ":" + pad(date.getMinutes(), 2);
        para.setAttribute("prevval", para.value);
        para.setAttribute("maxlength", 5);
        para.size = 1;
        return para;
    }

    function toggleDelete(what, row, imgUrls)
    {
      var iRow = what.id;
      iRow = iRow.substring(1,iRow.length);
      what.value = (+what.value + 1) % 2;
      what.setAttribute("src", imgUrls[what.value].src)
      what.setAttribute("alt", imgUrls[what.value].alt)
      what.setAttribute("title", imgUrls[what.value].alt)
      if (what.value > 0)
      {
        renderDeletedRow(row);
      }
      else
      {
        renderUndeletedRow(row);
      }
      fieldchange(what);
    }

    function renderDeletedRow(row)
    {
        $(row).find('.bootstrap-select').addClass('deleted')
        $(row).find(':input').addClass('deleted')
        $(row).find('td').addClass('deleted')
    }

    function renderUndeletedRow(row)
    {
        $(row).find('.bootstrap-select').removeClass('deleted')
        $(row).find(':input').removeClass('deleted')
        $(row).find('td').removeClass('deleted')
    }

    function addComboCell(row, colnum, combo, options = {}) {
        var cell = row.insertCell(colnum);
        if(options.classes) {
            $(cell).addClass(options.classes);
        }
        combo.addTo(cell)
    }

    function buildClockPicker(txtBox){
        $(txtBox).flatpickr({
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i", // 24-hour format
            time_24hr: true, // Enables 24-hour format
            minuteIncrement: 1
        });
    }

    return myPublic
}()
