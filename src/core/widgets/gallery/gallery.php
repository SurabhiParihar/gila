<div class="gallery">
  <?php if(isset($widget_data->images)) foreach(json_decode($widget_data->images) as $img) { ?>
  <img src="<?=view::thumb($img[0])?>">
  <?php } ?>
</div>