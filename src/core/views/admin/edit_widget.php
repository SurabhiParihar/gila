
<form id="widget_options_form" class="g-form">
<input type="hidden" value="<?=$widget->id?>" id='widget_id' name='widget_id'>
<div class="gm-12" style="display:inline-flex;margin-bottom:8px">
<div class="gm-6">
    <label class="gm-4">Widget Area</label>
    <select  id="widget_area" name="widget_area" value="<?=gila::config('default-controller')?>" class="gm-6 g-input">
        <?php
        foreach (gila::$widget_area as $value) {
            $sel = ($widget->area==$value?'selected':'');
            echo '<option value="'.$value."\" $sel>".ucwords($value).'</option>';
        }
        ?>
    </select>
</div>

<div class="gm-6">
    <label class="gm-4">Position</label>
    <input id="widget_pos" name="widget_pos" value="<?=$widget->pos?>" class="gm-6 g-input">
</div>
</div>

<div class="gm-6">
    <label class="gm-4">Title</label>
    <input id="widget_title" name="widget_title" value="<?=$widget->title?>" class="gm-6 g-input">
</div>
<hr>

<?php
global $db;
$widget_data = json_decode($db->value("SELECT data FROM widget WHERE id=? LIMIT 1;", $widget->id));
$widget_folder = 'src/'.gila::$widget[$widget->widget];

include $widget_folder.'/widget.php';

if(isset($options)) foreach($options as $key=>$op) {
    $values[$key] = isset($widget_data->$key)?$widget_data->$key:'';
}
include view::getViewFile('admin/optionInputs.php');

echo "</form>";
