<?php


class package
{

  function __construct()
  {
    $activate = router::get('activate');
    if($activate) self::activate($activate);
    $deactivate = router::get('deactivate');
    if($deactivate) self::deactivate($deactivate);
    $download = router::get('download');
    if($download && FS_ACCESS) {
      if(self::download($download)==true) {
        if(!$_REQUEST['g_response']) {
          echo '<meta http-equiv="refresh" content="2;url='.gila::base_url().'/admin/packages" />';
          echo __('_package_downloaded').'. Redirecting...';
        } else echo 'ok';
      } else echo __('_package_not_downloaded');
      exit;
    }
    $save_options = router::get('save_options');
    if($save_options) self::save_options($save_options);
    $options = router::post('options');
    if($options) self::options($options);
  }

  /**
  * Activates a package
  * @param $activate (string) Package name to activate
  */
  static function activate($activate)
  {
    if (in_array($activate, scandir('src/'))) {
      if(!in_array($activate, $GLOBALS['config']['packages'])) {
        $pac=json_decode(file_get_contents('src/'.$activate.'/package.json'),true);
        $require = [];
        $require_op = [];
        if(isset($pac['require'])) foreach ($pac['require'] as $key => $value) {
          if(!in_array($key, gila::packages())&&($key!='core')) {
            if(!file_exists('vendor/'.$key)) $require[$key]=$value;
          } else {
            $pacx=json_decode(file_get_contents('src/'.$key.'/package.json'),true);
            if(version_compare($pacx['version'], $value) < 0) $require[$key]=$value;
          }
        }
        if(isset($pac['options'])) {
          foreach($pac['options'] as $key=>$option) if(@$option['required']==true) {
            if(gila::option($activate.'.'.$key)==null) $require_op[] = @$option['title']?:$key;
          }
        }

        if($require==[] && $require_op==[]) {
          $GLOBALS['config']['packages'][]=$activate;
          $updatefile = 'src/'.$activate.'/update.php';
          if(file_exists($updatefile)) include $updatefile;
          gila::updateConfigFile();
          self::updateLoadFile();
          usleep(300);
          view::alert('success',__('_package_activated'));
          echo 'ok';
        }
        else {
          if($require!=[]) {
            echo __('_packages_required').':';
            foreach($require as $k=>$r) {
              if(strpos($k, '/')) {
                echo "<br><a href='https://gilacms.com/blog/36' target='_blank'>$k $r</a>";
              } else echo "<br><a href='admin/packages/new?search=$k' target='_blank'>$k v$r</a>";
            }
          }
          if($require_op!=[]) {
            echo '<br>'.__('_options_required').': '.implode(', ', $require_op);
          }
        }
      } else echo __("_package_activated");
    } else echo __("_package_not_downloaded");
    exit;
  }

  /**
  * Deactivates a package and its dependecies
  * @param $deactivate (string) Package name to deactivate
  */
  static function deactivate($deactivate)
  {
    if (in_array($deactivate,$GLOBALS['config']['packages'])) {
      $key = array_search($deactivate, $GLOBALS['config']['packages']);
      unset($GLOBALS['config']['packages'][$key]);

      // deactivate other packages that require $deactivate
      foreach($GLOBALS['config']['packages'] as $p) {
        $string = file_get_contents("/src/$p/package.json");
        $json_p = json_decode($string, true);
        if(isset($json_p['require'])) if(isset($json_p['require'][$deactivate])) {
          $key = array_search($deactivate, $GLOBALS['config']['packages']);
          if($key !== false) unset($GLOBALS['config']['packages'][$key]);
        }
      }
      gila::updateConfigFile();
      self::updateLoadFile();
      usleep(100);
      $alert = gila::alert('success',__("_package_deactivated"));
      exit;
    }
    exit;
  }

  /**
  * Downloads a package from gilacms.com assets in zip
  * @param $package (string) Package name to download
  */
  static function download($package)
  {
    if (!$package) {
      return false;
    }
    $zip = new ZipArchive;
    $target = 'src/'.$package;
    $request = 'https://gilacms.com/packages/?package='.$package;
    $pinfo = json_decode(file_get_contents($request), true)[0];

    if(!$pinfo) {
      return false;
    }
    $file = 'https://gilacms.com/assets/packages/'.$package.'.zip';

    if(substr($pinfo['download_url'],0,8)=='https://'){
      $file = $pinfo['download_url'];
    }

    if(isset($_GET['src'])) $file = $_GET['src'];
    $tmp_name = $target.'__tmp__';
    $localfile = 'src/'.$package.'.zip';

    if (!copy($file, $localfile)) {
      return false;
    }
    if ($zip->open($localfile) === true) {
      $zip->extractTo($tmp_name);
      $zip->close();
      if(file_exists($target)) {
        rename($target, gila::dir(LOG_PATH.'/previous-packages/'.date("Y-m-d H:i:s").' '.$package));
      }
      $unzipped = scandir($tmp_name);
      if(count(scandir($tmp_name))==3) if($unzipped[2][0]!='.') $tmp_name .= '/'.$unzipped[2];
      rename($tmp_name, $target);
      if(file_exists($target.'__tmp__')) rmdir($target.'__tmp__');
      $update_file = 'src/'.$package.'/update.php';
      self::runUpdateFile($update_file);
      unlink(LOG_PATH.'/load.php');
      unlink($localfile);
      return true;
    }
    return false;
  }

  static function runUpdateFile($update_file) {
    global $db;
    if(file_exists($update_file)) {
      include $update_file;
      $sites = scandir('sites');
      foreach($sites as $site) if($site[0]!='.'){
        $config = 'sites/'.$site.'/config.php';
        if(file_exists($config)) {
          include $config;
          $db = new db($GLOBALS['config']['db']);
          include $update_file;
        }
      }
    }
  }

  /**
  * Returns the package options on html
  * @param $package (string) Package name to generate the options code
  */
  static function options($package)
  {
    if (file_exists('src/'.$package)) {
      global $db;
      echo '<form id="addon_options_form" class="g-form">';
      echo '<input id="addon_id" value="'.$package.'" type="hidden">';
      $pack=$package;
      if(file_exists('src/'.$package.'/package.json')) {
        $pac=json_decode(file_get_contents('src/'.$package.'/package.json'),true);
        @$options=$pac['options'];
      } else die('Could not find src/'.$package.'/package.json');

      if(is_array($options)) {
        foreach($options as $key=>$op) {
          $values[$key] = gila::option($pack.'.'.$key);
        }
        echo gForm::html($options,$values,'option[',']');
      } // else error alert
      echo "</form>";
      exit;
    }
    exit;
  }

  /**
  * Saves option values for a package
  * @param $package (string) Package name
  */
  static function save_options($package)
  {
    if (file_exists('src/'.$package)) {
      global $db;
      foreach($_POST['option'] as $key=>$value) {
        $ql="INSERT INTO `option`(`option`,`value`) VALUES('$package.$key','$value')
          ON DUPLICATE KEY UPDATE `value`='$value';";
        $db->query($ql);
      }
      if(gila::config('env')=='pro') unlink(LOG_PATH.'/load.php');
      exit;
    }
  }

  /**
  * Returns the installed packages in an array option values for a package
  * @return Array Packages
  */
  static function scan()
  {
    $dir = "src/";
    $scanned = scandir($dir);
    $_packages = [];
    foreach($scanned as $folder) if($folder[0] != '.'){
      $json = $dir.$folder.'/package.json';
      if(file_exists($json)) {
        $data = json_decode(file_get_contents($json));
        @$data->title = @$data->title?? @$data->name;
        $data->package = $folder;
        $data->url = @$data->homepage?? (@$data->url?? '');
        $_packages[$folder] = $data;
      }
    }
    return $_packages;
  }

  /**
  * Combines all package load.php files in log/load.php
  * @return Array Packages
  */
  static function updateLoadFile()
  {
    global $db;
    $file = LOG_PATH.'/load.php';
    $contents = file_get_contents('src/core/load.php');//"/*--- Load file ---*/";
    foreach(gila::packages() as $package) {
      $handle = @fopen("src/$package/load.php", "r");
      if ($handle) {
        $line = fgets($handle);
        $contents .= "\n\n/*--- $package ---*/";
        while (($line = fgets($handle)) !== false) {
          $contents .= $line;
        }

        fclose($handle);
      } else {
        // error op
      }
    }
    gila::$option=[];
    $db->connect();
  	$res = $db->get('SELECT `option`,`value` FROM `option`;');
  	foreach($res as $r) gila::$option[$r[0]] = $r[1];
    $db->close();

    $contents .= "\n\ngila::\$option = ".var_export(gila::$option, true).";\n";

    file_put_contents($file, $contents);
  }

  static function check4updates()
  {
    if(gila::config('check4updates')==0) return;
    $now = new DateTime("now");
    if(gila::option('checked4updates')==null) {
      gila::setOption('checked4updates', $now->format('Y-m-d'));
      $diff = 1000;
    } else {
      $diff = date_diff(new DateTime(gila::option('checked4updates')), new DateTime("now"))->format('%a');
    }

    // check after 2 days
    if($diff>2) {
      $installed_packages = self::scan();
      $packages2update = [];
      $versions = [];
      $uri = "https://gilacms.com/addons/package_versions?p[]=".implode('&p[]=',array_keys($installed_packages));
      if($res = file_get_contents($uri)) {
        gila::setOption('checked4updates', $now->format('Y-m-d H:i:s'));
        $versions = json_decode($res,true);
      }
      foreach($installed_packages as $ipac=>$pac) {
        if(isset($versions[$ipac]) && version_compare($versions[$ipac], $pac->version) == 1)
          $packages2update[$ipac] = $versions[$ipac];
      }
      if($packages2update != [])
        file_put_contents(LOG_PATH.'/packages2update.json',json_encode($packages2update,JSON_PRETTY_PRINT));
    }

    if(file_exists(LOG_PATH.'/packages2update.json')) return true;
    return false;
  }

}
