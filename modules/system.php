<?php
function hd_module_escape($value){$db=$GLOBALS['_lynxhd_mysql_connection']??null;return $db?mysqli_real_escape_string($db,(string)$value):addslashes((string)$value);}
function hd_modules_registry_ready(){global $pre;return mysql_query("CREATE TABLE IF NOT EXISTS {$pre}module (id INT UNSIGNED NOT NULL AUTO_INCREMENT,module_dir VARCHAR(80) NOT NULL,module_name VARCHAR(150) NOT NULL,version VARCHAR(30) NOT NULL DEFAULT '',author VARCHAR(150) NOT NULL DEFAULT '',description TEXT,installed TINYINT(1) NOT NULL DEFAULT 0,enabled TINYINT(1) NOT NULL DEFAULT 0,updated_at INT UNSIGNED NOT NULL,PRIMARY KEY(id),UNIQUE KEY module_dir(module_dir)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
function hd_module_manifest($dir){if(!preg_match('/^[a-z0-9_-]+$/',$dir))return false;$file=__DIR__."/$dir/module.json";if(!is_file($file))return false;$data=json_decode(file_get_contents($file),true);if(!is_array($data))return false;$data['dir']=$dir;return $data;}
function hd_modules_available(){ $items=array();foreach(scandir(__DIR__) as $dir){$manifest=hd_module_manifest($dir);if($manifest)$items[$dir]=$manifest;}ksort($items);return $items; }
function hd_module_load($dir){$manifest=hd_module_manifest($dir);if(!$manifest)return false;$file=__DIR__."/$dir/bootstrap.php";if(!is_file($file))return false;require_once $file;return $manifest;}
function hd_module_sync($dir,$installed,$enabled){global $pre;$m=hd_module_manifest($dir);if(!$m||!hd_modules_registry_ready())return false;$d=hd_module_escape($dir);$name=hd_module_escape($m['name']??$dir);$version=hd_module_escape($m['version']??'');$author=hd_module_escape($m['author']??'');$description=hd_module_escape($m['description']??'');$now=time();if(get_row_count("SELECT COUNT(*) FROM {$pre}module WHERE module_dir='$d'"))return mysql_query("UPDATE {$pre}module SET module_name='$name',version='$version',author='$author',description='$description',installed=".(int)$installed.",enabled=".(int)$enabled.",updated_at=$now WHERE module_dir='$d'");return mysql_query("INSERT INTO {$pre}module(module_dir,module_name,version,author,description,installed,enabled,updated_at) VALUES('$d','$name','$version','$author','$description',".(int)$installed.",".(int)$enabled.",$now)");}
function hd_module_state($dir){global $pre;if(!hd_modules_registry_ready())return array('installed'=>false,'enabled'=>false);$d=hd_module_escape($dir);$r=mysql_query("SELECT installed,enabled FROM {$pre}module WHERE module_dir='$d' LIMIT 1");$row=$r?mysql_fetch_array($r,MYSQLI_ASSOC):false;return array('installed'=>$row&&(bool)$row['installed'],'enabled'=>$row&&(bool)$row['enabled']);}
function hd_module_action($dir,$action){$m=hd_module_load($dir);if(!$m)return false;$prefix=str_replace('-','_',$dir);$fn=$prefix.'_'.$action;if(!function_exists($fn))return false;$ok=(bool)$fn();if(!$ok)return false;if($action==='install'){ $enable=$prefix.'_enable';if(function_exists($enable)&&!$enable())return false;return hd_module_sync($dir,true,true);}if($action==='uninstall')return hd_module_sync($dir,false,false);if($action==='enable')return hd_module_sync($dir,true,true);if($action==='disable')return hd_module_sync($dir,true,false);return true;}
function hd_module_enabled($dir){$state=hd_module_state($dir);return $state['installed']&&$state['enabled'];}

function hd_module_remove_tree($path){if(!is_dir($path))return;foreach(scandir($path) as $item){if($item==='.'||$item==='..')continue;$target=$path.'/'.$item;if(is_dir($target)&&!is_link($target))hd_module_remove_tree($target);else @unlink($target);}@rmdir($path);}
function hd_module_upload_zip($upload){
    $fail=function($message){return array('ok'=>false,'message'=>$message);};
    if(!is_array($upload)||($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)return $fail('Select a valid module ZIP file.');
    if((int)($upload['size']??0)<1||(int)$upload['size']>10*1024*1024)return $fail('Module ZIP files must be smaller than 10 MB.');
    $name=(string)($upload['name']??'');if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='zip')return $fail('Only ZIP module packages are accepted.');
    $tmp=(string)$upload['tmp_name'];$entries=array();
    if(class_exists('ZipArchive')){
        $zip=new ZipArchive();if($zip->open($tmp)!==true)return $fail('The uploaded ZIP file could not be opened.');
        for($i=0;$i<$zip->numFiles;$i++){ $stat=$zip->statIndex($i);$entry=(string)($stat['name']??'');if(substr($entry,-1)==='/')continue;$entries[$entry]=$zip->getFromIndex($i); }
        $zip->close();
    }elseif(class_exists('PharData')){
        try{$phar=new PharData($tmp);$prefix='phar://'.$tmp.'/';$iterator=new RecursiveIteratorIterator($phar);foreach($iterator as $file){if($file->isDir()||$file->isLink())continue;$path=$file->getPathname();$entry=strpos($path,$prefix)===0?substr($path,strlen($prefix)):$file->getFilename();$entries[$entry]=file_get_contents($path);}}catch(Exception $e){return $fail('The uploaded ZIP file could not be opened.');}
    }else return $fail('ZIP support is not available on this server.');
    if(!$entries)return $fail('The module ZIP is empty.');
    $clean=array();foreach($entries as $entry=>$content){$entry=str_replace('\\','/',$entry);if($entry===''||$entry[0]==='/'||strpos($entry,"\0")!==false||preg_match('#(^|/)\.\.(/|$)#',$entry))return $fail('The ZIP contains an unsafe file path.');if(strpos($entry,'__MACOSX/')===0||basename($entry)==='.DS_Store')continue;$clean[$entry]=$content;}
    $manifests=array_values(array_filter(array_keys($clean),function($entry){return basename($entry)==='module.json';}));if(count($manifests)!==1)return $fail('A module ZIP must contain exactly one module.json file.');
    $manifest_path=$manifests[0];$root=dirname($manifest_path);if($root==='.')$root='';$manifest=json_decode($clean[$manifest_path],true);if(!is_array($manifest))return $fail('module.json is not valid JSON.');
    $dir=$root!==''?basename($root):(string)($manifest['slug']??'');if(!preg_match('/^[a-z0-9_-]+$/',$dir))return $fail('The module directory name is invalid.');
    $bootstrap=($root!==''?$root.'/':'').'bootstrap.php';if(!isset($clean[$bootstrap]))return $fail('The module package is missing bootstrap.php.');
    $target=__DIR__.'/'.$dir;if(file_exists($target))return $fail('A module named '.$dir.' already exists. Remove it before uploading this package.');
    $stage=__DIR__.'/.upload-'.bin2hex(random_bytes(8));if(!mkdir($stage,0755,true))return $fail('The module staging directory could not be created.');
    foreach($clean as $entry=>$content){if($root!==''&&strpos($entry,$root.'/')!==0){hd_module_remove_tree($stage);return $fail('All package files must be inside the module directory.');}$relative=$root!==''?substr($entry,strlen($root)+1):$entry;if($relative===''||substr($relative,-1)==='/')continue;$destination=$stage.'/'.$relative;$parent=dirname($destination);if(!is_dir($parent)&&!mkdir($parent,0755,true)){hd_module_remove_tree($stage);return $fail('A module directory could not be created.');}if(file_put_contents($destination,$content)===false){hd_module_remove_tree($stage);return $fail('A module file could not be written.');}}
    if(!rename($stage,$target)){hd_module_remove_tree($stage);return $fail('The module could not be moved into place.');}
    return array('ok'=>true,'message'=>($manifest['name']??$dir).' was uploaded and is ready to install.','dir'=>$dir);
}
