<?php
require_once('PhpPDBar.php');

class PQPExample {
	
	private $profiler;
	private $db = '';
	
	public function __construct() {
		$this->profiler = new PhpPDBar(PhpPDBar::getMicroTime());
	}
	
	public function init() {
		$this->sampleConsoleData();
		$this->sampleDatabaseData();
		$this->sampleMemoryLeak();
		$this->sampleSpeedComparison();
	}
	
	public function sampleConsoleData() {
		try {
			Console::log('Starte logging');
			Console::logMemory($this, 'Beispielseite : Zeile '.__LINE__);
			Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
			Console::log(array('Name' => 'Max', 'Nachname' => 'Mustermann'));
			Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
			Console::logMemory($this, 'Beispielseite : Zeile '.__LINE__);
			Console::log('Beenden des Loggings mit einem Fehler(hierdrunter).');
			throw new Exception('Beispiel-Fehler!');
		}
		catch(Exception $e) {
			Console::logError($e, 'Catch Fehlermeldung.');
		}
	}
		
	public function sampleDatabaseData() {}
		
	public function sampleMemoryLeak() {
		$ret = '';
		$longString = 'This is a really long string that when appended with the . symbol 
					  will cause memory to be duplicated in order to create the new string.';
		for($i = 0; $i < 10; $i++) {
			$ret = $ret . $longString;
			Console::logMemory($ret, 'Watch memory leak -- iteration '.$i);
		}
	}
	
	public function sampleSpeedComparison() {
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
		Console::logSpeed('Zeit gebraucht bis Zeile '.__LINE__);
	}
	
	public function __destruct() {
		$this->profiler->display($this->db);
	}
	
}

$pqp = new PQPExample();
$pqp->init();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>PHP Profiler und Debugging Bar Demo</title>
<!-- CSS -->
<style type="text/css">
body{
	font-family:"Lucida Grande", Tahoma, Arial, sans-serif;
	margin:100px 0 0 0;
	background:#eee;
}
h3{
	line-height:160%;
}
#box{
	margin:100px auto 0 auto;
	width: 450px;
	padding:10px 20px 30px 20px;
	background-color: #FFF;
	border: 10px solid #dedede;
}
#box ul {
	margin:0 0 20px 0;
	padding:0;
}
#box li {
	margin:0 0 0 20px;
	padding:0 0 10px 0;
}
li a{
	color:blue;
}
strong a{
	color:#7EA411;
}
</style>
</head>
<body>
<div id="box">
	<h3>Beispielseite f&uuml;r <br /> PHP Profiler und Debugging Bar...</h3>
	<ul>
		<li>PHP Objekte loggen. [ <a href="#" onclick="changeTab('console'); return false;">Demo</a> ]</li>
		<li>Memory-Usage &Uuml;berwachen. [ <a href="#" onclick="changeTab('memory'); return false;">Demo</a> ]</li>
		<!--<li>Monitor our queries and their indexes. [ <a href="#" onclick="changeTab('queries'); return false;">Demo</a> ]</li>-->
		<li>Ausf&uuml;hrungs-Zeiten &uuml;berwachen. [ <a href="#" onclick="changeTab('speed'); return false;">Demo</a> ]</li>
		<li>Eingebundene Dateien auflisten. [ <a href="#" onclick="changeTab('files'); return false;">Demo</a> ]</li>
	</ul>
</div>
</body>
</html>