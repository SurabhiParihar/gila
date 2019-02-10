<?php
namespace core\models;

class widget
{
  static function getById($id)
  {
    global $db;
    $res = $db->query("SELECT * FROM widget WHERE id=?",$id);
    return mysqli_fetch_object($res);
  }

  static function getByWidget($w)
  {
    global $db;
    return $db->query("SELECT * FROM widget WHERE widget=?",$w);
  }

  static function getActiveByArea($area)
  {
    global $db;
    $db->connect();
    return $db->get("SELECT * FROM widget WHERE active=1 AND area=? ORDER BY pos;",$area);
  }
}
