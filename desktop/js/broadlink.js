
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

$('.changeIncludeState').on('click', function() {
    var state = $(this).attr('data-state')
    changeIncludeState(state)
})

$('#bt_autoDetectModule').on('click', function() {
    bootbox.confirm('{{Etes-vous sûr de vouloir recréer toutes les commandes ? Cela va supprimer les commandes existantes.}}', function(result) {
        if (result) {
            $.ajax({
                type: "POST",
                url: "plugins/broadlink/core/ajax/broadlink.ajax.php",
                data: {
                    action: "autoDetectModule",
                    id: $('.eqLogicAttr[data-l1key=id]').value(),
                },
                dataType: 'json',
                global: false,
                error: function(error) {
                    $.fn.showAlert({ message: error.message, level: 'danger' })
                },
                success: function(data) {
                    if (data.state != 'ok') {
                        $.fn.showAlert({ message: data.result, level: 'danger' })
                        return
                    }
                    $.fn.showAlert({ message: '{{Opération réalisée avec succès}}', level: 'success' })
                }
            })
        }
    })
})

$('#bt_healthbroadlink').on('click', function() {
    $('#md_modal').dialog({ title: "{{Santé Broadlink}}" })
    $('#md_modal').load('index.php?v=d&plugin=broadlink&modal=health').dialog('open')
})

$('#btn_sync').on('click', function() {
    var logicalId = $('.eqLogicAttr[data-l1key=logicalId]').value()
    $('#md_modal').dialog({ title: "{{Synchronisation Broadlink}}" })
    $('#md_modal').load('index.php?v=d&plugin=broadlink&modal=synchro&id=' + logicalId).dialog('open')
})

$('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').on('change', function() {
    if ($('.eqLogicDisplayCard.active').attr('data-eqlogic_id') != '') {
        getModelListParam($(this).value(), $('.eqLogicDisplayCard.active').attr('data-eqlogic_id'))
    } else {
        $('#img_device').attr("src", 'plugins/broadlink/plugin_info/broadlink_icon.png')
    }
})

$('.eqLogicAttr[data-l1key=configuration][data-l2key=iconModel]').on('change', function() {
    if ($(this).value() != '' && $(this).value() != null) {
        $('#img_device').attr("src", 'plugins/broadlink/core/config/devices/' + $(this).value() + '.png')
    }
})

$('body').on('change', '.cmd .cmdAttr[data-l1key=type]', function() {
    if ($(this).value() == 'action') {
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=id]').show()
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=group]').show()
    } else {
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=id]').hide()
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=configuration][data-l2key=group]').hide()
    }
})
function getModelListParam(_conf, _id) {
    $.ajax({
        type: "POST",
        url: "plugins/broadlink/core/ajax/broadlink.ajax.php",
        data: {
            action: "getModelListParam",
            conf: _conf,
            id: _id,
        },
        dataType: 'json',
        global: false,
        error: function(error) {
            $.fn.showAlert({ message: error.message, level: 'danger' })
        },
        success: function(data) {
            if (data.state != 'ok') {
                $.fn.showAlert({ message: data.result, level: 'danger' })
                return
            }
            var options = ''
            for (var i in data.result[0]) {
                if (data.result[0][i]['selected'] == 1) {
                    options += '<option value="' + i + '" selected>' + data.result[0][i]['value'] + '</option>'
                } else {
                    options += '<option value="' + i + '">' + data.result[0][i]['value'] + '</option>'
                }
            }
            if (data.result[1] == true) {
                $(".learnCommand").show()
            } else {
                $(".learnCommand").hide()
            }
            $(".modelList").show()
            $(".listModel").html(options)
            $icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=iconModel]').value()
            if ($icon != '' && $icon != null) {
                $('#img_device').attr("src", 'plugins/broadlink/core/config/devices/' + $icon + '.png')
            } else {
                $('#img_device').attr("src", 'plugins/broadlink/plugin_info/broadlink_icon.png')
            }
        }
    })
}

$("#table_cmd").sortable({
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true
})

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = { configuration: {} }
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {}
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
    tr += '<td class="hidden-xs">'
    tr += '<span class="cmdAttr" data-l1key="id"></span>'
    tr += '</td>'
    tr += '<td>'
    tr += '<div class="input-group">'
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
    tr += '</div>'
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
    tr += '<option value="">{{Aucune}}</option>'
    tr += '</select>'
    tr += '</td>'
    tr += '<td>'
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
    tr += '</td>'
    tr += '<td><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="logicalid" value="0" placeholder="{{Commande}}"><br/>'
    tr += '</td>'
    tr += '<td>'
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateValue" placeholder="{{Valeur retour d\'état}}" style="margin-bottom:5px;">'
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateTime" placeholder="{{Durée avant retour d\'état (min)}}">'
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdId" style="display:none;margin-bottom:5px;" title="Commande information à mettre à jour">'
    tr += '<option value="">Aucune</option>'
    tr += '</select>'
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdToValue" placeholder="Valeur de l\'information" style="display:none;">'
    tr += '</td>'
    tr += '<td>'
    tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'
    tr += '</td>'
    tr += '<td>'
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
    tr += '<div style="margin-top:7px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '</div>'
    tr += '</td>'
    tr += '<td>'
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
    }
    tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>'
    tr += '</tr>'
    $('#table_cmd tbody').append(tr)
    var tr = $('#table_cmd tbody tr:last')
    jeedom.eqLogic.buildSelectCmd({
        id: $('.eqLogicAttr[data-l1key=id]').value(),
        filter: { type: 'info' },
        error: function(error) {
            $.fn.showAlert({ message: error.message, level: 'danger' })
        },
        success: function(result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result)
            tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result)
            tr.setValues(_cmd, '.cmdAttr')
            jeedom.cmd.changeType(tr, init(_cmd.subType))
        }
    })
}

$('body').on('broadlink::includeState', function(_event, _options) {
    if (_options['state'] == 1) {
        if ($('.include').attr('data-state') != 0) {
            $.hideAlert()
            $('.include').attr('data-state', 0)
            $('.include.card span').text("{{Arrêter l'inclusion}}")
            $('#div_inclusionAlert').showAlert({ message: "{{Vous êtes en mode inclusion. Patientez 5 secondes ou cliquez à nouveau sur le bouton d'inclusion pour quitter ce mode.}}", level: 'warning' })
        }
    } else {
        if ($('.include').attr('data-state') != 1) {
            $.hideAlert()
            $('.include').attr('data-state', 1)
            $('.include.card span').text('{{Mode inclusion}}')
        }
    }
})

$('body').on('broadlink::includeDevice', function(_event, _options) {
    if (modifyWithoutSave) {
        $('#div_inclusionAlert').showAlert({ message: "{{Un périphérique vient d'être inclus ou exclus. Veuillez réactualiser la page}}", level: 'warning' })
    } else {
        if (_options == '') {
            window.location.reload()
        } else {
            window.location.href = 'index.php?v=d&p=broadlink&m=broadlink&id=' + _options
        }
    }
})

$('body').on('broadlink::includeCommand', function(_event, _options) {
    $('#div_inclusionAlert').showAlert({ message: "{{Une nouvelle commande vient d'être ajoutée, pensez à la nommer.}}", level: 'success' })
    window.location.href = 'index.php?v=d&p=broadlink&m=broadlink&id=' + _options + '&nocache=' + (new Date()).getTime() + '#commandtab'
})

$('body').on('broadlink::missedCommand', function(_event, _options) {
    $('#div_inclusionAlert').showAlert({ message: '{{Aucune commande reçue dans le temps imparti}}', level: 'danger' })
})

$('body').on('broadlink::foundfrequency', function(_event, _options) {
    if (_options['state'] == 1) {
        $('#div_inclusionAlert').showAlert({ message: '{{Radio-fréquence trouvée, vous pouvez lâchez le bouton et vous préparer à appuyer dans 3 secondes}}', level: 'warning' })
    } else {
        $('#div_inclusionAlert').showAlert({ message: '{{Aucune radio-fréquence trouvée}}', level: 'danger' })
    }
})

$('body').on('broadlink::step2', function(_event, _options) {
    $('#div_inclusionAlert').showAlert({ message: '{{Etape 2, appuyez sur le bouton}}', level: 'warning' })
})


function changeIncludeState(_state) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/broadlink/core/ajax/broadlink.ajax.php", // url du fichier php
        data: {
            action: "changeIncludeState",
            state: _state,
        },
        dataType: 'json',
        error: function(error) {
            $.fn.showAlert({ message: error.message, level: 'danger' })
        },
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $.fn.showAlert({ message: data.result, level: 'danger' })
                return
            }
        }
    })
}

$('.learnCommand').on('click', function() {
    $('#div_inclusionAlert').showAlert({ message: '{{Veuillez appuyer sur le bouton de votre télécommande}}', level: 'warning' })
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/broadlink/core/ajax/broadlink.ajax.php", // url du fichier php
        data: {
            action: "learn",
            id: $('.eqLogicAttr[data-l1key=id]').value(),
            mode: 'normal',
        },
        dataType: 'json',
        error: function(error) {
            $.fn.showAlert({ message: error.message, level: 'danger' })
        },
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $.fn.showAlert({ message: data.result, level: 'danger' })
                return
            }
        }
    })
})

$('.learnCommandRF').on('click', function() {
    $('#div_inclusionAlert').showAlert({ message: '{{Veuillez maintenir appuyé le bouton de votre télécommande (ou appuyer successivement dessus) pour trouver la radio-fréquence}}', level: 'warning' })
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/broadlink/core/ajax/broadlink.ajax.php", // url du fichier php
        data: {
            action: "learn",
            id: $('.eqLogicAttr[data-l1key=id]').value(),
            mode: 'rf',
        },
        dataType: 'json',
        error: function(error) {
            $.fn.showAlert({ message: error.message, level: 'danger' })
        },
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $.fn.showAlert({ message: data.result, level: 'danger' })
                return
            }
        }
    })
})
