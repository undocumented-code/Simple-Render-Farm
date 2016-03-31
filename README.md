# Simple-Render-Farm
This is a simple render farm using PHP, ImageMagick, and FFMPEG

##Configuration
To give it a job, go to [this blog post](http://undocumented-code.blogspot.com/2016/03/building-basic-render-farm.html) to see the format for sequential animations of objects.

Nodes are in a file that is new line separated with the path to your render node code. Don't put `rendernodeinterface.php` on the end of it - the master node will do that for you. Look at the files for more details.
