<?php
namespace alanmanderson\clever_deploy\Connectors;

use alanmanderson\clever_deploy\Result;

class BitbucketConnector implements RepositoryConnectorInterface{
    private $localRoot;
    private $branch;
    private $payload;
    private $event;
    private $acceptedEvents;
    
    public function __construct($localRoot, $branch) {
        $this->localRoot = $localRoot;
        $this->branch = $branch;
        $this->payload = file_get_contents('php://input');
        $headers = getallheaders();
        $headers = array_change_key_case($headers);
        $this->event = $headers['x-event-key'];
        $this->acceptedEvents = ['repo:push'];
    }
    
    public function deploy(array $cmds = []) {
        header('Content-Type: application/json');
        $result = new Result();
        if (!$this->verify()) {
            $result->catchException(new \Exception('Could not verify request', 403));
            return $result;
        }
        
        if (!in_array($this->event, $this->acceptedEvents)) {
            $result->catchException(new \Exception('Invalid event', 400));
            return $result;
        }
        
        $data = json_decode($this->payload);
        if (empty($data)) {
            $result->catchException(new \Exception('Invalid request', 400));
            return $result;
        }
        
        $relevantChanges = false;
        foreach($data->data->push->changes as $change){
            if (isset($change->new->name) && $change->new->name == $this->branch){
                $relevantChanges = true;
                break;
            }
        }
        if ($relevantChanges) {
            $output = array();
            if (file_exists($this->localRoot)) {
                array_unshift(
                    $cmds,
                    "cd {$this->localRoot}",
                    "git fetch origin {$this->branch}",
                    "git merge origin/{$this->branch} --no-edit"
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
    
    public function verify(){
        return in_array($_SERVER['REMOTE_ADDR'], [
            '104.192.143.0',
            '104.192.143.24',
            '34.198.203.127',
            '34.198.178.64',
            '34.198.32.85'
        ]);
        return false;
    }
}