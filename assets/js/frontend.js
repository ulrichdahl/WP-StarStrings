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
        var sep = prompt("Indtast adskillelsestegn:", "");
        if (sep !== null && sep !== "") {
            appendSeparator(sep);
        }
    });

    function appendSeparator(sep) {
        var label = sep === " " ? "Mellemrum" : sep;
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
        receive: function(event, ui) {
            updateVehicleItem(ui.item);
        }
    }).disableSelection();

    function updateVehicleItem(item) {
        var isSelected = item.closest("#sc-loc-selected-vehicles").length > 0;
        if (isSelected) {
            if (item.find(".edit-vehicle-name").length === 0) {
                var currentName = item.find(".vehicle-name").text();
                item.append('<button class="edit-vehicle-btn" title="Rediger navn">✎</button>');
                item.append('<input type="text" class="edit-vehicle-name" value="' + currentName + '" style="display:none">');
            }
        } else {
            item.find(".edit-vehicle-btn, .edit-vehicle-name").remove();
            item.find(".vehicle-name").show();
        }
    }

    // Håndter klik på rediger knap
    $(document).on('click', '.edit-vehicle-btn', function(e) {
        e.stopPropagation();
        var li = $(this).closest('li');
        li.find(".vehicle-name").hide();
        $(this).hide();
        li.find(".edit-vehicle-name").show().focus();
    });

    // Gem navn ved enter eller blur
    $(document).on('keyup', '.edit-vehicle-name', function(e) {
        if (e.which === 13) {
            $(this).blur();
        }
    });

    $(document).on('blur', '.edit-vehicle-name', function() {
        var li = $(this).closest('li');
        var newName = $(this).val();
        li.find(".vehicle-name").text(newName).show();
        li.find(".edit-vehicle-btn").show();
        $(this).hide();
    });

    // Nulstil vehicle sortering
    $("#sc-loc-vehicle-clear").on('click', function() {
        if (confirm("Er du sikker på, at du vil nulstille sorteringen? Alle valgte fartøjer flyttes tilbage.")) {
            $("#sc-loc-selected-vehicles li").each(function() {
                var li = $(this);
                li.find(".edit-vehicle-btn, .edit-vehicle-name").remove();
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
                name: $(this).find(".vehicle-name").text()
            });
        });

        if (!format) {
            alert("Vælg venligst et format til komponenter.");
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
                alert("Opsætningen er indlæst!");

                // Her skal du kalde din egen funktion, der opdaterer siden
                // f.eks. opdaterBrugerflade();
                $("#sc-loc-format-input").val(JSON.stringify(minOpsaetning.components));
                const formatContainer = $("#sc-loc-active-format");
                minOpsaetning.components.forEach(emne => {
                    if (emne.type === 'variable') {
                        let item = $("#sc-loc-format-builder div[data-type="+emne.value+"]").remove();
                        formatContainer.append(item);
                    }
                    else if (emne.type === 'text') {
                        appendSeparator(emne.value);
                    }
                });
                scLocSortableFormat.sortable("refresh");
                const listeContainer = $("#sc-loc-selected-vehicles");
                minOpsaetning.vehicles.forEach(emne => {
                    let li = document.createElement('li');
                    // Sæt data-attributter (svarende til dit PHP-output)
                    li.setAttribute('data-key', emne.key);
                    li.setAttribute('data-name', emne.name);
                    // Indsæt indholdet (span med navnet)
                    li.innerHTML = `<span class="vehicle-name">${emne.name}</span><button class="edit-vehicle-btn" title="Rediger navn">✎</button>
<input type="text" class="edit-vehicle-name" value="${emne.name}" style="display:none">`;
                    // Tilføj til listen
                    listeContainer.append(li);
                });
                scLocSortableList.sortable("refresh");
                updateFormatInput();
            } catch (fejl) {
                console.error(fejl);
                alert("Fejl: Filen er ikke en gyldig JSON-fil.");
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
                name: $(this).find(".vehicle-name").text()
            });
        });

        if (!format) {
            alert("Vælg venligst et format til komponenter.");
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
