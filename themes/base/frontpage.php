<!-- Page Header -->
<!--header class="intro-header" style="background-color:#333; background-image: url('themes/startbootstrap-clean-blog/img/home-bg.jpg')">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1">
                <div class="site-heading">
                    <h1><?=gila::config('title')?></h1>
                    <hr class="small">
                    <span class="subheading"><?=gila::config('slogan')?></span>
                </div>
            </div>
        </div>
    </div>
</header-->

<!-- Posts -->
<div class="wrapper row gap-8px" style="background:#e8e8e8">
    <?php foreach (blog::post() as $r) { ?>
    <div class="gl-12 gm-4 gs-6">
    <div class="bordered  row" style="background:white">
        <div class="gl-2 wrapper">
            <?php
            if($img=view::thumb_sm($r['img'],$r['id'].'__sm.jpg')){
                echo '<img src="'.$img.'" style="width:100%; height:auto">';
            }
            ?>

        </div>
        <div class="gl-10 wrapper">
            <a href="<?=$r['id']?>">
                <h3 class="post-title" style="margin-top:0"><?=$r['title']?></h3>
            </a>
            <?=nl2br(strip_tags($r['post']))?>
        </div>
    </div>
    </div>
    <?php } ?>
    <!-- Pagination -->
    <ul class="g-nav">
        <li class="">
            <a href="?page=<?=$page+1?>">Older Posts &rarr;</a>
        </li>
    </ul>
</div>
<?php
global $starttime;
$end = microtime(true);
$creationtime = ($end - $starttime);
printf("<br>Page created in %.6f seconds.", $creationtime);
?>
