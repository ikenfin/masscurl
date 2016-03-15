#!/usr/local/bin/php
<?php

	/* 
		masscurl - 	PHP script that filter and output curl data
					can be used for information gathering about site urls
		Example (lets found all valid users ids):
			crunch 1 6 0123456789 | sed sed s/^/http:\\/\\/site.com\\/user\\//account_id\\// | masscurl -m f,ss

			Example output:
			http://site.com/user/account_id/0 404
			http://site.com/user/account_id/1 404
			http://site.com/user/account_id/2 200
			http://site.com/user/account_id/3 200

		Or you can use filter:
			crunch 1 6 0123456789 | sed sed s/^/http:\\/\\/site.com\\/user\\//account_id\\// | masscurl -f f

			Example output:
			http://site.com/user/account_id/2
			http://site.com/user/account_id/3

		@author - ikenfin
	*/

	$short = array(
		'r:', // urls file to read from
		'w:', // output to file
		'm:', // mode(see above)
		'f:', // filter (see above)
		'v'   // verbose mode
	);

	/*
		Filters :
				key -f
				options:
					s=400,404,502  <-- exact status codes
					nf === s=404   <-- not found shortkey
					f  === s=200   <-- found shortkey
		Output filtering mode :
				 -m ri,rsi,rc,t    <-- see comments on $modes_names above
	*/

	$filter_names = array(
		's' => (1 << 0),
		'nf'=> (1 << 1),
		'f' => (1 << 2)
	);

	$filter_statuses = array();

	$modes_names = array(
		'f'  => (1 << 0), // flat info mode (only urls)
		'ss' => (1 << 1), // show status code
		'ri' => (1 << 2), // request info
		'rsi'=> (1 << 3), // response info
		'rc' => (1 << 4), // redirect info
		't'  => (1 << 5), // time info
	);

	$modes_out = array(
		(1 << 2) => array(
			'url', 'content_type', 'http_code'
		),
		(1 << 3) => array(
			'content_type', 'http_code', 'header_size', 'filetime', 'ssl_verify_result'
		),
		(1 << 4) => array(
			'redirect_url', 'redirect_count', 'redirect_time'
		),
		(1 << 5) => array(
			'total_time', 'namelookup_time', 'connect_time', 'pretransfer_time', 'starttransfer_time'
		)
	);

	$options = getopt(implode('', $short));	

	$verbose = FALSE; // TRUE - more informative mode

	$urls_in = 'php://stdin';
	$urls_out = 'php://stdout';

	$mode = (1 << 0);

	foreach($options as $option=>$value) {
		switch($option) {
			case 'r' :
				$urls_in = getcwd() . DIRECTORY_SEPARATOR . $value;
			break;

			case 'w' :
				if($value != '-') {
					$urls_out = getcwd() . DIRECTORY_SEPARATOR . $value;
				}
			break;

			case 'm' :
				$modes = explode(',', $value);
				
				foreach($modes as $mode_name) {
					$mode_name = trim($mode_name);
					
					if(!in_array($mode_name, array_keys($modes_names))) {
						echo "Mode $mode_name not exists!\n";
						exit(1);
					}

					$mode |= $modes_names[$mode_name];
				}
			break;

			case 'f' :
				list($option, $option_value) = explode('=', $value);

				switch($option) {
					case 'nf' :
						$filter_statuses[] = 404;
					break;
					case 'f' :
						$filter_statuses[] = 200;
					break;
					case 's' :
						$filter_statuses = explode(',', $option_value);
					break;
				}

			break;

			case 'v' :
				$verbose = TRUE;
			break;
		}
	}

	$urls_in_fd = fopen($urls_in, 'r');
	$urls_out_fd = fopen($urls_out, 'w');

	$curl = curl_init();

	while(($url = fgets($urls_in_fd)) !== FALSE) {
		curl_setopt($curl, CURLOPT_URL, trim($url));
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		
		curl_exec($curl);

		$info = curl_getinfo($curl);
		
		if(count($filter_statuses) > 0) {
			if(!in_array($info['http_code'], $filter_statuses))
				continue;
		}

		$message = "";
		
		if($mode & $modes_names['f']) {
			$message .= $info['url'];
			
			if($mode & $modes_names['ss'])
				$message .= " " . $info['http_code'];

			$message .= "\n";
		}

		$modes_keys = array_keys($modes_out);

		foreach($modes_keys as $mode_key) {
			if($mode & $mode_key) {
				$fields = $modes_out[$mode_key];
				
				foreach($fields as $field) {
					$message .= "{$field} -> " . $info[$field] . "\n";
				}
			}
			else continue;
			
			$message .= "------======*======------\n";
		}

		fwrite($urls_out_fd, $message);
	}

	curl_close($curl);

	fclose($urls_in_fd);
	fclose($urls_out_fd);