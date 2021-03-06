<?php

use core\models\user;

class session
{
  private static $started = false;
  private static $waitForLogin = 0;
  private static $user_id;

  static function start ()
  {
    if(self::$started==true) return;
    //ini_set("session.save_handler", "files");
    //ini_set("session.save_path", __DIR__."/../../../log/sessions");
    //ini_set('session.gc_maxlifetime', 24*3600);
    session_set_cookie_params(24*3600);

    try {
      @session_start();
    } catch (Exception $e) {
      trigger_error($e->getMessage());
    }
    self::$started = true;
    session::define(['user_id'=>0]);

    if (isset($_POST['username']) && isset($_POST['password']) && session::waitForLogin()==0) {
      $usr = user::getByEmail($_POST['username']);
      if ($usr && $usr['active']==1 && password_verify($_POST['password'], $usr['pass'])) {
        session::user($usr['id'], $usr['username'], $usr['email'], 'Log In');
        $chars = 'bcdfghjklmnprstvwxzaeiou123467890';
        $gsession = (string)$usr['id'];
        for ($p = strlen($gsession); $p < 50; $p++) $gsession .= $chars[mt_rand(0, 32)];
        user::meta($usr[0], 'GSESSIONID', $gsession, true);
        setcookie('GSESSIONID', $gsession, time() + (86400 * 30), "/");
        unset($_SESSION['failed_attempts']);
      } else {
        @$_SESSION['failed_attempts'][] = time();
        $session_log = new logger(LOG_PATH.'/login.failed.log');
        $session_log->log($_SERVER['REQUEST_URI'], htmlentities($_POST['username']));
      }
    } else {
      if(session::user_id()==0) if(isset($_COOKIE['GSESSIONID'])) {
        foreach (user::getIdsByMeta('GSESSIONID', $_COOKIE['GSESSIONID']) as $user_id) {
          $usr = user::getById($user_id);
          if ($usr && $usr['active']==1) {
            session::user($usr['id'], $usr['username'], $usr['email'], 'By cookie');
          }
        }
      }
    }
  }

  static function user ($id, $name, $email, $msg=null)
  {
    session::key('user_id',$id);
    session::key('user_name',$name);
    session::key('user_email',$email);
    self::$user_id = $id;
    if($msg!==null) {
      $session_log = new logger(LOG_PATH.'/sessions.log');
      $session_log->info($msg,['user_id'=>$id, 'email'=>$email]);
    }
  }

  /**
  * Define new session variables
  * @param $vars Associative Array
  */
  static function define ($vars)
  {
    self::start();
    foreach ($vars as $k=>$v) if(!isset($_SESSION[session::md5($k)])) {
      $_SESSION[session::md5($k)]=$v;
    }
  }

  /**
  * Returns or sets value for a session variable
  * @param $var (string) Variable name
  * @param $val (optional) Variable value, if is not set the function will return the current value
  * @param $t optional (int) Time in seconds to save the variable in the cookie (not saved if not set)
  * @return Variable value
  */
  static function key ($var, $val = null, $t = 0)
  {
    self::start();
    if ($val == null) {
      return $_SESSION[session::md5($var)]?? null;
    }
    $_SESSION[session::md5($var)] = $val;

    if($t !== 0){
      if(is_object($val) || is_array($val)){
      $value = json_encode($val);
      }
      setcookie(session::md5($var), $val, (time() + $t), "/", $_SERVER["HTTP_HOST"]);
    }
  }

  /**
  * Unsets a session variable
  * @param $var (string) Variable name
  */
  static function unsetKey ($var)
  {
    self::start();
    unset($_SESSION[session::md5($var)]);
  }

  private static function md5 ($key)
  {
    $dbname = $GLOBALS['config']['db']['name'];
    return md5($dbname.$key);
  }

  /**
  * Returns user id
  * @return int User's id. 0 if user is not logged in.
  */
  static function user_id ()
  {
    if(isset(self::$user_id)) return self::$user_id;
    self::$user_id = 0;
    $token = $_REQUEST['token'] ?? ($_SERVER['HTTP_TOKEN'] ?? null);
    if($token) {
      $usr = user::getByMeta('token', $token);
      if($usr) {
        self::$user_id = $usr['id'];
      }
    } else {
      self::start();
      self::$user_id = $_SESSION[session::md5('user_id')];
    }
    return self::$user_id;
  }

  /**
  * Destroys the session session
  */
  static function destroy ()
  {
    if(self::user_id()>0) {
      $session_log = new logger(LOG_PATH.'/sessions.log');
      $session_log->info('End',['user_id'=>self::user_id(), 'email'=>self::key('user_email')]);
    }
    @$_SESSION = [];
    @session_destroy();
  }

  static function waitForLogin()
  {
    $wait = 0;
    session::define(['failed_attempts'=>[]]);
    if(@$_SESSION['failed_attempts']) {
      foreach($_SESSION['failed_attempts'] as $key=>$value) {
        if($value+120<time()) array_splice($_SESSION['failed_attempts'], $key, 1);
      }
      $attempts = count($_SESSION['failed_attempts']);
      if($attempts<5) return 0;
      $lastTime = $_SESSION['failed_attempts'][$attempts-1];
      $wait = $lastTime-time()+60;
      $wait = $wait<0? 0: $wait;
    }
    return $wait;
  }

}
