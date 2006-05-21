<?php

// +-----------------------------------------------------------------------+
// | Copyright (c) 2006, Anish Mistry                                      |
// | All rights reserved.                                                  |
// |                                                                       |
// | Redistribution and use in source and binary forms, with or without    |
// | modification, are permitted provided that the following conditions    |
// | are met:                                                              |
// |                                                                       |
// | o Redistributions of source code must retain the above copyright      |
// |   notice, this list of conditions and the following disclaimer.       |
// | o Redistributions in binary form must reproduce the above copyright   |
// |   notice, this list of conditions and the following disclaimer in the |
// |   documentation and/or other materials provided with the distribution.|
// |                                                                       |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS   |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT     |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR |
// | A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT  |
// | OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, |
// | SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT      |
// | LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, |
// | DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY |
// | THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT   |
// | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE |
// | OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  |
// |                                                                       |
// +-----------------------------------------------------------------------+
// | Author: Anish Mistry <amistry@am-productions.biz>                     |
// +-----------------------------------------------------------------------+

require_once("password.inc.php");
require_once('./Sieve.php');
require_once('PHPUnit2/Framework/TestCase.php');

class SieveTest extends PHPUnit2_Framework_TestCase
{
	// contains the object handle of the string class
	protected $fixture;
	
	protected function setUp()
	{
		// create a new instance of Net_Sieve
		$this->fixture = new Net_Sieve();
		$this->scripts = array();
		$this->scripts['test script1'] = "require \"fileinto\";\n\rif header :contains \"From\" \"@cnba.uba.ar\" \n\r{fileinto \"INBOX.Test1\";}\r\nelse \r\n{fileinto \"INBOX\";}";
		$this->scripts['test script2'] = "require \"fileinto\";\n\rif header :contains \"From\" \"@cnba.uba.ar\" \n\r{fileinto \"INBOX.Test\";}\r\nelse \r\n{fileinto \"INBOX\";}";
		$this->scripts['test script3'] = "require \"vacation\";\nvacation\n:days 7\n:addresses [\"matthew@de-construct.com\"]\n:subject \"This is a test\"\n\"I'm on my holiday!\nsadfafs\";";
		$this->scripts['test script4'] = file_get_contents("largescript.siv");
		// clear all the scripts in the account
		$this->login();
		$scripts = $this->fixture->listScripts();
		foreach($scripts as $script)
		{
			$this->fixture->removeScript($script);
		}
		$this->logout();
	}
	
	protected function tearDown()
	{
		// delete your instance
		unset($this->fixture);
	}
	
	protected function login()
	{
		$result = $this->fixture->connect(HOST , PORT);
		$this->assertTrue($result,"Can not connect");
		$result = $this->fixture->login(USERNAME, PASSWORD  , null , '', false );
		$this->assertTrue($result,"Can not login");
	}

	protected function logout()
	{
		$result = $this->fixture->disconnect();
		$this->assertTrue(!PEAR::isError($result),"Error on disconnect");
	}

	public function testConnect()
	{
		$result = $this->fixture->connect(HOST , PORT);
		$this->assertTrue($result,"Can not connect");
	}
	
	public function testLogin()
	{
		$result = $this->fixture->connect(HOST , PORT);
		$this->assertTrue($result,"Can not connect");
		$result = $this->fixture->login(USERNAME, PASSWORD  , null , '', false );
		$this->assertTrue($result,"Can not login");
	}

	public function testDisconnect()
	{
		$result = $this->fixture->connect(HOST , PORT);
		$this->assertTrue(!PEAR::isError($result),"Can not connect");
		$result = $this->fixture->login(USERNAME, PASSWORD  , null , '', false );
		$this->assertTrue(!PEAR::isError($result),"Can not login");
		$result = $this->fixture->disconnect();
		$this->assertTrue(!PEAR::isError($result),"Error on disconnect");
	}

	public function testListScripts()
	{
		$this->login();
		$scripts = $this->fixture->listScripts();
		$this->logout();

		$this->assertTrue(!PEAR::isError($scripts),"Can not list scripts");
	}

	public function testInstallScript()
	{
		$this->login();
		// first script
		$scriptname = "test script1";
		$before_scripts = $this->fixture->listScripts();
		$result = $this->fixture->installScript( $scriptname, $this->scripts[$scriptname]);
		$this->assertTrue(!PEAR::isError($result),"Can not install script ".$scriptname);
		$after_scripts = $this->fixture->listScripts();
		$diff_scripts = array_values(array_diff($after_scripts,$before_scripts));
		$this->assertTrue(count($diff_scripts) > 0,"Script not installed");
		$this->assertEquals($scriptname,$diff_scripts[0],0,"Added script has a different name");
		// second script (install and activate)
		$scriptname = "test script2";
		$before_scripts = $this->fixture->listScripts();
		$result = $this->fixture->installScript( $scriptname, $this->scripts[$scriptname], true);
		$this->assertTrue(!PEAR::isError($result),"Can not install script ".$scriptname);
		$after_scripts = $this->fixture->listScripts();
		$diff_scripts = array_values(array_diff($after_scripts,$before_scripts));
		$this->assertTrue(count($diff_scripts) > 0,"Script not installed");
		$this->assertEquals($scriptname,$diff_scripts[0],0,"Added script has a different name");
		$active_script = $this->fixture->getActive();
		$this->assertEquals($scriptname,$active_script,0,"Added script has a different name");
		$this->logout();
	}

	public function testInstallScriptLarge()
	/*
	There is a good chance that this test will fail since most servers have a 32KB limit
	on uploaded scripts.
	*/
	{
		$this->login();
		// first script
		$scriptname = "test script4";
		$before_scripts = $this->fixture->listScripts();
		$result = $this->fixture->installScript( $scriptname, $this->scripts[$scriptname]);
		$this->assertTrue(!PEAR::isError($result),"Unable to upload large script");
		$after_scripts = $this->fixture->listScripts();
		$diff_scripts = array_diff($before_scripts,$after_scripts);
		$this->assertEquals($scriptname,$diff_scripts[0],0,"Added script has a different name");
		$this->logout();
	}

	public function testGetScript()
	{
		$this->login();
		// first script
		$scriptname = "test script1";
		$before_scripts = $this->fixture->listScripts();
		$result = $this->fixture->installScript( $scriptname, $this->scripts[$scriptname]);
		$this->assertTrue(!PEAR::isError($result),"Can not install script ".$scriptname);
		$after_scripts = $this->fixture->listScripts();
		$diff_scripts = array_values(array_diff($after_scripts,$before_scripts));
		$this->assertTrue(count($diff_scripts) > 0);
		$this->assertEquals($scriptname,$diff_scripts[0],0,"Added script has a different name");
		$script = $this->fixture->getScript($scriptname);
		$this->assertEquals(trim($this->scripts[$scriptname]),trim($script),0,"Script installed it not the same script retrieved");
		$this->logout();
	}

	public function testGetActive()
	{
		$this->login();
		$active_script = $this->fixture->getActive();
		$this->assertTrue(!PEAR::isError($active_script),"Error getting the active script");
		$this->logout();
	}

	public function testSetActive()
	{
		$scriptname = "test script1";
		$this->login();
		$result = $this->fixture->installScript( $scriptname, $this->scripts[$scriptname]);
		$result = $this->fixture->setActive($scriptname);
		$this->assertTrue(!PEAR::isError($result),"Can not set active script");
		$active_script = $this->fixture->getActive();
		$this->assertEquals($scriptname,$active_script,0,"Active script does not match");

		// test for non-existant script
		$result = $this->fixture->setActive("non existant script");
		$this->assertTrue(PEAR::isError($result));
		$this->logout();
	}

	public function testRemoveScript()
	{
		$scriptname = "test script1";
		$this->login();
		$result = $this->fixture->installScript( $scriptname, $this->scripts[$scriptname]);
		$result = $this->fixture->removeScript($scriptname);
		$this->assertTrue(!PEAR::isError($result),"Error removing active script");
		$this->logout();
	}
}
?>
