<?=view::css('lib/CodeMirror/codemirror.css')?>
<style>
#main-wrapper{background:none!important;border:none;margin:0}
.fm_dir{overflow: hidden;padding:10px;line-height:1.5;background:white;}
.fm_dir a{color:#333}
.fm_dir a:hover{color:#000}
.fm_dir i{color:grey}
.fm_file{overflow: hidden;}
.g-btn{padding:6px}
.CodeMirror{height:auto}
#textarea{opacity:0;overflow:scroll;}
</style>
<?php
$mode_ext = ['php'=>'php','html'=>'htmlmixed','htm'=>'htmlmixed','js'=>'javascript','css'=>'css'];
$img_ext = ['jpg','jpeg','gif','png','svg','ico','tiff'];
$filepath = substr($c->filepath, strlen(realpath(''))+1); 
$pathinfo = pathinfo($filepath);
$ext = $pathinfo['extension'];

$dirname = $pathinfo['dirname'];
if(is_dir($filepath)) $dirname = $filepath;
if($dirname=='') $dirname = '.';
?>

<div style="display:grid; grid-template-columns: 250px 1fr;grid-gap:1em">

  <div class="fm_dir" style="border:1px solid lightgrey;"></div>

  <div class="fm_file">
    <div class="wrapper"><strong><?=$filepath?></strong>
    <?php
    if(in_array($ext ,$img_ext)) {
      echo '</div><img src="'.$_GET['f'].'" style="max-width:400px">';
    } else if(is_dir($filepath) || $filepath=='') {
      // do nothing
    } else {
      $value = htmlentities(file_get_contents($c->filepath));
    ?>
    <span class="g-btn" onclick="savefile('<?=$filepath?>')"><?=_('Save')?></span>
    <span class="g-btn" onclick="movefile('<?=$filepath?>')"><?=_('Rename')?></span>
    <span class="g-btn" onclick="deletefile('<?=$filepath?>')"><?=_('Delete')?></span>
    </div>
    <textarea id="textarea"><?=$value?></textarea>
    
    <script src="lib/CodeMirror/codemirror.js"></script>
    <script src="lib/CodeMirror/css.js"></script>
    <script src="lib/CodeMirror/xml.js"></script>
    <script src="lib/CodeMirror/htmlmixed.js"></script>
    <script src="lib/CodeMirror/javascript.js"></script>
    
    <script>
    requiredRes = new Array()
    var myCodeMirror = new Array();
    var saveFilePath;

    mirror = CodeMirror.fromTextArea(document.getElementById('textarea'),{
        lineNumbers:true
    });
    </script>
    <?php
    }
    ?>
    
<script>
updateDir("<?=$dirname?>");

function updateDir(path) {
  dir_path = path
  g.get('?c=fm&action=dir&path='+path, function(data){
    file = JSON.parse(data).files
    html = ''
    for (i=0; i<file.length; i++) {
      if(file[i].name=='dir') {
        html += '<span onclick="admin/fm?f='+path+'/'+file[i].name+'">'+get_file_icon(file[i].ext)+' '+file[i].name+'</span><br>';
      } else {
        html += '<a href="admin/fm?f='+path+'/'+file[i].name+'">'+get_file_icon(file[i].ext)+' '+file[i].name+'</a><br>';
      }
    }
    html += ' <span class="g-btn" onclick="createDir()"><?=_("+ Dir")?></span>'
    html += ' <span class="g-btn" onclick="createFile()"><?=_("+ File")?></span>'
    document.getElementsByClassName('fm_dir')[0].innerHTML=html;
  })
}

function get_file_icon(ext){
  icons={txt:'file-text',php:'file-text',jpg:'image',png:'image',gif:'image'}
  icon='file';
  if(typeof icons[ext]!='undefined') icon = icons[ext];
  if(ext=='') icon='folder-o';
  return '<i class="fa fa-'+icon+'"></i>'
}
 
function createDir() {
  path = prompt("Please enter the folder name", "New Folder");
  if(path != null) {
    g.loader()
    g.post('fm/newfolder', 'path='+dir_path+'/'+path,function(msg){
      g.loader(false)
      if(msg=='') msg="Folder created successfully"
      alert(msg);
      location.href = 'admin/fm?f='+dir_path
    })
  }
}
function createFile() {
  path = prompt("Please enter new file name", 'File.txt');
  if(path != null) {
    g.loader()
    g.post('fm/newfile', 'path='+dir_path+'/'+path,function(msg){
      g.loader(false)
      if(msg=='') msg="File created successfully"
      alert(msg);
      location.href = 'admin/fm?f='+dir_path+'/'+path
    })
  }
}
function savefile(path) {
  g.loader()
  $.post('fm/save', {contents:mirror.getValue(),path:path},function(msg){
    g.loader(false)
    if(msg=='') msg="File saved successfully"
    alert(msg);
  })
}
function movefile(path) {
  new_path = prompt("Please enter new file path", path);
  if(new_path != null) {
    g.loader()
    $.post('fm/move', {newpath:new_path, path:path},function(msg){
      g.loader(false)
      if(msg=='') msg="File saved successfully"
      alert(msg);
      location.href = 'admin/fm?f='+dir_path
    })
  }
}
function deletefile(path) {
  if(confirm("Are you sure you want to remove this file?")) {
    g.loader()
    $.post('fm/delete', {path:path},function(msg){
      g.loader(false)
      location.href = 'admin/fm?f='+dir_path
    })
  }
}
</script>

  </div>

</div>
