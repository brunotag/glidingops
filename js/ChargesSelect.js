function ChargesSelect (colname, collid, selvalue, options = {}) {
    var self = this;
    var sel = null;

    function buildSelect() {
        sel = document.createElement('select')
        sel.setAttribute('colname', colname)

        sel.onchange = function() {
            self.onValueSelected(sel.value)
            fieldchange(sel)
        }
        sel.id = collid;

        $(sel).addClass('combo-search')

        if (options.classes) {
            $(sel).addClass(options.classes)
        }

        buildOptions(selvalue)
    }

    function buildOptions(selvalue) {
        if(!ChargesSelect.ChargesSelectTemplate) {
            ChargesSelect.ChargesSelectTemplate = $(sel).clone()[0]

            const parser = new DOMParser();
            chargeDoc = parser.parseFromString(chargeopts, "text/xml");
            if (null != chargeDoc) {
                var mems = chargeDoc.getElementsByTagName("ChargeOpts")[0].childNodes;
                for (i = 0; i < mems.length; i++) {
                    if (mems[i].nodeName == "opt") {
                        var id = mems[i].getElementsByTagName("id")[0].childNodes[0].nodeValue;
                        var desc = mems[i].getElementsByTagName("desc")[0].childNodes[0].nodeValue;
                        opt = document.createElement("option");

                        opt.value = "c" + id;
                        opt.innerHTML = desc;
                        ChargesSelect.ChargesSelectTemplate.appendChild(opt);
                    }
                }
            }

            const parser2 = new DOMParser();
            membersDoc = parser2.parseFromString(allmembers, "text/xml");
            if (null != membersDoc) {
                var mems = membersDoc.getElementsByTagName("allmembers")[0].childNodes;
                for (i = 0; i < mems.length; i++) {

                    var id = mems[i].getElementsByTagName("id")[0].childNodes[0].nodeValue;
                    var name = mems[i].getElementsByTagName("name")[0].childNodes[0].nodeValue;
                    opt = document.createElement("option");

                    opt.value = "m" + id;
                    opt.innerHTML = name;
                    ChargesSelect.ChargesSelectTemplate.appendChild(opt);
                }
            }

            $(ChargesSelect.ChargesSelectTemplate).on('loaded.bs.select show.bs.select refreshed.bs.select', () => {
                $(ChargesSelect.ChargesSelectTemplate).siblings('.dropdown-toggle').remove(); // Or .hide();
            });   

            $(ChargesSelect.ChargesSelectTemplate).css("display", "none").appendTo("body")
            // Hide the dropdown menu when clicking outside of it
            $(document).on("click", function(e) {
                var $target = $(e.target);
                // Check if the clicked target is outside the dropdown menu
                if (!$(ChargesSelect.ChargesSelectTemplate).siblings(".dropdown-menu").is($target) && 
                    $(ChargesSelect.ChargesSelectTemplate).siblings(".dropdown-menu").has($target).length === 0) {
                $(ChargesSelect.ChargesSelectTemplate).siblings(".dropdown-menu").hide();  // Hide the dropdown menu
                }
            });
        }
        
        //set first value as default
        if (!selvalue){
            selvalue = $(ChargesSelect.ChargesSelectTemplate).find('option').first().val();
        }
        const selectedOption = $(ChargesSelect.ChargesSelectTemplate).find(`option[value="${selvalue}"]`)
        if (selectedOption.length > 0){
            sel.appendChild(new Option(selectedOption.text(), selectedOption.val()));
            $(sel).val(selvalue)
        }
        else{
            //TODO: throw catastrophic error? the sheet shouldn't be opened in edit mode to prevent data corruption.
        }
    }

    // ===========================================
    // Public interface
    // ===========================================
    function clear() {
        $(sel).html('')
    }

    self.onValueSelected = function(value) {
        // do nothing on select
    }

    self.value = function() {
        return sel.value
    }

    self.refresh = function() {
        var selectedValue = $(sel).val();
        clear();
        buildOptions(selectedValue);
    }


    self.addTo = function(targetDomNode) {
        targetDomNode.appendChild(sel)
        // Initialize the selectpicker
        $(sel).selectpicker({
            width: '100%',
        });

        // Prevent the dropdown from opening
        $(sel).on('show.bs.select', (e) => {
            $(sel).parent.find('.dropdown-menu.open').remove()
        });

        // Open the singleton selector on click
        $(sel).parent().find('button.dropdown-toggle').on('click', (e) => {
    
            $(ChargesSelect.ChargesSelectTemplate).selectpicker({
                header: "PLACEHOLDER",
                dropupAuto: false,
                dropdownAlignRight: false,
                size: 10,
                width: '100%',
                liveSearch: true,
                liveSearchStyle: 'startsWith',
            })

            const dropdownMenu = $(ChargesSelect.ChargesSelectTemplate).siblings('div.dropdown-menu');

            //show selected value
            if ($(sel).val())
            {
                setTimeout(() => {                    
                    dropdownMenu.find('li').removeClass('selected active');

                    const optionText = $(ChargesSelect.ChargesSelectTemplate).find('option[value="' + $(sel).val() + '"]').text();
                    const elementToHighlight = dropdownMenu.find('ul.dropdown-menu')
                        .find('li').filter(function() {
                            return $(this).find('a span.text').text().trim() === optionText;
                        });
                    elementToHighlight.addClass('selected active');

                    if (dropdownMenu.length && elementToHighlight.length) {
                        var offset = elementToHighlight.position().top + dropdownMenu.scrollTop();
                        dropdownMenu.scrollTop(offset); 
                    }

                    //focus the textbox
                    var inputElement = dropdownMenu.find('div input[type="text"]');    
                    if (inputElement.length) {
                        inputElement.focus();
                    }
                },25);
            }

            //Update the title of the singleton selector with the current field's name
            dropdownMenu.find('div.popover-title').contents().each(function() {
                if (this.nodeType === Node.TEXT_NODE) {
                    this.nodeValue = colname.toUpperCase();
                }
            });

            setTimeout(() => {      
                //clear the textbox
                var inputElement = dropdownMenu.find('div input[type="text"]');    
                if (inputElement.length) {
                    inputElement.val('');
                    inputElement.trigger('input');
                }
            },10);

            // Close the dropdown menu when the close button is clicked
            dropdownMenu.find(".close").one("click", function() {
                dropdownMenu.hide();  
            });            

            // When an item is clicked..
            dropdownMenu.one('click', 'a', function (e) {
                e.preventDefault(); 

                //highlight clicked item
                const listItem = $(this).closest('li');
                listItem.siblings().removeClass('active');
                listItem.addClass('active');
                
                //transfer the selection to the correct "select" (sel)
                const selectedText = $(this).text(); 
                const selectedValue = $(ChargesSelect.ChargesSelectTemplate).find(`option:contains("${selectedText}")`).val();                 
                if (!$(sel).find(`option[value="${selectedValue}"]`).length) {
                    $(sel).append(new Option(selectedText, selectedValue));
                }
                $(sel).val(selectedValue).change();
                
                dropdownMenu.hide()
            });

            //Open the singleton selector
            $(ChargesSelect.ChargesSelectTemplate).selectpicker("toggle")
            dropdownMenu.show()
        });
    }


    buildSelect()
}

ChargesSelect.ChargesSelectTemplate = null