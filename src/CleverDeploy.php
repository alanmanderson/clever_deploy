<?php
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

$headers = getallheaders();
$hubSignature = $headers['X-Hub-Signature'];
$payload = file_get_contents('php://input');
$jsonPayload = json_decode($payload);
if (!verifySecret($hubSignature, $payload)){
  http_response_code(403);
  die ("invalid secret");
}

$data = json_decode($payload);
$logFile = __DIR__."/../log/".time().".log";
$file = fopen($logFile, 'w');
$strData = print_r($data, true);
fwrite($file,$strData);
fclose($file);

#if (in_array($data['action'], ['closed', 'synchronize'])){

#}

$LOCAL_ROOT         = "/var/www/CompleteSolar";
$LOCAL_REPO_NAME    = "HelioTrack";
$LOCAL_REPO         = "{$LOCAL_ROOT}/{$LOCAL_REPO_NAME}";
$REMOTE_REPO        = "git@github.com:completesolar/HelioTrack.git";
$BRANCH             = "staging";
echo "Hello World!";
print_r($data->ref);
print ($data->ref == "refs/heads/2.0.4-test_setup1") ? "true" : "false";
if ($data) {
  // Only respond to POST requests from Github
  echo "payload detected";
  if ($data->ref == "refs/heads/staging") {
        echo "staging branch confirmed";
        if( file_exists($LOCAL_REPO) ) {
          // If there is already a repo, just run a git pull to grab the latest changes
          shell_exec("cd {$LOCAL_REPO} && git pull origin staging");
          echo shell_exec("git log");
          die("done " . time());
        }
  } else {
  }
} else {
  echo "no payload";
}

function verifySecret($hubSignature, $payload){
  $secret = 'to1Wv9AmC1pmHOe';
  // Split signature into algorithm and hash
  list($algo, $hash) = explode('=', $hubSignature, 2);
  $payloadHash = hash_hmac($algo, $payload, $secret);
  return $hash === $payloadHash;
}