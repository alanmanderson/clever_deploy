<?php
namespace alanmanderson\clever_deploy;

use alanmanderson\clever_deploy\Connectors\ConnectorFactory;

/**
 * The below script was forked from https://gist.github.com/marcelosomers/8305065 into
 * https://gist.github.com/alanmanderson/2b79c0e724eb5e143701 and modified.
 * The original code is from marcelosomers
 *
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
 * directory (i.e., sudo chown -R www-data:www-data)
 * 5. Go into your Github Repo > Settings > Service Hooks > WebHook URLs and add the public URL
 * (e.g., http://example.com/webhook.php)
 */
class CleverDeploy {
    public $connector;

    public function __construct($connectorType, $projectRoot, $branch, $repoSecret = null) {
    	$this->connector = ConnectorFactory::getConnector($connectorType, $projectRoot, $branch, $repoSecret);
    }

    public function deploy($cmds = array()) {
    	$result = $this->connector->deploy($cmds);
    	header('Content-Type: application/json');
        http_response_code($result->statusCode);
        echo json_encode($result);
    }
}
