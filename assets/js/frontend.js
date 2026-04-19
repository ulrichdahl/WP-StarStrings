let scLocSortableFormat = null;
let scLocSortableList = null;
jQuery(document).ready(function($) {
    // Gør chips i kilden drabbare (klon dem når de trækkes)
    $("#sc-loc-format-builder .chip").draggable({
        connectToSortable: "#sc-loc-active-format",
        helper: "clone",
        revert: "invalid"
    });

    scLocSortableFormat = $("#sc-loc-active-format").sortable({
        placeholder: "ui-state-highlight",
        receive: function(event, ui) {
            // Sørg for at den modtagne chip har en fjern-mulighed (vi bruger dblclick som tippet i UI)
            updateFormatInput();
        },
        update: function(event, ui) {
            updateFormatInput();
        }
    });

    // Fjern chip ved dobbeltklik
    $("#sc-loc-active-format").on('dblclick', '.chip', function() {
        $(this).remove();
        updateFormatInput();
    });
    $("#sc-loc-clear-format").on('click', function() {
        $("#sc-loc-active-format").find(".chip").remove();
        updateFormatInput();
    });

    // Knapper til separatorer
    $(".add-sep").on('click', function() {
        var sep = $(this).data('sep');
        appendSeparator(sep);
    });

    $("#sc-loc-custom-sep").on('click', function() {
        var sep = prompt(scLocData.i18n.enterSeparator, "");
        if (sep !== null && sep !== "") {
            appendSeparator(sep);
        }
    });

    function appendSeparator(sep) {
        var label = sep === " " ? scLocData.i18n.space : sep;
        $("#sc-loc-active-format").append('<div class="chip separator" data-type="text">' + label + '<span style="display:none">' + sep + '</span></div>');
        updateFormatInput();
    }

    function updateFormatInput() {
        var format = [];
        $("#sc-loc-active-format .chip").each(function() {
            var type = $(this).data('type');
            if (type === 'text') {
                var val = $(this).find('span').length ? $(this).find('span').text() : $(this).text();
                format.push({ type: 'text', value: val });
            } else {
                format.push({ type: 'variable', value: type });
            }
        });
        $("#sc-loc-format-input").val(JSON.stringify(format));
        let example = "";
        for (let i=0; i < format.length; i++) {
            let elm = format[i];
            if (elm.type === 'text') example += elm.value;
            else switch (elm.value) {
                case 'type_long':
                    example += "COOL";
                    break;
                case 'type_short':
                    example += "C";
                    break;
                case 'class_long':
                    example += "MIL";
                    break;
                case 'class_short':
                    example += "M";
                    break;
                case 'size':
                    example += "2";
                    break;
                case 'grade':
                    example += "B";
                    break;
                case 'name':
                    example += "Tundra";
                    break;
            }
        }
        $("#sc-loc-format-example").text(example);
    }

    // Vehicle sortering
    scLocSortableList = $("#sc-loc-available-vehicles, #sc-loc-selected-vehicles").sortable({
        connectWith: ".sc-loc-list",
        placeholder: "ui-state-highlight",
        receive: function(event, ui) {
            updateVehicleItem(ui.item);
            updateAllVehiclePrefixes();
        },
        update: function (event, ui) {
            if (this.id === "sc-loc-selected-vehicles") {
                updateAllVehiclePrefixes();
            }
        }
    }).disableSelection();

    function updateAllVehiclePrefixes() {
        var count = 0;
        var selectedLis = $("#sc-loc-selected-vehicles li");
        var totalMainVehicles = selectedLis.filter(function () {
            return !$(this).hasClass('is-nested');
        }).length;
        var usePadding = totalMainVehicles > 9;

        var prePrefix = 0;
        selectedLis.each(function () {
            var li = $(this);
            if (!li.hasClass('is-nested')) {
                count++;
            }
            var prefix = count;
            if (usePadding && count < 10) {
                prefix = "0" + count;
            }
            if (prePrefix === 0 && li.next()?.hasClass('is-nested')) {
                prePrefix = 1;
            } else if (li.hasClass('is-nested')) {
                prePrefix += 1;
            }
            li.find(".vehicle-prefix-label").text(prefix + (prePrefix ? String.fromCharCode(96 + prePrefix) : '') + ". ");
            if (prePrefix > 0 && !li.next()?.hasClass('is-nested')) {
                prePrefix = 0;
            }
        });
    }

    function updateVehicleItem(item) {
        var isSelected = item.closest("#sc-loc-selected-vehicles").length > 0;
        if (isSelected) {
            if (item.find(".vehicle-controls").length === 0) {
                var currentName = item.find(".vehicle-name").text();
                var currentIndex = item.index() + 1;
                item.prepend('<span class="vehicle-prefix-label">' + currentIndex + '. </span>');

                var controls = $('<div class="vehicle-controls"></div>');
                controls.append('<button class="nest-vehicle-btn unindent-vehicle" title="' + scLocData.i18n.unindent + '">↙</button>');
                controls.append('<button class="nest-vehicle-btn indent-vehicle" title="' + scLocData.i18n.indent + '">↗</button>');
                controls.append('<button class="edit-vehicle-btn" title="' + scLocData.i18n.editName + '">✎</button>');
                item.append(controls);

                item.append('<div class="edit-vehicle-fields" style="display:none; flex-grow: 1; gap: 5px;">' +
                    '<input type="text" class="edit-vehicle-name" value="' + currentName + '" style="flex-grow: 1;">' +
                    '</div>');
            }
        } else {
            item.removeClass('is-nested');
            item.find(".vehicle-prefix-label, .vehicle-controls, .edit-vehicle-fields").remove();
            item.find(".vehicle-name").show();
        }
    }

    // Håndter indrykning (nesting)
    $(document).on('click', '.nest-vehicle-btn', function (e) {
        e.stopPropagation();
        var li = $(this).closest('li');
        // Kan kun indrykke hvis det ikke er det første element
        if (li.index() > 0 || li.hasClass('is-nested')) {
            li.toggleClass('is-nested');
            updateAllVehiclePrefixes();
        }
    });

    // Håndter klik på rediger knap
    $(document).on('click', '.edit-vehicle-btn', function(e) {
        e.stopPropagation();
        var li = $(this).closest('li');
        li.find(".vehicle-name, .vehicle-prefix-label, .vehicle-controls").hide();
        li.find(".edit-vehicle-fields").css('display', 'flex');
        li.find(".edit-vehicle-name").focus();
    });

    // Gem navn ved enter eller blur
    $(document).on('keyup', '.edit-vehicle-name', function(e) {
        if (e.which === 13) {
            saveVehicleEdit($(this).closest('li'));
        }
    });

    $(document).on('blur', '.edit-vehicle-name', function() {
        saveVehicleEdit($(this).closest('li'));
    });

    function saveVehicleEdit(li) {
        var newName = li.find(".edit-vehicle-name").val();
        li.find(".vehicle-name").text(newName).show();
        li.find(".vehicle-prefix-label, .vehicle-controls").show();
        li.find(".edit-vehicle-fields").hide();
    }

    // Nulstil vehicle sortering
    $("#sc-loc-vehicle-clear").on('click', function() {
        if (confirm(scLocData.i18n.confirmReset)) {
            $("#sc-loc-selected-vehicles li").each(function() {
                var li = $(this);
                li.removeClass('is-nested');
                li.find(".vehicle-prefix-label, .vehicle-controls, .edit-vehicle-fields").remove();
                li.find(".vehicle-name").text(li.data('name')).show();
                $("#sc-loc-available-vehicles").append(li);
            });
            // Sorter tilgængelige alfabetisk igen (valgfrit men pænt)
            var listItems = $("#sc-loc-available-vehicles li").get();
            listItems.sort(function(a, b) {
                return $(a).find(".vehicle-name").text().toUpperCase().localeCompare($(b).find(".vehicle-name").text().toUpperCase());
            });
            $.each(listItems, function(i, itm) {
                $("#sc-loc-available-vehicles").append(itm);
            });
        }
    });

    // Søgefunktion til vehicles
    $("#sc-loc-vehicle-search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#sc-loc-available-vehicles li").filter(function() {
            $(this).toggle($(this).find(".vehicle-name").text()?.toLowerCase().indexOf(value) > -1)
        });
    });

    // Hent opsætning
    $("#sc-loc-save-btn").on('click', function() {
        var format = $("#sc-loc-format-input").val();
        var selectedVehicles = [];
        $("#sc-loc-selected-vehicles li").each(function() {
            selectedVehicles.push({
                key: $(this).data('key'),
                is_nested: $(this).hasClass('is-nested'),
                name: $(this).find(".vehicle-name").text()
            });
        });

        if (!format) {
            alert(scLocData.i18n.selectFormat);
            return;
        }

        const jsonData = JSON.stringify({components: JSON.parse(format), vehicles: selectedVehicles}, null, 2); // Lav objekt til tekst
        const blob = new Blob([jsonData], { type: "application/json" });
        const url = URL.createObjectURL(blob);

        // Lav et midlertidigt link og "klik" på det programmatisk
        const a = document.createElement("a");
        a.href = url;
        a.download = "UnitedDanes-SC-lokalisering.json";
        a.click();

        // Ryd op i hukommelsen
        URL.revokeObjectURL(url);
    });

    // --- FUNKTION: INDLÆS FIL ---
    $("#sc-loc-load-btn").on('change', function(event) {
        const fil = event.target.files[0];
        if (!fil) return;

        const læser = new FileReader();
        læser.onload = function(e) {
            try {
                const indhold = e.target.result;
                let minOpsaetning = JSON.parse(indhold); // Lav teksten tilbage til et objekt

                console.log("Opsætning genetableret:", minOpsaetning);
                alert(scLocData.i18n.configLoaded);

                // Ryd eksisterende format og valgte biler før indlæsning
                $("#sc-loc-clear-format").trigger('click');
                $("#sc-loc-selected-vehicles li").each(function () {
                    var li = $(this);
                    li.removeClass('is-nested');
                    li.find(".vehicle-prefix-label, .vehicle-controls, .edit-vehicle-fields").remove();
                    li.find(".vehicle-name").text(li.data('name')).show();
                    $("#sc-loc-available-vehicles").append(li);
                });

                $("#sc-loc-format-input").val(JSON.stringify(minOpsaetning.components));
                const formatContainer = $("#sc-loc-active-format");
                minOpsaetning.components.forEach(emne => {
                    if (emne.type === 'variable') {
                        let item = $("#sc-loc-format-builder div[data-type=" + emne.value + "]").detach();
                        formatContainer.append(item);
                    }
                    else if (emne.type === 'text') {
                        appendSeparator(emne.value);
                    }
                });
                scLocSortableFormat.sortable("refresh");

                const listeContainer = $("#sc-loc-selected-vehicles");
                minOpsaetning.vehicles.forEach(emne => {
                    // Prøv at finde det eksisterende element i "Tilgængelige"
                    let li = $("#sc-loc-available-vehicles li[data-key='" + emne.key + "']");

                    if (li.length > 0) {
                        // Flyt det eksisterende element
                        li.detach().appendTo(listeContainer);
                    } else {
                        // Hvis det ikke findes (måske fjernet fra INI), opret et nyt
                        li = $('<li data-key="' + emne.key + '" data-name="' + emne.name + '"><span class="vehicle-name notranslate" translate="no">' + emne.name + '</span></li>');
                        li.appendTo(listeContainer);
                    }

                    // Opdater elementet med gemt status
                    updateVehicleItem(li);
                    li.find(".vehicle-name").text(emne.name);
                    li.find(".edit-vehicle-name").val(emne.name);

                    if (emne.is_nested) {
                        li.addClass('is-nested');
                    }
                });

                // Sorter tilgængelige alfabetisk igen
                var listItems = $("#sc-loc-available-vehicles li").get();
                listItems.sort(function (a, b) {
                    return $(a).find(".vehicle-name").text().toUpperCase().localeCompare($(b).find(".vehicle-name").text().toUpperCase());
                });
                $.each(listItems, function (i, itm) {
                    $("#sc-loc-available-vehicles").append(itm);
                });

                updateAllVehiclePrefixes();
                scLocSortableList.sortable("refresh");
                updateFormatInput();
            } catch (fejl) {
                console.error(fejl);
                alert(scLocData.i18n.invalidJson);
            }
        };
        læser.readAsText(fil);
    });

    // Download logik
    $("#sc-loc-download-btn").on('click', function() {
        var format = $("#sc-loc-format-input").val();
        var selectedVehicles = [];
        $("#sc-loc-selected-vehicles li").each(function() {
            selectedVehicles.push({
                key: $(this).data('key'),
                is_nested: $(this).hasClass('is-nested'),
                name: $(this).find(".vehicle-name").text()
            });
        });

        if (!format) {
            alert(scLocData.i18n.selectFormat);
            return;
        }

        // Vi bruger en form post til at håndtere fil download
        var form = $('<form method="POST" action="' + scLocData.ajaxurl + '">');
        form.append('<input type="hidden" name="action" value="sc_loc_download">');
        form.append('<input type="hidden" name="nonce" value="' + scLocData.nonce + '">');
        form.append('<input type="hidden" name="format" value=\'' + format + '\'>');
        form.append('<input type="hidden" name="vehicles" value=\'' + JSON.stringify(selectedVehicles) + '\'>');
        $('body').append(form);
        form.submit();
        form.remove();
    });
});
