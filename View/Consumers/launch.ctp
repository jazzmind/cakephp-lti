<div class='page-header'>
<h1>LTI Launch Test</h1>
</div>

<div class='content-container'>

<p>This is a very simple reference implementaton of the LMS side (i.e. consumer) for IMS BasicLTI.</p>


<a id="displayText" href="javascript:lmsdataToggle();">Toggle Resource and Launch Data</a>
<div id='ltiResourceData'>
<?php
	echo $this->Form->create('Consumer', array(
		'inputDefaults' => array(
			'div' => 'form-group',
			'label' => array(
				'class' => 'col col-md-3 control-label'
			),
			'wrapInput' => 'col col-md-9',
			'class' => 'form-control'
		),
		'class' => 'form-horizontal'
	));
	echo $this->Form->submit("Recompute Launch Data");
	$options = ['URL' => 'URL plus secret', 'XML' => 'XML Descriptor'];
	echo $this->Form->select('format', $options, ['empty' => false, 'onchange'=> "return form.submit();"]);

	echo("<fieldset><legend>BasicLTI Resource</legend>\n");
	if ( $urlformat ) {
		echo $this->Form->input('endpoint', ['type' => 'text', 'label' => 'Launch URL']);
	} else {
		echo $this->Form->input('xmldesc', ['type' => 'textarea', 'rows' => 10, 'cols' => 80, 'label' => 'XML BasicLTI Resource Descriptor']);
	}
	echo $this->Form->input('key', ['type' => 'text', 'label' => 'OAuth Key']);
	echo $this->Form->input('secret', ['type' => 'text', 'label' => 'OAuth Secret']);
	echo("</fieldset><p>");
	echo("<fieldset><legend>Launch Data</legend>\n");
	foreach ($lmsdata as $k => $val ) {
		echo $this->Form->input($k, ['type' => 'text', 'label' => ucfirst($k)]);
	}
	echo("</fieldset><p>");
	echo $this->Form->end();

?>
</div>
<hr>
<div id="ltiLaunchFormSubmitArea">
	<a id="displayText" href="javascript:basicltiDebugToggle();">toggle debug data</a>
	<div id="basicltiDebug" style="display:none;">
		<b>Basic LTI Endpoint</b><br/>
		<?= $endpoint ?>
		<b>Basic LTI Parameters</b><br/>
		<?= pr($params); ?>
		<b>Basic LTI String</b><br/>
		<?= pr($last_base_string); ?>
	</div>
<?php
	$opts = [
		'id' => 'ltiLaunchForm',
		'name' => 'ltiLaunchForm',
		'encType' => 'application/x-www-form-urlencoded',
		'url' => $endpoint,
	];
	if (!empty($iframeattr)) {
		$opts['target'] = "basicltiLaunchFrame";
	}
	//echo $this->Form->create(null, $opts);
 	echo "<form method='POST' name='{$opts['name']}' " .
 		"id='{$opts['id']}' " .
 		"target='{$opts['target']}' " .
 		"encType='{$opts['encType']}' " .
 		"action='{$opts['url']}' " . $iframeattr . ">";
    $submit_text = $params['ext_submit'];
    foreach($params as $key => $value ) {
        $key = htmlspecialchars($key);
        $value = htmlspecialchars($value);
        if ( $key == "ext_submit" ) {
	        echo $this->Form->submit($value);
        } else {
	        echo "<input type='hidden' name='$key' value='$value'>\n";
        }
    }
    if (!empty($debug)) {
    	echo $this->Form-hidden("ext_submit", ['value' => $submit_text]);
    }
    echo "</form>";

    // if ( ! $debug ) {
    //     $ext_submit = "ext_submit";
    //     $ext_submit_text = $submit_text;
    //     $r .= " <script type=\"text/javascript\"> \n" .
    //         "  //<![CDATA[ \n" .
    //         "    document.getElementById(\"ltiLaunchForm\").style.display = \"none\";\n" .
    //         "    nei = document.createElement('input');\n" .
    //         "    nei.setAttribute('type', 'hidden');\n" .
    //         "    nei.setAttribute('name', '".$ext_submit."');\n" .
    //         "    nei.setAttribute('value', '".$ext_submit_text."');\n" .
    //         "    document.getElementById(\"ltiLaunchForm\").appendChild(nei);\n" .
    //         "    document.ltiLaunchForm.submit(); \n" .
    //         "  //]]> \n" .
    //         " </script> \n";
    // }
?>
	<iframe name="basicltiLaunchFrame"  id="basicltiLaunchFrame" src="" <?= $iframeattr ?>><P>Frames Required</P></iframe>
</div>
</div>
<?php
	$this->Html->script('Lti.launch', ['block' => 'script']);
	$this->Js->buffer('
	("document").ready(function() {
		document.ltiLaunchForm.submit();
	});
	');
	$this->Js->writeBuffer();
