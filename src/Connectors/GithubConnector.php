<?php
namespace alanmanderson\clever_deploy\Connectors;

use alanmanderson\clever_deploy\Result;

class GithubConnector implements RepositoryConnectorInterface{
	private $localRoot;
	private $branch;
	private $signature;
	private $repoSecret;
	private $payload;
	private $event;
	private $acceptedEvents;
	
	public function __construct($localRoot, $branch, $repoSecret = null) {
		$this->localRoot = $localRoot;
		$this->branch = $branch;
		$this->repoSecret = $repoSecret;
		$this->acceptedEvents = ['push'];
		$this->payload = file_get_contents('php://input');
		$headers = getallheaders();
		$headers = array_change_key_case($headers);
		$this->event = $headers['x-github-event'];
		$this->signature = $headers['x-hub-signature'];
	}
	
	public function deploy(array $cmds = []) {
		$result = new Result();
		header('Content-Type: application/json');
		if (!$this->verify()) {
			$result->catchException(new \Exception('invalid secret', 403));
		}
		
		if (!in_array($this->event, $this->acceptedEvents)) {
			$result->catchException(new \Exception('invalid event', 400));
			return $result;
		}
		
		$data = json_decode($this->payload);
		if (empty($data)) {
			$result->catchException(new \Exception('Payload could not be decoded', 400));
			return $result;
		}
		
		if ($data->ref == "refs/heads/{$this->branch}") {
			$output = array();
			if (file_exists($this->localRoot)) {
				chdir($this->localRoot);
				// If there is already a repo, just run a git pull to grab the latest changes
				array_unshift(
						$cmds,
						"git fetch origin {$this->branch}",
						"git merge origin/{$this->branch} --no-edit",
						"git log - 3"
								);
				foreach ($cmds as $cmd) {
					$output[] = $cmd . " " . shell_exec($cmd);
				}
				$result->setOutput($output);
				$result->setWasDeployed(true);
			}
		}
		return $result;
	}
	
	public function addAcceptedEvent($event) {
		$this->acceptedEvents[] = $event;
	}
	
	public function verify(){
		if ($this->repoSecret == null) return true;
		// Split signature into algorithm and hash
		list ($algo, $hash) = explode('=', $this->signature, 2);
		$payloadHash = hash_hmac($algo, $this->payload, $this->repoSecret);
		return $hash === $payloadHash;
	}
}
