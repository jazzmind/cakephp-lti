<html>
<head>
<title>IMS LTI message</title>
<script type="text/javascript">
//<![CDATA[
function doOnLoad() {
	document.forms[0].submit();
}

window.onload=doOnLoad;
//]]>
</script>
</head>
<body>
<form action="<?=$url?>" method="post" target="" encType="application/x-www-form-urlencoded">
<?php
foreach($params as $key => $value ):
	$key = htmlentities($key, ENT_COMPAT | ENT_HTML401, 'UTF-8');
	$value = htmlentities($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
?>
	<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
<?php
endforeach;
?>
</form>
</body>
</html>
