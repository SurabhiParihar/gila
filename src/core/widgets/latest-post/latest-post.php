<ul class="g-nav vertical">
<?php

if(!@class_exists('blog')) {
	include_once "src/core/controllers/blog.php";
	new blog();
}

$stacked_file = 'tmp/stacked-wdgt'.$widget_id.'.png';
$posts = [];
$img = [];
$widget_data->n_post = @$widget_data->n_post?:5;
$widget_data->show_thumbnails = @$widget_data->show_thumbnails?:1;

foreach (core\models\post::getLatest($widget_data->n_post) as $r ) {
	$posts[] = $r;
	$img[]=$r['img'];
}
$stacked = view::thumb_stack($img, $stacked_file,80,80);

foreach ($posts as $key=>$r ) {
	echo "<li>";
	echo "<a href='".blog::get_url($r['id'],$r['slug'])."'>";
	if($widget_data->show_thumbnails == 1) if($stacked[$key]==false){
		if($img=view::thumb_xs($r['img'])) {
			echo "<img src='$img' style='float:left;margin-right:6px'> ";
		} else echo "<div style='width:80px;height:40px;float:left;margin-right:6px'></div> ";
	} else {
		$i = $stacked[$key];
		echo "<div style='background:url($stacked_file) 0 -{$i['top']}px; width:{$i['width']}px;height:{$i['height']}px;float:left;margin-right:6px'></div> ";
	}
	echo "{$r['title']}</a></li>";
}
?>
</ul>
