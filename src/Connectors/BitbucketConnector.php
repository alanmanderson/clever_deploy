<?php
namespace alanmanderson\clever_deploy\Connectors;

use alanmanderson\clever_deploy\Result;

class BitbucketConnector implements RepositoryConnectorInterface{
    private $localRoot;
    private $branch;
    private $repoSecret;
    private $hookUuid;
    private $payload;
    private $event;
    private $acceptedEvents;
    
    public function __construct($localRoot, $branch, $repoSecret = null) {
        $this->localRoot = $localRoot;
        $this->branch = $branch;
        $this->repoSecret = $repoSecret;
        $this->payload = file_get_contents('php://input');
        $headers = getallheaders();
        $headers = array_change_key_case($headers);
        $this->hookUuid = $headers['x-hook-uuid'];
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
        foreach($data->push->changes as $change){
            if (isset($change->new->name) && $change->new->name == $this->branch){
                $relevantChanges = true;
                break;
            }
        }
        if ($relevantChanges) {
            $output = array();
            if (file_exists($this->localRoot)) {
                chdir($this->localRoot);
                array_unshift(
                    $cmds,
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
        if ($this->repoSecret == null) return true;
        return $this->repoSecret == $this->hookUuid;
    }
}
