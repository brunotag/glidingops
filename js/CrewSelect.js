function MemberSelect (colname, collid, selvalue, newvalLabel, options = {}) {
    var self = this;
    var listtag = "allmembers"
    var sel = null;
    var colname = colname;

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
        var opt = document.createElement("option");
        opt.value = "0";
        opt.text = "--";
        sel.appendChild(opt);

        opt = document.createElement("option");
        opt.value = "new";
        opt.text = newvalLabel;
        sel.appendChild(opt);

        //the first time, we create the "template" TODO: rename it to "singleton"
        if(!MemberSelect.MemberSelectTemplate) {
            MemberSelect.MemberSelectTemplate = $(sel).clone()[0]

            parser = new DOMParser();
            dropDoc = parser.parseFromString(allmembers, "text/xml");
            if (null != dropDoc) {
                var mems = dropDoc.getElementsByTagName(listtag)[0].childNodes;
                for (i = 0; i < mems.length; i++) {
                    var id = mems[i].getElementsByTagName("id")[0].childNodes[0].nodeValue;
                    var name = mems[i].getElementsByTagName("name")[0].childNodes[0].nodeValue;

                    opt = document.createElement("option");
                    opt.value = id;
                    opt.innerHTML = name;
                    MemberSelect.MemberSelectTemplate.appendChild(opt);                 
                }
            }

            $(MemberSelect.MemberSelectTemplate).on('loaded.bs.select show.bs.select refreshed.bs.select', () => {
                $(MemberSelect.MemberSelectTemplate).siblings('.dropdown-toggle').remove(); // Or .hide();
            });   

            $(MemberSelect.MemberSelectTemplate).css("display", "none").appendTo("body")
            // Hide the dropdown menu when clicking outside of it
            $(document).on("click", function(e) {
                var $target = $(e.target);
                // Check if the clicked target is outside the dropdown menu
                if (!$(MemberSelect.MemberSelectTemplate).siblings(".dropdown-menu").is($target) && 
                    $(MemberSelect.MemberSelectTemplate).siblings(".dropdown-menu").has($target).length === 0) {
                $(MemberSelect.MemberSelectTemplate).siblings(".dropdown-menu").hide();  // Hide the dropdown menu
                }
            });
        }
        if (selvalue){
            const selectedOption = $(MemberSelect.MemberSelectTemplate).find(`option[value="${selvalue}"]`)
            if (selectedOption.length > 0){
                sel.appendChild(new Option(selectedOption.text(), selectedOption.val()));
                $(sel).val(selvalue)
            }
            else{
                //TODO: throw catastrophic error? the sheet shouldn't be opened in edit mode to prevent data corruption.
            }
        }
    }

    self.onValueSelected = function(value) {
        // do nothing on select
    }

    function clear() {
        $(sel).html('')
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
            
            $(MemberSelect.MemberSelectTemplate).selectpicker({
                header: "PLACEHOLDER",
                dropupAuto: false,
                dropdownAlignRight: false,
                size: 10,
                width: '100%',
                liveSearch: true,
                liveSearchStyle: 'startsWith',
            })

            const dropdownMenu = $(MemberSelect.MemberSelectTemplate).siblings('div.dropdown-menu');

            //show selected value
            if ($(sel).val())
            {
                setTimeout(() => {                    
                    dropdownMenu.find('li').removeClass('selected active');

                    const optionText = $(MemberSelect.MemberSelectTemplate).find('option[value="' + $(sel).val() + '"]').text();
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
                //Clear the text box
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
                const selectedValue = $(MemberSelect.MemberSelectTemplate).find(`option:contains("${selectedText}")`).val();                 
                if (!$(sel).find(`option[value="${selectedValue}"]`).length) {
                    $(sel).append(new Option(selectedText, selectedValue));
                }
                $(sel).val(selectedValue).change();
                
                dropdownMenu.hide()
            });

            //Open the singleton selector
            $(MemberSelect.MemberSelectTemplate).selectpicker("toggle")
            dropdownMenu.show()
        });
    }

    buildSelect()
}

MemberSelect.MemberSelectTemplate = null;