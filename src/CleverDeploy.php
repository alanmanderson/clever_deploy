<?php
namespace alanmanderson\clever_deploy;
/**
  * The below script was forked from https://gist.github.com/marcelosomers/8305065 into
  * https://gist.github.com/alanmanderson/2b79c0e724eb5e143701 and modified.  The original code is from marcelosomers

  * This script is for easily deploying updates to Github repos to your local server. It will automatically git clone or
  * git pull in your repo directory every time an update is pushed to your $BRANCH (configured below).
  *
  * Read more about how to use this script at http://behindcompanies.com/2014/01/a-simple-script-for-deploying-code-with-githubs-webhooks/
  *
  * INSTRUCTIONS:
  * 1. Edit the variables below
  * 2. Upload this script to your server somewhere it can be publicly accessed
  * 3. Make sure the apache user owns this script (e.g., sudo chown www-data:www-data webhook.php)
  * 4. (optional) If the repo already exists on the server, make sure the same apache user from step 3 also owns that
  *    directory (i.e., sudo chown -R www-data:www-data)
  * 5. Go into your Github Repo > Settings > Service Hooks > WebHook URLs and add the public URL
  *    (e.g., http://example.com/webhook.php)
  *
**/
class CleverDeploy{

    private $localRoot;
    private $localRepoName;
    private $remoteRepo;
    private $branch;
    private $requestSignature;
    private $repoSecret;
    private $payload;
    private $event;
    private $acceptedEvents;
    public $logFile;

    public function __construct($localRoot,
                                $localRepoName,
                                $remoteRepo,
                                $branch,
                                $repoSecret = null){
        $this->localRoot = $localRoot;
        $this->localRepoName = $localRepoName;
        $this->remoteRepo = $remoteRepo;
        $this->branch = $branch;
        $this->repoSecret = $repoSecret;
        $this->acceptedEvents = ['push'];
        $this->payload = file_get_contents('php://input');
        $headers = getallheaders();
        $this->event = $headers['X-GitHub-Event'];
        $this->requestSignature = $headers['X-Hub-Signature'];
        $dir = __DIR__."/log/";
        $success = file_exists($dir) || mkdir(__DIR__."/log/", 0700);
        $this->logFile = __DIR__."/log/".time().".log";
    }

    public function deploy(){
        $result = array();
        $result['success'] = false;
        $result['deployed'] = false;
        if (!$this->verifySecret($this->requestSignature, $this->payload)){
            http_response_code(403);
            $result['error'] = "invalid secret";
            echo json_decode($result);
            exit();
        }
        
        if (! in_array($this->event, $this->acceptedEvents)){
            http_response_code(400);
        }

        $data = json_decode($this->payload);
        if (empty($data)){
            $result['error'] = "Payload could not be decoded: ".$this->payload;
            http_response_code(400);
            echo json_encode($result);
            exit(0);
        }
        
        if (isset($this->logFile)){
            $file = fopen($this->logFile, 'w');
            $strData = print_r($data, true);
            fwrite($file,$strData);
            fclose($file);
        }
        
        if ($data->ref == "refs/heads/{$this->branch}") {
            $output = array();
            if( file_exists($this->localRoot) ) {
            // If there is already a repo, just run a git pull to grab the latest changes
                $cmd = "cd {$this->localRoot}";
                $output[] = $cmd." ".shell_exec($cmd);
                $cmd = "git pull origin {$this->branch}";
                //$output[] = `git pull origin staging`;
                $output[] .= $cmd." ".shell_exec($cmd);
                //$output[] .= shell_exec("git log");
                $result['output'] = $output;
                $result['deployed'] = true;
                $result['success'] = true;
                $result['endTime'] = time();
            }
        }
        return json_encode($result);
    }
    
    public function addAcceptedEvent($event){
        $this->acceptedEvents[] = $event;
    }
    
    private function verifySecret($signature, $payload){
        // Split signature into algorithm and hash
        list($algo, $hash) = explode('=', $signature, 2);
        $payloadHash = hash_hmac($algo, $payload, $this->repoSecret);
        return $hash === $payloadHash;
    }
}