<?php view::includeFile('header.php')?>
<div class="wrapper">
  <h1><?=$title?></h1>

  <div>
      <?=$text?>
  </div>

  <form role="form" method="post" action="<?=$_SERVER['REQUEST_URI']?>" class="g-form bg-white wrapper">
    <?=gForm::hiddenInput('contact-form'.$widget_data->widget_id)?>
    <?php view::alerts() ?>
    <label><?=__("Name")?></label>
    <input name="name" class="form-control g-input" autofocus required/>
    <label><?=__("E-mail")?></label>
    <input name="email" class="form-control g-input" required/>
    <label><?=__("Subject")?></label>
    <textarea name="message" class="form-control g-input" style="resize:vertical" required></textarea>
    <?php event::fire('recaptcha.form')?>
    <input type="submit" class="btn btn-primary btn-block" value="<?=__('Send')?>">
  </form>

</div>
<?php view::includeFile('footer.php')?>
