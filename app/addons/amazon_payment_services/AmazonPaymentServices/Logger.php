<?php 
namespace AmazonPaymentServices;

class Logger{

	private $enable = false;
	private $log_dir;
	private $extension = 'log';
	
	public function __construct($enable = false,$log_dir = null){
		$this->enable = $enable;
		$this->log_dir = !empty($log_dir) ? $log_dir : DIR_ROOT.'/var/amazon-payment-services/';
		if( !is_dir($this->log_dir) )
			mkdir($this->log_dir,0777,true);
	}

	public function getFiles($limit= 30){
		$files = glob($this->log_dir."*.".$this->extension);
		
		usort($files, function($f1, $f2) {
		    return filemtime($f2) - filemtime($f1);
		});

		$files = array_map(function($f){ return pathinfo($f,PATHINFO_FILENAME); },$files);

		return array_slice($files,0,$limit);
	}

	public function readFile($name){

		$name = trim(str_replace(['\/','/','.','..'],'',$name));
		if (preg_match("/\d{4}\-\d{2}\-\d{2}/", $name) ){

			$file = $this->log_dir.$name.'.'.$this->extension; 
			if( file_exists($file) )
				return file_get_contents($file);
		
		}

		return false;
	}

	public function log($info,$data,$type = ''){
	
		$data = is_array($data) ? json_encode($data) : $data;

		$file = $this->log_dir.date("Y-m-d").'.'.$this->extension;

		$txt = date("Y-m-d")." @ ".date("H:i:s");

		if( !empty($type) )
			$txt .= " - ".strtoupper($type);

		$txt .= " - ".trim($info).":\n".$data."\n";
		
		return file_put_contents($file, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
	}
}
