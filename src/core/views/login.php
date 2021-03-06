<!DOCTYPE html>
<html lang="en">

<head>
    <base href="<?=gila::base_url()?>">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?=gila::config('title')?> - <?=__('Log In')?></title>

    <link href="lib/gila.min.css" rel="stylesheet">
    <link href="lib/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
</head>

<body>
    <div class="gl-4 centered wrapper">
        <div class="border-buttom-main_ text-align-center">
            <div style="width:16%;display:inline-block">
                <img src="<?=gila::config('admin_logo')?:'assets/gila-logo.png'?>">
            </div>
            <h3><?=__('Log In')?></h3>
        </div>
        <?=view::alerts()?>
<?php
if(session::waitForLogin()==0) { ?>
        <form role="form" method="post" action="" class="g-form wrapper g-card bg-white">
                <div class="form-group">
                    <input class="form-control fullwidth" placeholder="E-mail" name="username" type="email" autofocus>
                </div>
                <div class="form-group ">
                    <input class="form-control fullwidth" placeholder="Password" name="password" id="pass" type="password" value="">
                </div>
                <input type="submit" class="btn btn-primary btn-block" value="Login">
                <?php event::fire('login.btn'); ?>
        </form>
        <label class="helptext"><input type="checkbox" oninput="if(this.checked) pass.type='text'; else pass.type='password';"> Show password</label>
<?php } ?>
        <p>
            <a href="login/password_reset"><?=__('forgot_pass')?></a>
            <?php if(gila::config('user_register')==1) echo '| <a href="login/register">'.__('Register').'</a>';?>
        </p>
    </div>

</body>

</html>
