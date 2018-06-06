<?php

use core\models\widget;
use core\models\user;
use core\models\page;

class admin extends controller
{

    public function __construct ()
    {
        self::admin();
        gila::addLang('core/lang/admin/');
    }

    /**
    * Renders admin/dashboard.php
    */
    function indexAction ()
    {
      global $db;
      $wfolders=['log','themes','src','tmp','assets'];
      foreach($wfolders as $wf) if(is_writable($wf)==false) {
          view::alert('warning', $wf.' folder is not writable. Permissions may have to be adjusted.');
      }
      view::set('posts',$db->value('SELECT count(*) from post;'));
      view::set('pages',$db->value('SELECT count(*) from page;'));
      view::set('users',$db->value('SELECT count(*) from user;'));
      view::set('packages',count($GLOBALS['config']['packages']));
      view::renderAdmin('admin/dashboard.php');
    }

    /**
    * List and edit posts
    */
    function postsAction ()
    {
        global $db;
        if ($id = router::get('id',1)) {
            view::set('id',$id);
            view::renderAdmin('admin/edit_post.php');
            return;
        }
        view::renderAdmin('admin/post.php');
    }

    /**
    * List and edit pages
    */
    function pagesAction ()
    {
        global $db;
        if ($id = router::get('id',1)) {
            view::set('id',$id);
            view::renderAdmin('admin/edit_page.php');
            return;
        }
        view::renderAdmin('admin/page.php');
    }

    /**
    * List and edit post categories
    */
    function postcategoriesAction ()
    {
        view::renderAdmin('admin/postcategory.php');
    }

    /**
    * List and edit widgets
    */
    function widgetsAction ()
    {
        global $db;

        if ($id = router::get('id',1)) {
            view::set('widget',widget::getById($id));
            view::renderFile('admin/edit_widget.php');
            return;
        }
        view::renderAdmin('admin/list_widget.php');
    }

    function contentAction()
    {
        $type = router::get('type',1);
        $src = explode('.',gila::$content[$type])[0];
        view::set('table', $type);
        view::set('tablesrc', $src);
        view::renderAdmin('admin/content.php');
    }

    function update_widgetAction ()
    {
        global $db;
        $widget_data =json_encode($_POST['option']);
        echo $widget_data;
        if (isset($_POST['option'])) {
            $db->query("UPDATE widget SET data=?,area=?,pos=?,title=? WHERE id=?",[$widget_data,$_POST['widget_area'],$_POST['widget_pos'],$_POST['widget_title'],$_POST['widget_id']]);
            echo $_POST['widget_id'];
        }
    }

    function usersAction ()
    {
        view::renderAdmin('admin/user.php');
    }

    /**
    * List and manage installed packages
    * @photo
    */
    function packagesAction ()
    {
        new package();
        $tab = router::get('tab',1);
        $packages = [];

        if($tab == 'new') {
            if(!$contents=file_get_contents('http://gilacms.com/packages/')) {
                view::alert('error',"Could not connect to packages list. Please try later.");
                exit;
            }
            $packages = json_decode($contents);
        } else if($tab == 'search'){
            $search = router::get('search',2);
            if(!$contents=file_get_contents('http://gilacms.com/packages/?search='.$search)) {
                view::alert('error',"Could not connect to packages list. Please try later.");
                exit;
            }
            //$packages = package::scan();
            $packages = json_decode($contents);
        } else {
            $packages = package::scan();
        }
        view::set('packages',$packages);
		view::renderAdmin('admin/package-list.php');
    }

    function newthemesAction ()
    {
        if(!$contents=file_get_contents('http://gilacms.com/packages/themes')) {
            echo "<br>Could not connect to themes list. Please try later.";
            exit;
        }
        $packages = json_decode($contents);
        view::set('packages',$packages);
        view::renderAdmin('admin/theme-list.php');
    }

    function themesAction ()
    {
        new theme();
        $packages = theme::scan();
        view::set('packages',$packages);
		view::renderAdmin('admin/theme-list.php');
    }

    function settingsAction ()
    {
        view::renderAdmin('admin/settings.php');
    }

    function loginAction ()
    {
        view::renderAdmin('login.php');
    }

    function logoutAction ()
    {
        global $db;
        //if(isset($_COOKIE['GSESSIONID']))
            $res = $db->query("DELETE FROM usermeta WHERE user_id=? AND vartype='GSESSIONID';",[session::key('user_id')]);
        session::destroy();
        echo "<meta http-equiv='refresh' content='0;url=".gila::config('base')."' />";
    }

    function media_uploadAction(){
        if(isset($_FILES['uploadfiles'])) {
            if (isset($_FILES['upload_files']["error"])) if ($_FILES['upload_files']["error"] > 0) {
                echo "Error: " . $_FILES['upload_files']['error'] . "<br>";
            }
            $path = router::post('path','assets');
            if(!move_uploaded_file($_FILES['uploadfiles']['tmp_name'],$path.'/'.$_FILES['uploadfiles']['name'])) {
                echo "Error: could not upload file!<br>";
            }
        }
        self::mediaAction();
    }

    function mediaAction()
    {
        view::renderAdmin('admin/media.php');
        event::fire('admin::media');
    }

    function db_backupAction()
    {
        new db_backup();
    }

    function updateAction()
    {
        $zip = new ZipArchive;
        $target = 'src/core';
        $file = 'http://gilacms.com/assets/packages/core'.$download.'.zip';
        $localfile = 'src/core.zip';
        if (!copy($file, $localfile)) {
          echo "Failed to download new version!";
        }
        if ($zip->open($localfile) === TRUE) {
          if(!file_exists($target)) mkdir($target);
          $zip->extractTo($target);
          $zip->close();
          include 'src/core/update.php';
          echo 'Gila CMS successfully updated to '.$version;
        } else {
          echo 'Failed to download new version!';
        }
    }

    function profileAction()
    {
        $user_id = session::key('user_id');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') if (router::post('submit-btn')=='submited'){
            user::updateName($user_id, $_POST['gila_username']);
            user::meta($user_id, 'twitter_account', $_POST['twitter_account']);
            //echo "<span class='alert success'>Name changed successfully<span>";
        }
        view::set('twitter_account',user::meta($user_id,'twitter_account'));
        view::renderAdmin('admin/myprofile.php');
    }

    function phpinfoAction()
    {
        view::includeFile('admin/header.php');
        phpinfo();
        view::includeFile('admin/footer.php');
    }

    function menuAction()
    {
        $menu = router::get('menu',1);
        if($menu != null) if($_SERVER['REQUEST_METHOD'] == 'POST') if(gila::hasPrivilege('admin')) {
            if(isset($_POST['menu'])) {
                $folder = gila::dir('log/menus/');
                file_put_contents($folder.$menu.'.json',$_POST['menu']);
                echo json_encode(["msg"=>"saved"]);
            }
            exit;
        }
        view::set('menu',($menu?:'mainmenu'));
        view::renderAdmin('admin/menu_editor.php');
    }

    function sendmailAction()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && event::get('recaptcha',true)) {
            $baseurl = gila::config('base');
            $email = gila::config('admin_email');
            $subject = "Message from ".$baseurl;
            $message = "";
            $headers = "From: GilaCMS <noreply@{$_SERVER['HTTP_HOST']}>";

            foreach($_POST as $key=>$post) {
                $message .= "$key:\n$post\n\n";
            }

            mail($email,$subject,$message,$headers);

            echo "ok";
            return;
        }
        echo "<meta http-equiv=\"refresh\" content=\"2;url=/\" />";
    }

}
