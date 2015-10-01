<div class='page-header'>
<h1>LTI Request</h1>
</div>
<div class='content-container'>
<?php
if (!empty($error)):
	echo "Error: $error";
elseif (!empty($message)):
	echo "Message: $message";
elseif (!empty($output)):
	echo $output;
endif;
pr($this->request->data);
?>

</div>
