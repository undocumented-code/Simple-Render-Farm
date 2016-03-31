<?php
//Rendernode interface
//Tucker Osman 2016
//Recieves a job and starts the rendering process in a nonblocking way

foreach($_FILES as $file) {
	var_dump($file);
	move_uploaded_file($file["tmp_name"],$file["name"]);
}
file_put_contents($_POST["jobid"],$_POST["jobdescription"]);
file_put_contents("callback",$_POST["callback"]);
//echo("php rendernode.php ".$_POST["jobid"]." ".$_POST["framestart"]." ".$_POST["framecount"]);
shell_exec("php rendernode.php ".$_POST["jobid"]." ".$_POST["framestart"]." ".$_POST["framecount"]." >out 2>err &");
?>
