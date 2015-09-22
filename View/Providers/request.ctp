<div class='page-header'>
<h1>LTI Request</h1>
</div>
<div class='content-container'>
<?php
if ($error):
	echo "Error: $error";
elseif ($message):
	echo "Message: $message";
else:
	echo $output;
endif;
pr($this->request->data);
?>

</div>
