<?php
//Master Render Node
//Tucker Osman, 2016
//Framework for a parallel rendering structure using grid computing
//This is the master node and is responsible for delegating all tasks

//Step 1: Parse the descriptor file and make sure everything exists
$descriptionfile = json_decode(file_get_contents($argv[1]),true);
$nodelist = array_filter(explode("\n",file_get_contents($argv[2])));
$length = 0;
$fps = 30;

foreach($descriptionfile as $key=>$value) {
	if(!file_exists($key)) die("File $key doesn't exist!");
	if(isset($value["motion"])) {
		foreach($value["motion"] as $motionelement) {
			if($motionelement["et"]>$length) $length = $motionelement["et"];
		}
	}
}

//Step 2: Decide how to split up the work
$frames = $length * $fps; //Frames in final product
$delegateframerange = $frames/count($nodelist); //Frames each node will be responsible for
$jobid = substr(md5(microtime()),-6); //This job id so we can keep frames unique

//Step 3: Send the assets and the job to the nodes
echo "Submitting jobs to nodes in list...\n";
$i = 0;
$file = fopen($jobid."_fragmentqueue","w");
foreach($nodelist as $node) {
	$post = array( //Job description
		"jobdescription" => json_encode($descriptionfile),
		"framecount"=>$delegateframerange,
		"framestart"=>$delegateframerange*$i,
		"jobid"=>$jobid,
		"callback"=>"localhost/imgtest/masternodeinterface.php"
	);
	foreach($descriptionfile as $key=>$value) $post[str_replace(".","",$key)] = new CURLFile(realpath($key),"application/octet-stream",basename($key)); //Send all of the resources

 	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$node."/rendernodeinterface.php");
	curl_setopt($ch, CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_SAFE_UPLOAD, 0);
	$result = curl_exec($ch); //Post this to the node
	curl_close($ch);
	fputs($file,"file '".$jobid."_videofragment_".($delegateframerange*$i).".mp4'\n");
	$i++;
}
echo "Jobs submitted. Waiting for all frames to be rendered...\n";
//Here we wait until we've recieved all of the frames
while(count(glob($jobid."_videofragment_*"))!=count($nodelist)) {
	sleep(2);
	//echo count(glob($jobid."_videofragment_*"))." to ".count($nodelist)."\n";
}

echo "All fragments have been recieved, building video...";
//Invoke FFMPEG
shell_exec("ffmpeg -f concat -i ".$jobid."_fragmentqueue -c copy ".$jobid."_output.mp4 >/dev/null 2>/dev/null");
shell_exec("rm ".$jobid."_videofragment_*");
unlink($jobid."_fragmentqueue");
echo "All tasks for job $jobid are complete!\n\n";
?>
