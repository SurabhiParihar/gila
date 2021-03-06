<?php

use core\models\post as post;
use core\models\page as page;
use core\models\user as user;

/**
* The blog controller, get calls for display of posts
*/
class Blog extends controller
{
  public static $page; /** The page number */
  public static $totalPosts;
  public static $totalPages;
  public static $ppp; /** Posts per page */

  function __construct ()
  {
    self::$page = intval(@$_GET['page'])?:1;
    self::$ppp = 12;
    self::$totalPosts = null;
  }

  /**
  * The default action.
  * First checks if there is parameter for a post and calls postShow()
  * Then check if there is search parameter and renders blog-search.php
  * If none, will render homepage.php or frontpage.php
  * @see postShow()
  */
  function indexAction()
  {
    if ($id=router::get('page_id',1)) {
      $this->postShow($id);
      return;
    }
    if ($id=router::get('p',1)) {
      $this->postShow($id);
      return;
    }
    if ($s=router::get('search')) {
      $s = strip_tags($s);
      view::set('search', $s);
      view::set('posts',post::search($s));
      view::render('blog-search.php');
      return;
    }

    if($_GET['url']!='' || view::getViewFile('homepage.php')==false) {
      if ($r = page::getByIdSlug('')) {
        view::set('title',$r['title']);
        view::set('text',$r['page']);
        view::render('blog-homepage.php','blog');
        return;
      }
      view::set('page',blog::$page);
      view::set('posts',post::getPosts(['posts'=>self::$ppp,'page'=>self::$page]));
      view::render('frontpage.php');
    }
    else view::render('homepage.php');
  }

  /**
  * Displays new posts in xml feed
  */
  function feedAction()
  {
    $title = gila::config('title');
    $link = gila::config('base');
    $description = gila::config('slogan');
    $items = self::latestposts(20);
    include 'src/core/views/blog-feed.php';
  }

  /**
  * Displays posts with a specific tag
  */
  function tagAction()
  {
    $tag = router::get('tag',1);
    view::set('tag',$tag);
    view::set('page',self::$page);
    view::set('posts',post::getPosts(['posts'=>self::$ppp,'tag'=>$tag,'page'=>self::$page]));
    view::render('blog-tag.php');
  }

  /**
  * Display a list with all post tags
  */
  function tagsAction()
  {
    view::set('tags',post::getMeta('tag'));
    view::render('blog-tags.php');
  }

  /**
  * Display posts by a category
  */
  function categoryAction($category)
  {
    global $db;
    if(!is_numeric($category)) {
      $category = $db->value('SELECT id FROM postcategory WHERE slug=?', $category);
    }
    $name = $db->value("SELECT title from postcategory WHERE id=?",$category);
    gila::canonical('blog/category/'.$category.'/'.$name.'/');
    self::$totalPosts = post::total(['category'=>$category]);
    view::set('category', $name);
    view::set('page',self::$page);
    view::set('posts',post::getPosts(['posts'=>self::$ppp,'category'=>$category,'page'=>self::$page]));
    view::render('blog-category.php');
  }

  /**
  * Display posts by author
  */
  function authorAction()
  {
    global $db;
    $user_id = router::get('author',1);
    $res = $db->get("SELECT username,id from user WHERE id=? OR username=?",[$user_id,$user_id]);
    if($res) {
      view::set('author',$res[0][0]);
      view::set('posts',post::getPosts(['posts'=>self::$ppp,'user_id'=>$res[0][1]]));
    } else {
      view::set('author',__('unknown'));
      view::set('posts',[]);
    }
    view::render('blog-author.php');
  }


  /**
  * Display a post
  */
  function postShow($id=null)
  {
    global $db;

    if (($r = post::getByIdSlug($id)) && ($r['publish']==1)) {
      router::action('post');
      $id = $r['id'];
      if(!$r['user_id']) {
        $r['user_id'] = $db->value("SELECT user_id FROM post WHERE id=? OR slug=?", [$id,$id]);
      }
      $user_id = $r['user_id'];
      view::set('author_id',$user_id);
      view::set('title',$r['title']);
      view::set('slug',$r['slug']);
      view::set('text',$r['post']);
      view::set('id',$r['id']);
      view::set('updated',$r['updated']);

      gila::canonical('blog/'.$r['id'].'/'.$r['slug'].'/');
      view::meta('og:title',$r['title']);
      view::meta('og:type','website');
      view::meta('og:url', view::$canonical);
      view::meta('og:description',$r['description']);

      if ($r['img'] ) {
        view::set('img', $r['img']);
        view::meta('og:image', $r['img']);
        view::meta('twitter:image:src', gila::base_url($r['img']));
      } else if(gila::config('og-image')) {
        view::meta('og:image', gila::config('og-image'));
        view::meta('twitter:image:src', gila::base_url(gila::config('og-image')));
      } else {
        view::set('img', '');
      }

      if($r['tags']) {
        view::meta('keywords', $r['tags']);
      }

      if($value = gila::option('blog.twitter-card')) {
        view::meta('twitter:card', $value);
      }
      if($value = gila::option('blog.twitter-site')) {
        view::meta('twitter:site', '@'.$value);
      }

      if ($r = user::getById($user_id)) {
        view::set('author',$r['username']);
        view::meta('author',$r['username']);
        if($creator = user::meta($user_id, 'twitter_account'))
          view::meta('twitter:creator','@'.$creator);
      } else view::set('author',__('unknown'));

      view::render('single-post.php');
    }
    else {
      if($category = $db->value('SELECT id FROM postcategory WHERE slug=?', $id)) {
        $this->categoryAction($category);
        return;
      }
  
      if (($r = page::getByIdSlug($id)) && ($r['publish']==1)) {
        view::set('title',$r['title']);
        view::set('text',$r['page']);
        if($r['template']==''||$r['template']===null) {
          view::render('page.php');
        } else {
          view::render('page--'.$r['template'].'.php');
        }
      } else {
        http_response_code(404);
        view::render('404.php');
      }
    }
  }

  /**
  * Display posts by a search query
  */
  function searchAction()
  {
    if ($s=router::get('search',1)) {
      $s = strip_tags($s);
      view::set('search', $s);
      view::set('posts',post::search($s));
      view::render('blog-search.php');
      return;
    }
    view::set('page',self::$page);
    view::set('posts',self::post(['posts'=>(self::$ppp)]));
    view::render('frontpage.php');
  }

  static function post ($args = []) {
    $args['page'] = self::$page;
    return post::getPosts($args);
  }

  static function latestposts ($n = 10) {
    return post::getLatest($n);
  }

  static function posts ($args = []) {
    $args['page'] = self::$page;
    return post::getPosts($args);
  }

  static function totalposts ($args = []) {
    if(self::$totalPosts == null) return post::total($args);
    return self::$totalPosts;
  }

  static function totalpages ($args = []) {
    $totalPosts = self::totalposts($args);
    self::$totalPages = floor(($totalPosts+self::$ppp)/self::$ppp);
    return self::$totalPages;
  }

  static function get_url($id,$slug=NULL)
  {
    if($slug==NULL) return gila::make_url('blog','',['p'=>$id]);
    return gila::make_url('blog','',['p'=>$id,'slug'=>$slug]);
  }

  static function thumb_sm($img,$id)
  {
    $target = 'post_sm/'.str_replace(["://",":\\\\","\\","/",":"], "_", $img);
    return view::thumb_sm($img, $target);
  }

}
