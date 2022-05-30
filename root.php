<?php
/*
	Copyright (c) 2019-2022 Mark J Crane <markjcrane@fusionpbx.com>
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions
	are met:

		1. Redistributions of source code must retain the above copyright
		notice, this list of conditions and the following disclaimer.
	
		2. Redistributions in binary form must reproduce the above copyright
		notice, this list of conditions and the following disclaimer in the
		documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
	ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
	FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
	DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
	OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
	HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
	LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
	OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
	SUCH DAMAGE.
*/

// make sure the PATH_SEPARATOR is defined
	umask(2);
	if (!defined("PATH_SEPARATOR")) {
		if (strpos($_ENV["OS"], "Win") !== false) {
			define("PATH_SEPARATOR", ";");
		} else {
			define("PATH_SEPARATOR", ":");
		}
	}

	if (!isset($output_format)) $output_format = (PHP_SAPI == 'cli') ? 'text' : 'html';

	// make sure the document_root is set
	$_SERVER["SCRIPT_FILENAME"] = str_replace("\\", '/', $_SERVER["SCRIPT_FILENAME"]);
	if(PHP_SAPI == 'cli'){
		chdir(pathinfo(realpath($_SERVER["PHP_SELF"]), PATHINFO_DIRNAME));
		$script_full_path = str_replace("\\", '/', getcwd() . '/' . $_SERVER["SCRIPT_FILENAME"]);
		$dirs = explode('/', pathinfo($script_full_path, PATHINFO_DIRNAME));
		if (file_exists('/project_root.php')) {
			$path = '/';
		} else {
			$i    = 1;
			$path = '';
			while ($i < count($dirs)) {
				$path .= '/' . $dirs[$i];
				if (file_exists($path. '/project_root.php')) {
					break;
				}
				$i++;
			}
		}
		$_SERVER["DOCUMENT_ROOT"] = $path;
	}else{
		$_SERVER["DOCUMENT_ROOT"]   = str_replace($_SERVER["PHP_SELF"], "", $_SERVER["SCRIPT_FILENAME"]);
	}
	$_SERVER["DOCUMENT_ROOT"]   = realpath($_SERVER["DOCUMENT_ROOT"]);
// try to detect if a project path is being used
	if (!defined('PROJECT_PATH')) {
		if (is_dir($_SERVER["DOCUMENT_ROOT"]. '/fusionpbx')) {
			define('PROJECT_PATH', '/fusionpbx');
		} elseif (file_exists($_SERVER["DOCUMENT_ROOT"]. '/project_root.php')) {
			define('PROJECT_PATH', '');
		} else {
			$dirs = explode('/', str_replace('\\', '/', pathinfo($_SERVER["PHP_SELF"], PATHINFO_DIRNAME)));
			$i    = 1;
			$path = $_SERVER["DOCUMENT_ROOT"];
			while ($i < count($dirs)) {
				$path .= '/' . $dirs[$i];
				if (file_exists($path. '/project_root.php')) {
					break;
				}
				$i++;
			}
			if(!file_exists($path. '/project_root.php')){
				die("Failed to locate the Project Root by searching for project_root.php please contact support for assistance");
			}
			$project_path = str_replace($_SERVER["DOCUMENT_ROOT"], "", $path);
			define('PROJECT_PATH', $project_path);
		}
		$_SERVER["PROJECT_ROOT"] = realpath($_SERVER["DOCUMENT_ROOT"] . PROJECT_PATH);
		set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER["PROJECT_ROOT"]);
	}

?>
