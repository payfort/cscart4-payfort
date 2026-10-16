<?php 
namespace AmazonPaymentServices;

class Logger{

	private $enable = false;
	private $log_dir;
	private $extension = 'log';
	
	public function __construct($enable = false,$log_dir = null){
		$this->enable = ($enable === true || $enable === 'Y');
		$this->log_dir = !empty($log_dir) ? $log_dir : DIR_ROOT.'/var/amazon-payment-services/';
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
	
		if (!$this->enable) {
			return false;
		}

		// Do not serialize arbitrary objects or retain unstructured payment text.
		$data = json_encode($this->sanitizeData($data));
		if ($data === false) {
			$data = '"[REDACTED]"';
		}
		if (!is_dir($this->log_dir) && !mkdir($this->log_dir, 0750, true) && !is_dir($this->log_dir)) {
			return false;
		}

		$events = [
			'Notify Response', 'Signature Verification Failed', 'Amount Mismatch',
			'Tokenization - Request', 'Tokenization - Response', 'Form Request',
			'Request', 'Response', 'Purchase Plan - Request', 'Purchase Plan - Response',
			'Get Plans - Request', 'Get Plans - Response', 'Token',
			'Verify Merchant - Request', 'Verify Merchant - Response',
			'Purchase Request', 'Purchase Response', 'OTP Verify - Request',
			'OTP Verify - Response', 'Phone Verify - Request', 'Phone Verify - Response',
			'Purchase - Request', 'Purchase - Response', 'OTP Generate - Request',
			'OTP Generate - Response',
		];
		$info = in_array($info, $events, true) ? $info : '[REDACTED EVENT]';
		$type = in_array($type, ['cc', 'installments', 'apple', 'visa', 'valu', 'knet', 'naps', 'tabby'], true) ? $type : '';

		$file = $this->log_dir.date("Y-m-d").'.'.$this->extension;

		$txt = date("Y-m-d")." @ ".date("H:i:s");

		if( !empty($type) )
			$txt .= " - ".strtoupper($type);

		$txt .= " - ".trim($info).":\n".$data."\n";
		
		return file_put_contents($file, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
	}

	private function sanitizeData($data, $depth = 0){
		if ($depth >= 8) {
			return '[REDACTED]';
		}
		if (is_string($data)) {
			$decoded = json_decode($data, true, 16);
			return is_array($decoded) ? $this->sanitizeData($decoded, $depth + 1) : '[REDACTED]';
		}
		if (!is_array($data)) {
			return '[REDACTED]';
		}

		$result = [];
		foreach ($data as $key => $value) {
			// Only known containers and diagnostics survive. Unknown keys themselves
			// can contain secrets, so omit them rather than copying their names.
			$name = strtolower(str_replace(' ', '_', (string)$key));
			if (in_array($name, ['params', 'response', 'request', 'data'], true) || is_int($key)) {
				$result[$key] = $this->sanitizeData($value, $depth + 1);
			} elseif (in_array($name, ['amount', 'captured_amount', 'refunded_amount', 'order', 'order_id', 'status', 'response_code'], true)
				&& is_scalar($value) && preg_match('/\A[0-9]{1,12}(?:\.[0-9]{1,3})?\z/', (string)$value)) {
				$result[$key] = $value;
			} elseif ($name === 'currency' && is_string($value) && preg_match('/\A[A-Z]{3}\z/', $value)) {
				$result[$key] = $value;
			} elseif ($name === 'mode' && in_array($value, ['return', 'notify'], true)) {
				$result[$key] = $value;
			} elseif (in_array($name, ['command', 'service_command'], true)
				&& in_array($value, ['PURCHASE', 'AUTHORIZATION', 'CAPTURE', 'REFUND', 'VOID_AUTHORIZATION', 'TOKENIZATION', 'UPDATE_TOKEN', 'CHECK_STATUS'], true)) {
				$result[$key] = $value;
			} else {
				$result['redacted'] = '[REDACTED]';
			}
		}
		return $result;
	}

}
