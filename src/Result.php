<?php
namespace alanmanderson\clever_deploy;

class Result {
	public $statusCode = 200;
	public $message = "Success";
	public $wasDeployed = false;
	public $output = '';
	
	public function catchException(\Exception $e){
		$this->statusCode = $e->getCode();
		if ($this->statusCode < 300 || $this->statusCode > 599) $this->statusCode = 500;
		$this->message = $e->getMessage();
	}
	
	public function setWasDeployed($wasDeployed){
		$this->wasDeployed = $wasDeployed;
	}
	
	public function setOutput($output){
		$this->output = $output;
	}
	
	public function setMessage($message){
		$this->message = $message;
	}
}