<?php 
// Load up the Basic LTI Support code
require_once 'ims-blti/blti.php';

// Initialize, all secrets are 'secret', do not set session, and do not redirect
$context = new BLTI("secret", false, false);
?>
<html>
<head>
  <title>IMS Basic Learning Tools Interoperability</title>
</head>
<body style="font-family:sans-serif">
<img src="http://www.imsglobal.org/images/IMSGLCLogo.jpg"/>
<p><b>IMS BasicLTI PHP Provider</b></p>
<p>This is a very simple Basic LTI Tool.  If the message is a Basic LTI Launch,
it checks the signature and if the signature is OK,  it establishes context.
All secrets are "secret".
</p>
<?php

if ( $context->valid ) {
    print "<pre>\n";
    print "Context Information:\n\n";
    print $context->dump();
    print "</pre>\n";
} else {
    print "<p style=\"color:red\">Could not establish context: ".$context->message."<p>\n";
}

print "<pre>\n";
print "Raw POST Parameters:\n\n";
foreach($_POST as $key => $value ) {
    print "$key=$value\n";
}
print "</pre>";

?>
