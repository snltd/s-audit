<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit license";
$pg = new docPage("s-audit license");

?>

<h1>License</h1>

<p>s-audit is released under a BSD License.</p>

<div class="codeBlock">

<p>Copyright (c) 2011, SearchNet Ltd<br />
All rights reserved.</p>

<p>Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:</p>

<ul>

	<li>Redistributions of source code must retain the above copyright
	notice, this list of conditions and the following disclaimer.</li>

	<li>Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in the
	documentation and/or other materials provided with the
	distribution.</li>

	<li>Neither the name of the &lt;ORGANIZATION&gt; nor the names of its
	contributors may be used to endorse or promote products derived from
	this software without specific prior written permission.</li>
	
</ul>

<p>THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.</p>

</div>

<?php

$pg->close_page();

?>

