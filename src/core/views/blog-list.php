
<!-- Posts -->
<div class="row wrapper">
    <div class="gm-9">
    <?php foreach ($c->posts as $r) { ?>
    <div class="gm-12 row gap-8px post-review">
            <?php
            if($img=view::thumb_sm($r['img'],$r['id'].'__sm.jpg')){
		       $title_gl='gs-9';
		       echo '<div class="gs-3">';
               echo '<img class="lazy" data-src="'.$img.'" style="width:100%; height:auto">';
		       echo '</div>';
            } else $title_gl='gm-12';
            ?>

        <div class="<?=$title_gl?>">
            <a href="<?=$c->get_url($r['id'],$r['slug'])?>">
                <h2 class="post-title" style="margin-top:0"><?=$r['title']?></h2>
            </a>
            <?=strip_tags($r['post'])?>
        </div>
    </div><!--hr-->
    <?php } ?>
    <!-- Pagination -->
    <ul class="g-nav pagination">
        <ul class="g-nav pagination">
            <?php
            $totalpages = $c->totalpages();
            for($pl=0;$pl<$totalpages;$pl++) { ?>
            <li class="">
                <a href="<?=router::url()?>?page=<?=$pl+1?>"><?=($pl+1)?></a>
            </li>
            <?php } ?>
        </ul>
    </ul>
    </div>
    <div class="gm-3 sidebar">
      <form method="get" class="inline-flex" action="<?=gila::make_url('blog')?>">
        <input name='search' class="g-input fullwidth" value="<?=(isset($search)?:'')?>">
        <button class="g-btn g-group-item" onclick='submit'><?=__("Search")?></button>
    </form>
      <?php view::widget_area('sidebar'); ?>
    </div>
</div>
