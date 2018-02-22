<?php
require_once 'Milo/github-api.php';
use Milo\Github; //https://github.com/milo/github-api/

header('Content-Type: application/json');

$api = new Github\Api;
$token = new Milo\Github\OAuth\Token('your secret key here');
$api->setToken($token);

$githubData = [
	'repository' => [],
	'user' => [],
	'contributors' => []
];
$repositorySearchResponse= $api->get('/search/repositories', ['q' => 'language:swift', 'per_page' => 5, 'sort' => 'stars', 'order'=>'desc']);
$repositorySearchData= $api->decode($repositorySearchResponse);

foreach($repositorySearchData->items as $r) {
	$fullRepoResponse = $api->get('/repos/:owner/:repo', ['owner' => $r->owner->login, 'repo' => $r->name]);
	$fullRepoData = $api->decode($fullRepoResponse);
	
	$repo = [
		'id' => $r->id,
		'name' => $r->name,
		'full_name' => $r->full_name,
		'description' => $r->description,
		'html_url' => $r->html_url,
		'star_count' => $r->stargazers_count,
		'fork_count' => $r->forks,
		'watchers_count' => $fullRepoData->subscribers_count,
		'owner_id' => $r->owner->id
	];

	$owner = $r->owner;
	$user = [
		'login' => $owner->login,
		'id' => $owner->id,
		'avatar_url' => $owner->avatar_url,
		'url' => $owner->url,
		'html_url' => $owner->html_url,
	];
	$githubData['repository'][] = $repo;
	$githubData['user'][$owner->id] = $user;
	
	$contributorsResponse = $api->get('/repos/:owner/:repo/contributors', ['owner' => $r->owner->login, 'repo' => $r->name]);	
	$contributorsData = $api->decode($contributorsResponse);
	
	foreach($contributorsData as $c) {
		$contributor = [
			'login' => $c->login,
			'id' => $c->id,
			'avatar_url' => $c->avatar_url,
			'url' => $c->url,
			'html_url' => $c->html_url,
		];
		
		if(!isset($githubData['user'][$c->id]))$githubData['user'][$c->id] = $contributor;
		$githubData['contributors'][] = [
			'user_id' => $c->id,
			'repo_id' => $r->id,
			'contributions' => $c->contributions
		];
		
	}
}

echo json_encode($githubData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);