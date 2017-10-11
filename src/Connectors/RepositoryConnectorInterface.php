<?php
namespace alanmanderson\clever_deploy\Connectors;

interface RepositoryConnectorInterface {
	public function deploy(array $cmds);
	public function verify();
}