<?php
$config = parse_ini_file('config.ini',true);
define("IS_CLI_CALL",( strcmp(php_sapi_name(),'cli') == 0 ));
define("NEW_LINE", IS_CLI_CALL?"\n":"<BR>");
define("TARGET_ROOT", preg_replace('/\/$/','',$config['SOURCE']));

//////////////////////////// Minify Javascript/Css File using YUI Compressor /////////////////////////////////////
$arrFile = $config['MINIFY']['FILE'];
if(is_file('debug.txt'))unlink('debug.txt');
foreach($arrFile as $filePath){		
	if($filePath=="")continue;
	echo IS_CLI_CALL?"\n":"<HR>";
	$path	= trim(TARGET_ROOT.'/'.$filePath);	
	if(is_file($path)){		
		minifyFile($path);
	}else if(is_dir($path)){
		minifyDir($path);
	}else{
		echo "Can not access to $path ".NEW_LINE;
		continue;
	}	
}

function minifyFile($path,$path_dir=''){
	echo "- Minify $path" . NEW_LINE;	
	$info 			= pathinfo($path);	
	$fileName 		= $info['filename'];	
	$baseName 		= $info['basename'];	
	$fileExtension  = $info['extension'];
	if($path_dir==''){
		$targetDir		= $info['dirname'];		
		$newFilePath 	= $targetDir."/".$fileName."_mnf.$fileExtension";
	}else{
		$targetDir = $path_dir . "_mnf";
		$newFilePath 	= $targetDir."/".$baseName;
	}
	if(in_array($fileExtension,array('css','js'))){			
		$command = "java -jar yuicompressor-2.4.6.jar --type $fileExtension -v -o $newFilePath $path";
		$out = null; $err=null;
		cmd_exec($command,$out,$err);
		//Print the debug file:
		cmdOut("\n\n-------------------------------------- Minify $path-------------------------------------- \n\n");
		cmdOut(print_r($err,true));		
	}	
}

function cmd_exec($cmd, &$stdout, &$stderr){
    $outfile = tempnam(".", "cmd");
    $errfile = tempnam(".", "cmd");
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("file", $outfile, "w"),
        2 => array("file", $errfile, "w")
    );
    $proc = proc_open($cmd, $descriptorspec, $pipes);   
    if (!is_resource($proc)) return 255;
    fclose($pipes[0]);    //Don't really want to give any input
    $exit = proc_close($proc);   
	$stderr = file_get_contents($errfile);
    unlink($outfile);
    unlink($errfile);
    return $exit;
}

//for css & js folders:
function minifyDir($path_dir){
	echo "+ Minify Directory $path_dir" . NEW_LINE;
	$dirContent = getDirContent($path_dir);
	foreach($dirContent as $fileName){
		$fullPath = $path_dir."/".$fileName;
		$info = pathinfo($fullPath);
		if(!in_array($info['extension'],array('js','css','xml'))) continue;
		minifyFile($fullPath,$path_dir);		
	}
	echo NEW_LINE. NEW_LINE;
}

function getDirContent($dir){
	$outPut = array();
	$handle = opendir($dir);
	if(!$handle)return $outPut;
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..")
		array_push($outPut,$file);
	}
	closedir($handle);
	return $outPut;
}

function cmdOut($content){
	return write($content,'debug.txt','a');
}

function write($content,$filename='debug.txt',$mode='w'){
	if (!$handle = @fopen($filename, $mode)) 
	return 3;    
	// Write content to our opened file. return 2 if can not write.
    if (@fwrite($handle, $content) === FALSE) {
		@fclose($handle);
        return 2;
    }	    
    @fclose($handle);
	return true;
}

//////////////////////////// Combine Javascript File /////////////////////////////////////
foreach($config as $key => $value){
	if($key=='MINIFY')continue;
	if(is_array($value)) combineJs($config[$key]);
}

function combineJs($source){
	if(!is_array($source) || count($source)==0)return;
	$targetPath  = TARGET_ROOT . '/'. $source['TARGET'];
	$sourceFiles = $source['FILE'];

	echo "+ Combine $targetPath:" . NEW_LINE;

	//Check if can create combine version
	if(is_file($targetPath)){
		if(!unlink($targetPath)) {
			echo 'Can not delete the previous existed combined file: '. $targetPath . NEW_LINE;
			return;
		}
	}else{
		$pathInfo = pathinfo($targetPath);
		if(!is_writable($pathInfo['dirname'])){
			echo 'Can not create file '. $targetPath . NEW_LINE;
			return;
		}
	}

	//Combine all js files in $sourceFiles:
	foreach($sourceFiles as $filePath){
		$filePath = TARGET_ROOT.'/'.$filePath;
		//Check if can access to source file
		if(!is_file($filePath)){
			echo "Can not access $filePath". NEW_LINE;
			continue;
		}
		echo '- source: ' . $filePath . NEW_LINE;
		$result = @file_put_contents($targetPath, file_get_contents($filePath), FILE_APPEND);
	}
	echo NEW_LINE. NEW_LINE;
}

echo NEW_LINE ."DONE. Please check debug.txt to see warning & info.";
?>