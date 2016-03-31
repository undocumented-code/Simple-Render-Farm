<?php
//Render node
//Tucker Osman 2016
//Accepts a job and renders the given frames based on what's required.
$jobid = $argv[1];
$startframe = $argv[2];
$endframe = $argv[3];
$fps = 30;
$currentframe = $startframe;
$job = json_decode(file_get_contents($jobid),true);
while($currentframe<$endframe) { //For each frame
	$images = array();
	foreach($job as $file=>&$properties) $images[] = array("image"=>new \Imagick($file),"x"=>computeX($currentframe,$properties),"y"=>computeY($currentframe,$properties));
	$base = new Imagick();
	$base->newImage($images[0]["image"]->getImageWidth(),$images[0]["image"]->getImageHeight(),new ImagickPixel('black'));
	foreach($images as $image) {
		$base->compositeImage($image["image"], Imagick::COMPOSITE_DEFAULT,$image["x"],$image["y"]);
		$image["image"]->clear();
	}
	$base->writeImage($jobid."_frame_".$currentframe.".png");
	$base->clear();
	$currentframe++;
}

//Now that all of the frames are rendered, let's make a video!
shell_exec("ffmpeg -framerate ".$fps." -i ".$jobid."_frame_%d.png -c:v libx264 -r 30 -pix_fmt yuv420p ".$jobid."_videofragment_".$startframe.".mp4");
shell_exec("rm ".$jobid."_frame_*");
$post = array( //Job description
	"framestart"=>$startframe,
	"jobid"=>$jobid,
	"videofragment"=>new CURLFile(realpath($jobid."_videofragment_".$startframe.".mp4"))
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,"http://".file_get_contents("callback"));
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_SAFE_UPLOAD, 0);
$result = curl_exec($ch); //Post this to the master node
curl_close($ch);

unlink($jobid);
unlink("callback");
unlink($jobid."_videofragment_".$startframe.".mp4");
foreach($job as $file=>&$properties) unlink($file);

function computeX($frame,&$prop) {
	global $fps;
	if(!isset($prop["motion"])) return 0;
	$motion =& $prop["motion"];
	foreach($motion as &$motionelement) {
		if($motionelement["st"]*$fps<=$frame&&$motionelement["et"]*$fps>$frame) {//This is the motion element we need
			$motion["lastx"] = $motionelement["sx"]+(($motionelement["ex"]-$motionelement["sx"])*($frame-($motionelement["st"]*$fps)))/($motionelement["et"]*$fps-$motionelement["st"]*$fps);
			//echo "Frame $frame in bounds ".$motionelement["st"]."-".$motionelement["et"]." with x ".$motion["lastx"]."\n";
			return $motionelement["sx"]+(($motionelement["ex"]-$motionelement["sx"])*($frame-($motionelement["st"]*$fps)))/($motionelement["et"]*$fps-$motionelement["st"]*$fps);
		}
	}
	//If we get to this point, the object should remain at it's last computed point.
	//echo "Frame $frame out of bounds, using last x of ".$motion["lastx"]."\n";
	return $motion["lastx"];
}

function computeY($frame,&$prop) {
	global $fps;
	if(!isset($prop["motion"])) return 0;
	$motion = &$prop["motion"];
	foreach($motion as &$motionelement) {
		if($motionelement["st"]*$fps<=$frame&&$motionelement["et"]*$fps>$frame) {//This is the motion element we need
			$motion["lasty"] = $motionelement["sy"]+(($motionelement["ey"]-$motionelement["sy"])*($frame-($motionelement["st"]*$fps)))/($motionelement["et"]*$fps-$motionelement["st"]*$fps);
			return $motionelement["sy"]+(($motionelement["ey"]-$motionelement["sy"])*($frame-($motionelement["st"]*$fps)))/($motionelement["et"]*$fps-$motionelement["st"]*$fps);
		}
	}
	//If we get to this point, the object should remain at it's last computed point.
	return $motion["lasty"];
}

