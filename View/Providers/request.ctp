<?php
if ($error):
	echo "Error: $error";
else if ($message):
	echo "Message: $message";
else
	echo $output;
