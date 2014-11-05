<?php

class PhpPDBar{	
	public $output = array();
	public $config = '';
	
	public function __construct($startTime, $config = '') 
	{
		$this->startTime = $startTime;
		$this->config = $config;
	}
	
	public function gatherConsoleData() {
		$logs = Console::getLogs();
		if($logs['console']) {
			foreach($logs['console'] as $key => $log) {
				if($log['type'] == 'log') {$logs['console'][$key]['data'] = print_r($log['data'], true);}
				elseif($log['type'] == 'memory') {$logs['console'][$key]['data'] = $this->getReadableFileSize($log['data']);}
				elseif($log['type'] == 'speed') {$logs['console'][$key]['data'] = $this->getReadableTime(($log['data'] - $this->startTime)*1000);}
			}
		}
		$this->output['logs'] = $logs;
	}
	
	public function gatherFileData() {
		$files = get_included_files();
		$fileList = array();
		$fileTotals = array("count" => count($files),"size" => 0,"largest" => 0,);
		foreach($files as $key => $file) {
			$size = filesize($file);
			$fileList[] = array('name' => $file,'size' => $this->getReadableFileSize($size));
			$fileTotals['size'] += $size;
			if($size > $fileTotals['largest']) $fileTotals['largest'] = $size;
		}		
		$fileTotals['size'] = $this->getReadableFileSize($fileTotals['size']);
		$fileTotals['largest'] = $this->getReadableFileSize($fileTotals['largest']);
		$this->output['files'] = $fileList;
		$this->output['fileTotals'] = $fileTotals;
	}
		
	public function gatherMemoryData() {
		$memoryTotals = array();
		$memoryTotals['used'] = $this->getReadableFileSize(memory_get_peak_usage());
		$memoryTotals['total'] = ini_get("memory_limit");
		$this->output['memoryTotals'] = $memoryTotals;
	}
	
	public function gatherQueryData() {
		$queryTotals = array();
		$queryTotals['count'] = 0;
		$queryTotals['time'] = 0;
		$queries = array();		
		if($this->db != '') {
			$queryTotals['count'] += $this->db->queryCount;
			foreach($this->db->queries as $key => $query) {
				$query = $this->attemptToExplainQuery($query);
				$queryTotals['time'] += $query['time'];
				$query['time'] = $this->getReadableTime($query['time']);
				$queries[] = $query;
			}
		}		
		$queryTotals['time'] = $this->getReadableTime($queryTotals['time']);
		$this->output['queries'] = $queries;
		$this->output['queryTotals'] = $queryTotals;
	}
	
	function attemptToExplainQuery($query) {
		try {
			$sql = 'EXPLAIN '.$query['sql'];
			$rs = $this->db->query($sql);
		}
		catch(Exception $e) {}
		if($rs) {
			$row = mysql_fetch_array($rs, MYSQL_ASSOC);
			$query['explain'] = $row;
		}
		return $query;
	}
	
	public function gatherSpeedData() {
		$speedTotals = array();
		$speedTotals['total'] = $this->getReadableTime(($this->getMicroTime() - $this->startTime)*1000);
		$speedTotals['allowed'] = ini_get("max_execution_time");
		$this->output['speedTotals'] = $speedTotals;
	}
	
	static function getMicroTime() {
		$time = microtime();
		$time = explode(' ', $time);
		return $time[1] + $time[0];
	}
	
	public function getReadableFileSize($size, $retstring = null) {
        	// adapted from code at http://aidanlister.com/repos/v/function.size_readable.php
	       $sizes = array('bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

	       if ($retstring === null) { $retstring = '%01.2f %s'; }

		$lastsizestring = end($sizes);

		foreach ($sizes as $sizestring) {
	       	if ($size < 1024) { break; }
	           if ($sizestring != $lastsizestring) { $size /= 1024; }
	       }
	       if ($sizestring == $sizes[0]) { $retstring = '%01d %s'; } // Bytes aren't normally fractional
	       return sprintf($retstring, $size, $sizestring);
	}
	
	public function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret,3,'.','') . ' ' . $formats[$formatter];
		return $ret;
	}
	
	public function display($db = '', $master_db = '') {
		$this->db = $db;
		$this->master_db = $master_db;
		$this->gatherConsoleData();
		$this->gatherFileData();
		$this->gatherMemoryData();
		$this->gatherQueryData();
		$this->gatherSpeedData();
		$this->outputAll($this->output);
	}
	
	private function outputAll($output,$cssUrl="css/default.css")
	{ 
			
echo <<<JAVASCRIPT
<!-- JavaScript -->
<script type="text/javascript">
	var PDBAR_DETAILS = true;
	var PDBAR_HEIGHT = "short";
	
	addEvent(window, 'load', loadCSS);

	function changeTab(tab) 
	{
		var pdbar = document.getElementById('pdbar');
		hideAllTabs();
		addClassName(pdbar, tab, true);
	}
	
	function hideAllTabs() 
	{
		var pQp = document.getElementById('pdbar');
		removeClassName(pdbar, 'console');
		removeClassName(pdbar, 'speed');
		removeClassName(pdbar, 'queries');
		removeClassName(pdbar, 'memory');
		removeClassName(pdbar, 'files');
	}
	
	function toggleDetails()
	{
		var container = document.getElementById('pdbar-container');
		if(PDBAR_DETAILS)
		{
			addClassName(container, 'hideDetails', true);
			PDBAR_DETAILS = false;
		}
		else
		{
			removeClassName(container, 'hideDetails');
			PDBAR_DETAILS = true;
		}
	}
	
	function toggleHeight()
	{
		var container = document.getElementById('pdbar-container');		
		if(PDBAR_HEIGHT == "short")
		{
			addClassName(container, 'tallDetails', true);
			PDBAR_HEIGHT = "tall";
		}
		else
		{
			removeClassName(container, 'tallDetails');
			PDBAR_HEIGHT = "short";
		}
	}
	
	function loadCSS() {
		var sheet = document.createElement("link");
		sheet.setAttribute("rel", "stylesheet");
		sheet.setAttribute("type", "text/css");
		sheet.setAttribute("href", "$cssUrl");
		document.getElementsByTagName("head")[0].appendChild(sheet);
		setTimeout(function(){document.getElementById("pdbar-container").style.display = "block"}, 10);
	}
	
	function addClassName(objElement, strClass, blnMayAlreadyExist){
	   if ( objElement.className ){
	      var arrList = objElement.className.split(' ');
	      if ( blnMayAlreadyExist ){
	         var strClassUpper = strClass.toUpperCase();
	         for ( var i = 0; i < arrList.length; i++ ){
	            if ( arrList[i].toUpperCase() == strClassUpper ){
	               arrList.splice(i, 1);
	               i--;
	             }
	           }
	      }
	      arrList[arrList.length] = strClass;
	      objElement.className = arrList.join(' ');
	   }
	   else{  
	      objElement.className = strClass;
	      }
	}

	function removeClassName(objElement, strClass){
	   if ( objElement.className ){
	      var arrList = objElement.className.split(' ');
	      var strClassUpper = strClass.toUpperCase();
	      for ( var i = 0; i < arrList.length; i++ ){
	         if ( arrList[i].toUpperCase() == strClassUpper ){
	            arrList.splice(i, 1);
	            i--;
	         }
	      }
	      objElement.className = arrList.join(' ');
	   }
	}

	function addEvent( obj, type, fn ) {
	  if ( obj.attachEvent ) {
	    obj["e"+type+fn] = fn;
	    obj[type+fn] = function() { obj["e"+type+fn]( window.event ) };
	    obj.attachEvent( "on"+type, obj[type+fn] );
	  } 
	  else{
	    obj.addEventListener( type, fn, false );	
	  }
	}
</script>
JAVASCRIPT;

echo '<div id="pdbar-container" class="pdbar" style="display:none">';

$logCount = count($output['logs']['console']);
$fileCount = count($output['files']);
$memoryUsed = $output['memoryTotals']['used'];
$queryCount = $output['queryTotals']['count'];
$speedTotal = $output['speedTotals']['total'];

echo <<<PQPTABS
<div id="pdbar" class="console">
<table id="pdbar-metrics" cellspacing="0">
<tr>
	<td class="green" onclick="changeTab('console');">
		<h4>Konsole</h4>
		<var>$logCount</var>		
	</td>
	<td class="blue" onclick="changeTab('speed');">
		<h4>Ladezeit</h4>
		<var>$speedTotal</var>		
	</td>
	<td class="purple" onclick="changeTab('queries');" style="display:none;">
		<h4>Datenbank</h4>
		<var>$queryCount Abfragen</var>		
	</td>
	<td class="orange" onclick="changeTab('memory');">
		<h4>Speicherverbrauch</h4>
		<var>$memoryUsed</var>		
	</td>
	<td class="red" onclick="changeTab('files');">
		<h4>Eingeb.Dateien</h4>
		<var>{$fileCount}</var>		
	</td>
</tr>
</table>
PQPTABS;

echo '<div id="pdbar-console" class="pdbar-box">';

if($logCount ==  0) {echo '<h3>Keine Eintr&auml;ge.</h3>';}
else 
{
	echo '<table class="side" cellspacing="0">
		<tr>
			<td class="alt1"><var>'.$output['logs']['logCount'].'</var><h4>Logs</h4></td>
			<td class="alt2"><var>'.$output['logs']['errorCount'].'</var> <h4>Fehler</h4></td>
		</tr>
		<tr>
			<td class="alt3"><var>'.$output['logs']['memoryCount'].'</var> <h4>Speicher</h4></td>
			<td class="alt4"><var>'.$output['logs']['speedCount'].'</var> <h4>Geschw.</h4></td>
		</tr>
		</table>
		<table class="main" cellspacing="0">';
		
		$class = '';
		foreach($output['logs']['console'] as $log) {
			echo '<tr class="log-'.$log['type'].'">
				<td class="type">'.$log['type'].'</td>
				<td class="'.$class.'">';
			if($log['type'] == 'log') {
				echo '<div><pre>'.$log['data'].'</pre></div>';
			}
			elseif($log['type'] == 'memory') {
				echo '<div><pre>'.$log['data'].'</pre> <em>'.$log['dataType'].'</em>: '.$log['name'].' </div>';
			}
			elseif($log['type'] == 'speed') {
				echo '<div><pre>'.$log['data'].'</pre> <em>'.$log['name'].'</em></div>';
			}
			elseif($log['type'] == 'error') {
				echo '<div><em>Line '.$log['line'].'</em> : '.$log['data'].' <pre>'.$log['file'].'</pre></div>';
			}
		
			echo '</td></tr>';
			if($class == '') $class = 'alt';
			else $class = '';
		}
			
		echo '</table>';
}

echo '</div>';

echo '<div id="pdbar-speed" class="pdbar-box">';

if($output['logs']['speedCount'] ==  0) {
	echo '<h3>Keine Eintr&auml;ge.</h3>';
}
else {
	echo '<table class="side" cellspacing="0">
		  <tr><td><var>'.$output['speedTotals']['total'].'</var><h4>Ladezeit</h4></td></tr>
		  <tr><td class="alt"><var>'.$output['speedTotals']['allowed'].'</var> <h4>Max. erlaubte Ladezeit</h4></td></tr>
		 </table>
		<table class="main" cellspacing="0">';
		
		$class = '';
		foreach($output['logs']['console'] as $log) {
			if($log['type'] == 'speed') {
				echo '<tr class="log-'.$log['type'].'">
				<td class="'.$class.'">';
				echo '<div><pre>'.$log['data'].'</pre> <em>'.$log['name'].'</em></div>';
				echo '</td></tr>';
				if($class == '') $class = 'alt';
				else $class = '';
			}
		}
			
		echo '</table>';
}

echo '</div>';

echo '<div id="pdbar-queries" class="pdbar-box">';

if($output['queryTotals']['count'] ==  0) {
	echo '<h3>Keine Eintr&auml;ge.</h3>';
}
else {
	echo '<table class="side" cellspacing="0">
		  <tr><td><var>'.$output['queryTotals']['count'].'</var><h4>Gesamt-Abfragen</h4></td></tr>
		  <tr><td class="alt"><var>'.$output['queryTotals']['time'].'</var> <h4>Gesamtzeit</h4></td></tr>
		  <tr><td><var>0</var> <h4>Duplikate</h4></td></tr>
		 </table>
		<table class="main" cellspacing="0">';
		
		$class = '';
		foreach($output['queries'] as $query) {
			echo '<tr>
				<td class="'.$class.'">'.$query['sql'];
			if($query['explain']) {
					echo '<em>
						Possible keys: <b>'.$query['explain']['possible_keys'].'</b> &middot; 
						Key Used: <b>'.$query['explain']['key'].'</b> &middot; 
						Type: <b>'.$query['explain']['type'].'</b> &middot; 
						Rows: <b>'.$query['explain']['rows'].'</b> &middot; 
						Speed: <b>'.$query['time'].'</b>
					</em>';
			}
			echo '</td></tr>';
			if($class == '') $class = 'alt';
			else $class = '';
		}
			
		echo '</table>';
}

echo '</div>';

echo '<div id="pdbar-memory" class="pdbar-box">';

if($output['logs']['memoryCount'] ==  0) {
echo '<h3>Keine Eintr&auml;ge.</h3>';
}
else {
	echo '<table class="side" cellspacing="0">
		  <tr><td><var>'.$output['memoryTotals']['used'].'</var><h4>Benutzter Speicher</h4></td></tr>
		  <tr><td class="alt"><var>'.$output['memoryTotals']['total'].'</var> <h4>Gesamt Verf&uuml;gbar</h4></td></tr>
		 </table>
		<table class="main" cellspacing="0">';
		
		$class = '';
		foreach($output['logs']['console'] as $log) {
			if($log['type'] == 'memory') {
				echo '<tr class="log-'.$log['type'].'">';
				echo '<td class="'.$class.'"><b>'.$log['data'].'</b> <em>'.$log['dataType'].'</em>: '.$log['name'].'</td>';
				echo '</tr>';
				if($class == '') $class = 'alt';
				else $class = '';
			}
		}
			
		echo '</table>';
}

echo '</div>';

echo '<div id="pdbar-files" class="pdbar-box">';

if($output['fileTotals']['count'] ==  0) {
echo '<h3>Keine Eintr&auml;ge.</h3>';
}
else {
	echo '<table class="side" cellspacing="0">
		  	<tr><td><var>'.$output['fileTotals']['count'].'</var><h4>Dateien insgesamt</h4></td></tr>
			<tr><td class="alt"><var>'.$output['fileTotals']['size'].'</var> <h4>Gesamtgr&ouml;&szlig;e</h4></td></tr>
			<tr><td><var>'.$output['fileTotals']['largest'].'</var> <h4>Gr&ouml;&szlig;te</h4></td></tr>
		 </table>
		<table class="main" cellspacing="0">';
		
		$class ='';
		foreach($output['files'] as $file) {
			echo '<tr><td class="'.$class.'"><b>'.$file['size'].'</b> '.$file['name'].'</td></tr>';
			if($class == '') $class = 'alt';
			else $class = '';
		}
			
		echo '</table>';
}

echo '</div>';

echo <<<FOOTER
	<table id="pdbar-footer" cellspacing="0">
		<tr>
			<td class="credit">
				<a href="http://www.fresh-dev.de" target="_blank">
				<b class="blue">Fresh</b>-<b class="green">DEV</b><b class="orange">.</b><b class="red">de</b></a></td>
			<td class="actions">
				<a href="#" onclick="toggleDetails();return false">Details</a>
				<a class="heightToggle" href="#" onclick="toggleHeight();return false">H&ouml;he &auml;ndern</a>
			</td>
		</tr>
	</table>
FOOTER;
		
echo '</div></div>';
	}
	
}

class Console {
	
	public static function log($data) {
		$logItem = array(
			"data" => $data,
			"type" => 'log'
		);
		$GLOBALS['debugger_logs']['console'][] = $logItem;
		if(!isset($GLOBALS['debugger_logs']['logCount'])){$GLOBALS['debugger_logs']['logCount']=1;}
		$GLOBALS['debugger_logs']['logCount'] += 1;
	}
		
	public static function logMemory($object = false, $name = 'PHP') {
		$memory = memory_get_usage();
		if($object) $memory = strlen(serialize($object));
		$logItem = array(
			"data" => $memory,
			"type" => 'memory',
			"name" => $name,
			"dataType" => gettype($object)
		);
		$GLOBALS['debugger_logs']['console'][] = $logItem;
		if(!isset($GLOBALS['debugger_logs']['memoryCount'])){$GLOBALS ['debugger_logs'] ['memoryCount'] = 1;}
		$GLOBALS['debugger_logs']['memoryCount'] += 1;
	}
	
	public static function logError($exception, $message) {
		$logItem = array(
			"data" => $message,
			"type" => 'error',
			"file" => $exception->getFile(),
			"line" => $exception->getLine()
		);
		$GLOBALS['debugger_logs']['console'][] = $logItem;
		if(!isset($GLOBALS['debugger_logs']['errorCount'])){	$GLOBALS['debugger_logs']['errorCount'] = 1;}
		$GLOBALS['debugger_logs']['errorCount'] += 1;
	}
	
	public static function logSpeed($name = 'Point in Time') {
		$logItem = array(
			"data" => PhpPDBar::getMicroTime(),
			"type" => 'speed',
			"name" => $name
		);
		$GLOBALS['debugger_logs']['console'][] = $logItem;
		if(!isset($GLOBALS['debugger_logs']['speedCount'])){$GLOBALS['debugger_logs']['speedCount'] = 1;}
		$GLOBALS['debugger_logs']['speedCount'] += 1;
	}
		
	public static function getLogs() {
		if(!isset($GLOBALS['debugger_logs']['memoryCount'])){$GLOBALS['debugger_logs']['memoryCount'] = 0;}
		if(!isset($GLOBALS['debugger_logs']['logCount']) ){$GLOBALS['debugger_logs']['logCount'] = 0;}
		if(!isset($GLOBALS['debugger_logs']['speedCount']) ){$GLOBALS['debugger_logs']['speedCount'] = 0;}
		if(!isset($GLOBALS['debugger_logs']['errorCount']) ){$GLOBALS['debugger_logs']['errorCount'] = 0;}
		return $GLOBALS['debugger_logs'];
	}
}


class MySqlDatabase {

	private $host;			
	private $user;		
	private $password;	
	private $database;	
	public $queryCount = 0;
	public $queries = array();
	public $conn;
		
	function __construct($host, $user, $password) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
	}
	
	function connect($new = false) {
		$this->conn = mysql_connect($this->host, $this->user, $this->password, $new);
		if(!$this->conn) {throw new Exception('We\'re working on a few connection issues.');}
	}
	
	function changeDatabase($database) {$this->database = $database;if($this->conn) {if(!mysql_select_db($database, $this->conn)) {	throw new CustomException('We\'re working on a few connection issues.');}}}
	
	function lazyLoadConnection() {	$this->connect(true);	if($this->database) $this->changeDatabase($this->database);	}
		
	function query($sql) {
		if(!$this->conn) $this->lazyLoadConnection();
		$start = $this->getTime();
		$rs = mysql_query($sql, $this->conn);
		$this->queryCount += 1;
		$this->logQuery($sql, $start);
		if(!$rs) {throw new Exception('Could not execute query.');}
		return $rs;
	}
	
	function logQuery($sql, $start) {$query = array('sql' => $sql,'time' => ($this->getTime() - $start)*1000);array_push($this->queries, $query);}
	
	function getTime() {
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;
		return $start;
	}
	
	public function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret,3,'.','') . ' ' . $formats[$formatter];
		return $ret;
	}	
	function __destruct()  {@mysql_close($this->conn);}	
}

?>