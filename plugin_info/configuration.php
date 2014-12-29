<?php
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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
$deamonRunning = piface2::deamonRunning();
?>
<form class="form-horizontal">
    <fieldset>
         <?php
            if (!$deamonRunning) {
                echo '<div class="alert alert-danger">Le démon piface2 ne tourne pas</div>';
            } else {
                echo '<div class="alert alert-success">Le démon piface2 est en marche</div>';
            }
        ?>
 
         <div class="form-group">
            <label class="col-lg-2 control-label">Mode</label>
            <div class="col-lg-2">
                <select class="configKey form-control" data-l1key="Mode">
                    <option value="standalone">Standalone</option>
                    <option value="maitre">Maitre</option>
                    <option value="esclave">Esclave</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">Piface Port</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="PifacePort" />
            </div>
            <div class="col-lg-3">
                <div class="alert alert-info">Default = 8000</div>
            </div>
        </div>
         <div class="form-group">
            <label class="col-lg-2 control-label">Arrêt/Relance</label>
            <div class="col-lg-3">
                <a class="btn btn-warning" id="bt_stoppiface2Deamon"><i class='fa fa-stop'></i> Arrêter/Redemarrer le démon</a>
            </div>
        </div>
    </fieldset>
</form>

<script>
    $('#bt_stoppiface2Deamon').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/piface2/core/ajax/piface2.ajax.php", // url du fichier php
            data: {
                action: "stopRestartDeamon",
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: data.result, level: 'danger'});
                    return;
                }
                $('#div_alert').showAlert({message: 'Le démon a été correctement arrêté : il se relancera automatiquement dans 1 minute', level: 'success'});
                //$('#ul_plugin .li_plugin[data-plugin_id=rfxcom]').click();
            }
        });
    });
</script>
