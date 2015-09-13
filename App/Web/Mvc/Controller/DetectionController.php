<?php
namespace App\Web\Mvc\Controller;
class DetectionController extends \Core\Mvc\Controller{
	protected function indexAction(){
		header('Content-Type:text/html;chasrset=utf-8');
		echo "==================PHP 非法控制器==================<br />";
		$this->loopdir(__DIR__);
		echo "<br />===================JS 非法地址====================<br />";
		$this->loopjsdir(ROOT_DIR.'entrance/web/js');
		echo "<br />===================JS Jquery非法选择器====================<br />";
		$this->loopjstagdir(ROOT_DIR.'entrance/web/js/application_Js');
	}
	protected function loopdir($dir){
		foreach(glob($dir.'/*') as $filename){
			if(is_dir($filename)){
				$this->loopdir($filename);
			}else{
				$_filename = str_replace('/','\\',substr($filename,strlen(realpath(__DIR__.'/../../../..')),-4));
				$checkfilename = substr($_filename, 0,0-strlen('Controller'));
				if(preg_match('#[a-z][A-Z]#', $checkfilename)){//类名称不符合要求
					$word = preg_replace('#([a-z][A-Z])#', '<span style="color:red;">$1</span>', substr($filename, 0,0-strlen('Controller.php'))).'Controller.php';
					echo "<p style='color:rgb(1, 142, 88);'>{$word}</p>";
				}
				//判断类方法
				if(!\Core\Autoload::isExists($_filename)){
					continue;
				}
				$ref = new \ReflectionClass($_filename);
				$methods = $ref->getMethods();
				$cmethods = array();
				foreach ($methods as $method){
					$name = $method->getName();
					if(preg_match('#Action$#', $name)){
						$_name = substr($name, 0,0-strlen('Action'));
						if(preg_match('#[A-Z]#', $_name)){//类名称不符合要求
							$cmethods[] = $name;
						}
					}
				}
				if(!empty($cmethods)){
					if(!preg_match('#[a-z][A-Z]#', $checkfilename)){
						$word = substr($checkfilename,strlen(__NAMESPACE__.'\\')+1);
						echo "<p>{$word}</p>";
					}
					foreach ($cmethods as $method){
						$method = preg_replace('#([A-Z])#', '<span style="color:red;">$1</span>', substr($method, 0,0-strlen('Action'))).'Action';
						echo "<p style='padding-left:30px;'>{$method}</p>";
					}
				}
			}
		}
	}
	protected function loopjsdir($dir){
		foreach(glob($dir.'/*') as $filename){
			if(is_dir($filename)){
				$this->loopjsdir($filename);
			}elseif(substr($filename,-3) == '.js'){//JS标签文件
				$fs = @fopen($filename, 'r');
				if(!$fs){
					echo "<p style='color:red;font-weight:bold;'>".$filename.' 文件打开失败</p>';
					continue;
				}
				$lines = array();
				$index = 1;
				while (!feof($fs)){
					$line = fgets($fs);
					if(strpos($line, 'index.php') !== false){//有非法地址
						$lines[$index] = $line;
					}
					$index++;
				}
				if(!empty($lines)){
					echo $filename.'<br />';
					foreach ($lines as $index => $line){
						$line = str_replace('index.php', '<span style="color:red;">index.php</span>', htmlentities($line));
						echo "<p style='padding-left:30px;'>行:{$index}<span style='padding-left:40px;'>{$line}</span></p>";
					}
				}
				fclose($fs);
			}
		}
	}
	protected function loopjstagdir($dir){
		foreach(glob($dir.'/*') as $filename){
			if(is_dir($filename)){
				$this->loopjstagdir($filename);
			}elseif(substr($filename,-3) == '.js'){//JS文件
				$fs = @fopen($filename, 'r');
				if(!$fs){
					echo "<p style='color:red;font-weight:bold;'>".$filename.' 文件打开失败</p>';
					continue;
				}
				$lines = array();
				$index = 1;
				while (!feof($fs)){
					$line = fgets($fs);
					$match_list = array();
					if(preg_match_all('#\$(\([\'|"][^\)]+\))#', $line,$match_list)){
						foreach ($match_list[0] as $match){
							if(strpos($match, ',$$') === false){
								$lines[$index] = $line;
							}
						}
					}
					$index++;
				}
				if(!empty($lines)){
					echo $filename.'<br />';
					foreach ($lines as $index => $line){
						$line =	htmlentities(preg_replace('#(\$\([\'|"][^\)]+\))#', ':COLORE-BEGIN:$1:COLORE-END:',$line));
						$line = str_replace(':COLORE-BEGIN:', '<span style="color:red;">', $line);
						$line = str_replace(':COLORE-END:', '</span>', $line);
						echo "<p style='padding-left:30px;'>行:{$index}<span style='padding-left:40px;'>{$line}</span></p>";
					}
				}
				fclose($fs);
			}
		}
	}
}