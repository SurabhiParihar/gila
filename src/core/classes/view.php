<?php

class view
{
    private static $part = array();
    private static $stylesheet = array();
    private static $script = array();
    private static $scriptAsync = array();
    private static $meta = array();

	static function set($param,$value) {
        self::$part[$param]=$value;
	}

    static function meta($meta,$value)
    {
        self::$meta[$meta]=$value;
    }
    static function stylesheet($href)
    {
        if(in_array($href,self::$stylesheet)) return;
        self::$stylesheet[]=$href;
    }

    static function links()
    {
        foreach (self::$stylesheet as $link) echo '<link href="'.$link.'" rel="stylesheet">';
    }

    static function script($script)
    {
        if(in_array($script,self::$script)) return;
        self::$script[]=$script;
    }

    static function scriptAsync($script)
    {
        if(in_array($script,self::$scriptAsync)) return;
        self::$scriptAsync[]=$script;
    }

    static function getThemePath()
    {
        if(isset($_GET['g_preview_theme'])) return 'themes/'.$_GET['g_preview_theme'];
        return 'themes/'.gila::config('theme');
    }

    static function renderAdmin($file, $package = 'core')
    {
        if(router::request('g_response')=='content') {
            self::renderFile($file, $package);
            return;
        }
        self::includeFile('admin/header.php');
        self::renderFile($file, $package);
        self::includeFile('admin/footer.php');
    }


    static function render($file, $package = 'core')
    {
        if(router::request('g_response')=='json') {
            foreach (self::$part as $key => $value) if(is_object($value)) {
                self::$part[$key]=[];
                foreach($value as $r) {
                    self::$part[$key][]=(array)$r;
                }
            }
            echo json_encode(self::$part);
            exit;
        }

        if(router::request('g_response')=='content') {
            self::renderFile($file, $package);
            return;
        }
        self::includeFile('header.php');
        self::renderFile($file, $package);
        self::includeFile('footer.php');
    }

    static function head()
    {
        self::includeFile('head.php');
    }

    static function findPath($file, $package = 'core')
    {
        $tpath = self::getThemePath().'/'.$file;
        if(file_exists($tpath)) {
            return $tpath;
        } else {
          $spath = 'src/'.$package.'/views/'.$file;
          if(file_exists($spath)) {
              return $spath;
          }
        }
        return false;
    }

    static function renderFile($file, $package = 'core')
    {
        global $c;
        foreach (self::$part as $key => $value) {
            $$key = $value;
            @$c->$key = $value;
        }

        $tpath = self::getThemePath().'/'.$file;
        if(file_exists($tpath)) {
            include $tpath;
            return;
        }
        $spath = 'src/'.$package.'/views/'.$file;
        if(file_exists($spath)) {
            include $spath;
        }

        if(router::request('g_response')!='content')
            foreach(self::$script as $src) echo '<script src="'.$src.'"></script>';
	}

    static function includeFile($file,$package='core')
    {
        global $c;
        $tpath = self::getThemePath().'/'.$file;
        if(file_exists($tpath)) {
            include $tpath;
            return;
        }
        $spath = 'src/'.$package.'/views/'.$file;
        if(file_exists($spath)) {
            include $spath;
        }
    }

/**
 * Widget
 *
 * @widget  name of the widget
 *
 */

    static function widget ($widget,$widget_exp=null)
    {
        global $db,$widget_data;
        if($widget_exp==null) $widget_exp=$widget;
        $mm = gila::config('default.'.$widget);
        if($mm > 0) {
            $res = $db->get("SELECT data FROM widget WHERE id=?;",[$mm])[0];
            $widget_data = json_decode($res['data']);
        }

        $filePath = gila::config('theme').'/widgets/'.$widget.'.php';
        //$widget_data = json_decode($db->get("SELECT data FROM widget WHERE active=1 AND widget=? LIMIT 1;", $widget)[0][0]);

        if (file_exists($filePath)) {
            include $filePath;
        }
        else {
            $filePath = 'src/core/widgets/'.$widget.'/'.$widget_exp.'.php';
            if (file_exists($filePath)) {
                include $filePath;
            }
            else {
                echo $filePath." file not found!";
            }
        }
    }

    static function block ($area)
    {
        view::widget_area($area);
    }
    static function widget_area ($area,$div=true)
    {
        global $db,$widget_data;
        $widgets = $db->get("SELECT * FROM widget WHERE active=1 AND area=? ORDER BY pos ;",[$area]);
        if ($widgets) foreach ($widgets as $widget) {
          $widget_data = json_decode($widget['data']);
          $widget_file = "src/".gila::$widget[$widget['widget']]."/{$widget['widget']}.php";
          if($div){
              echo '<div class="widget">';
              if($widget['title']!='') echo '<div class="widget-title">'.$widget['title'].'</div>';
              echo '<div class="widget-body">';
          }
          include $widget_file;
          if($div) echo '</div></div>';
        }
        event::fire($area);
    }

    static function thumb ($src,$id,$max=180)
    {
        if($src==null) return false;
        $file = 'tmp/'.$id;
        $max_width=$max;
        $max_height=$max;
        if($src=='') return false;
        if (!file_exists($file)) {
            image::make_thumb($src,$file,$max_width,$max_height);
        }
        return $file;
    }

    static function thumb_xs ($src,$id)
    {
        return view::thumb($src,$id,80);
    }
    static function thumb_sm ($src,$id)
    {
        return view::thumb($src,$id,160);
    }
    static function thumb_md ($src,$id)
    {
        return view::thumb($src,$id,320);
    }
    static function thumb_lg ($src,$id)
    {
        return self::thumb($src,$id,640);
    }
    static function thumb_xl ($src,$id)
    {
        return view::thumb($src,$id,1200);
    }

}
