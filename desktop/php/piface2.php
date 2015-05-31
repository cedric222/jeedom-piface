<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'piface2');
$eqLogics = eqLogic::byType('piface2');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un Piface}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend>{{Mes Pifaces}}
        </legend>
        <?php
        if (count($eqLogics) == 0) {
            echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez pas encore de templates, cliquez sur Ajouter un équipement pour commencer}}</span></center>";
        } else {
            ?>
            <div class="eqLogicThumbnailContainer">
                <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                    echo "<center>";
                    echo '<img src="plugins/piface2/doc/images/piface2_icon.png" height="105" width="95" />';
                    echo "</center>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                    echo '</div>';
                }
                ?>
            </div>
        <?php } ?>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <form class="form-horizontal">
            <fieldset>
                <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}  <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{Nom de l'équipement template}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement template}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label" >{{Objet parent}}</label>
                    <div class="col-sm-3">
                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (object::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{Catégorie}}</label>
                    <div class="col-sm-8">
                        <?php
				foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
			        echo '<label class="checkbox-inline">';
			        echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
			        echo '</label>';
				}
			?>
                    </div>
                </div>
		<div class="form-group">
                    <label class="col-sm-2 control-label" >{{Activer}}</label>
                    <div class="col-sm-1">
                        <input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" size="16" checked/>
                    </div>
                    <label class="col-sm-2 control-label" >{{Visible}}</label>
                    <div class="col-sm-1">
                        <input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{ip server web}}</label>
                    <div class="col-sm-1">
                        <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="ippiface" placeholder="IP du server piface"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">{{port server web}}</label>
                    <div class="col-sm-1">
                        <input type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="portpiface" placeholder="port du server piface"/>
                    </div>
                </div>
            </fieldset> 
        </form>


    <legend>Commandes</legend>
        <a class="btn btn-success btn-sm cmdAction expertModeVisible" id="Bt_AddSortie"><i class="fa fa-plus-circle"></i> {{Ajouter une sortie}}</a>
	<a class="btn btn-success btn-sm cmdAction expertModeVisible" id="Bt_AddEntree"><i class="fa fa-plus-circle"></i> {{Ajouter une entree}}</a>
	<a class="btn btn-success btn-sm cmdAction expertModeVisible" id="Bt_AddImpulsion"><i class="fa fa-plus-circle"></i> {{Ajouter une entree d'impulsion}}</a><br/><br/>

             <table id="table_cmd" class="table table-bordered table-condensed">
                 <thead>
                     <tr>
                         <th style="width: 300px;">{{Nom}}</th>
                         <th style="width: 130px;" class="expertModeVisible">{{Type}}</th>
                         <th style="width: 100px;" class="expertModeVisible">{{ID}}</th>
                         <th style="width: 200px;" class="expertModeVisible">{{Interface}}</th>
                         <th style="width: 200px;" class="expertModeVisible">{{Valeur}}</th>
                         <th >{{Paramètres}}</th>
                         <th style="width: 100px;">{{Options}}</th>
                         <th></th>
                     </tr>
                 </thead>
                 <tbody>
    
                 </tbody>
             </table>
    
             <form class="form-horizontal">
                 <fieldset>
                     <div class="form-actions">
                         <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                         <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                     </div>
                 </fieldset>
             </form>
    
         </div>
     </div>

    </div>
</div>

<?php include_file('desktop', 'piface2', 'js', 'piface2'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

