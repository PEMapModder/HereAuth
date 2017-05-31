<?php

if(!getenv("TRAVIS")){
	echo "Only run this script on Travis\n";
	exit(1);
}
$target = $argv[1];
if(!isset($argv[1])){
	echo /** @lang text */
	"Usage: php $argv[0] <path to download into>\n";
}
list($owner, $repo) = explode("/", getenv("TRAVIS_REPO_SLUG"), 2);
$sha = getenv("TRAVIS_COMMIT");
for($i = 1; true; $i++){
	echo "Attempting to download CI build from Poggit (trial #$i)\n";
	$json = shell_exec("curl " . escapeshellarg("https://poggit.pmmp.io/ci.info?owner=$owner&repo=$repo&sha=$sha"));
	$data = json_decode($json);
	if($data === null){
		var_dump($json);
		exit(1);
	}
	if(count($data) === 0){
		sleep(5);
		echo "[*] Waiting for Poggit builds...\n";
		continue;
	}
	foreach($data as $datum){
		shell_exec("wget -O " . escapeshellarg($target) . " " . escapeshellarg("https://poggit.pmmp.io/r/" . $datum->resourceId));
	}
	exit(0);
}
