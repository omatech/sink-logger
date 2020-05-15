<?php

//à

namespace Omatech\Models;

class SinkModel extends AppModel {

	function __construct($conn, $params, $debug = false) {// requires doctrine dbal connection or array with data
		foreach ($params as $key => $val) {
			$this->$key = $val;
		}
		return parent::__construct($conn);
	}

	function log ($result, $code, $host, $path, $method, $sanitized, $ip, $input, $seconds_taken, $user_agent)
	{
		$sql = "insert into sink_logs (result, code, host, path, method, sanitized, ip, input, seconds_taken, user_agent, created_at) 
		values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue(1, $result);
		$stmt->bindValue(2, $code);
		$stmt->bindValue(3, $host);
		$stmt->bindValue(4, $path);
		$stmt->bindValue(5, $method);
		$stmt->bindValue(6, $sanitized);
		$stmt->bindValue(7, $ip);
		$stmt->bindValue(8, $input);
		$stmt->bindValue(9, $seconds_taken);
		$stmt->bindValue(10, $user_agent);
		$stmt->execute();
	}
}
