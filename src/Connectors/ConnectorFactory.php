<?php
namespace alanmanderson\clever_deploy\Connectors;

class ConnectorFactory{
	const GITHUB_CONNECTOR = "github";
	const BITBUCKET_CONNECTOR = "bitbucket";
	static function getConnector($type, $projectRoot, $branch, $repoSecret = null){
		switch($type){
			case self::GITHUB_CONNECTOR:
				return new GithubConnector($projectRoot, $branch, $repoSecret);
			case self::BITBUCKET_CONNECTOR:
				return new BitBucketConnector($projectRoot, $branch);
			default:
				throw new \Exception('Unknown connector');
		}
	}
}