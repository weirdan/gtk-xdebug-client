<?php
/**
 * gtk-xdebug-client - Provides GUI for a debugger engine speaking DBGp
 * Copyright (C) 2009  Bruce Weirdan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * ut WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class XdebugProtocol {
	protected $_events;
	protected $port = 9000;
	protected static $version = '0.1';
	protected $sendQueue = array();
	protected $sentQueue = array();

	public function __construct($application) {
		$this->application = $application;
	}

	public function run() {
		$this->events->dispatch('runStarted');
		$this->send(array($this, 'runStopped'), 'run');
	}

	public function runStopped($message, $command, $params, $data) {
		$this->events->dispatch('runStopped');
		$this->send(array($this, 'stackReceived'), 'stack_get');
	}

	public function stackReceived($message, $command, $params, $data) {
		$stack = array();
		foreach ($message->stack as $frame) {
			$stack[] = array('level' => (string) $frame['level'],
							 'type' => (string) $frame['type'],
							 'filename' => (string) $frame['filename'],
							 'lineno' => ((int) $frame['lineno']) - 1,
							 'where' => (string) $frame['where'],
							 'cmdbegin' => (string) $frame['cmdbegin'],
							 'cmdend' => (string) $frame['cmdend']);
		}
		$this->events->dispatch('stackReceived', array($stack));
	}

	public function __get($name) {
		switch ($name) {
			case 'events':
				if (empty($this->_events)) {
					$this->_events = new XdebugProtocol_EventManager($this);
				}
				return $this->_events;
			case 'breakpoints':
				if (empty($this->_breakpoints)) {
					$this->_breakpoints = new XdebugProtocol_BreakpointManager($this);
				}
				return $this->_breakpoints;
		}
	}

	public function main() {
		if (empty($this->socket)) {
			$this->trace("Initialized XDebug protocol wrapper v" . self::$version);
			$this->socket = socket_create_listen($this->port);
			socket_set_nonblock($this->socket);
			socket_getsockname($this->socket, $addr, $port);
			$this->trace("Created socket on port {$addr}:{$port}");
		}

		if (!$this->connection) {
			$this->connection = @socket_accept($this->socket);
			if ($this->connection) {
				socket_getpeername($this->connection, $raddr, $rport);
				$this->traceIn("Accepted connection from {$raddr}:{$rport}");
			}
		}

		if ($this->connection) {
			$data = @socket_read($this->connection, 2048);
			if (!strlen($data) && socket_last_error($this->connection) != SOCKET_EWOULDBLOCK) {
					$this->traceIn('Closing connection');
					socket_shutdown($this->connection, 2);
					socket_close($this->connection);
					$this->connection = false;
					$this->buffer = '';
			}
			if ($this->connection) {
				socket_clear_error($this->connection);
				if (strlen($data)) {
					$this->buffer .= $data;
					$message = $this->parse($this->buffer);
					if (is_object($message)) {
						$this->traceIn('Got message: ' . $message->asXml());
						$this->dispatchMessage($message);
						$this->buffer = '';
					}
				}

				if (!empty($this->sendQueue)) {
					list($command, $params, $data, $callback) = array_shift($this->sendQueue);
					list($id, $message) = $this->formatCommand($command, $params, $data);
					$params['i'] = $id;
					$this->sentQueue[$id] = array($command, $params, $data, $callback);
					socket_write($this->connection, $message);
					$this->traceOut('Sent message: ' . $message);
				}
			}
		}

		return true;
	}

	protected function dispatchMessage(SimpleXMLElement $message) {
		switch ($message->getName()) {
			case 'init':
				$this->initPacketReceived($message);
				break;
			case 'response':
				$this->responsePacketReceived($message);
				break;
		}
	}

	protected function responsePacketReceived(SimpleXMLElement $message) {
		if (isset($this->sentQueue[(string)$message['transaction_id']])) {
			list($command, $params, $data, $callback) = $this->sentQueue[(string)$message['transaction_id']];
			if (is_callable($callback)) {
				call_user_func($callback, $message, $command, $params, $data);
			}
		}
	}

	protected function initPacketReceived(SimpleXMLElement $message) {
		$this->send(array($this, 'sourceReceived'), 'source', array('f' => (string) $message['fileuri']));
	}

	protected function sourceReceived(SimpleXMLElement $message, $command, $params, $data) {
		$this->events->dispatch('sourceReceived', array($params['f'], base64_decode((string) $message)));
		$this->runStopped($message, $command, $params, $data);
	}

	public function send($callback, $command, $params = array(), $data = "") {
		$this->sendQueue[] = array($command, $params, $data, $callback);
	}

	protected function parse($buffer) {
		$buffer = trim($buffer);
		if (strpos($buffer, "\0") !== false) {
			list($length, $buffer) = explode("\0", $buffer, 2);
		}
		libxml_clear_errors();
		$errorsEnabled = libxml_use_internal_errors(true);
		$msg = false;
		try {
			$msg = simplexml_load_string($buffer);
		} catch (Exception $e) {
		}

		if ($errors = libxml_get_errors()) {
			libxml_use_internal_errors($errorsEnabled);
			return false;
		}

		if (isset($e)) {
			libxml_use_internal_errors($errorsEnabled);
			return false;
		}

		libxml_use_internal_errors($errorsEnabled);
		return $msg;
	}

	public function formatCommand($command, $args = array(), $data = "") {
		if (empty($command)) {
			throw new InvalidArgumentException('Command cannot be empty');
		}
		static $i = 0;
		$args['i'] = ++$i;
		$argstring = '';
		foreach ($args as $name => $value) {
			if ($argstring) {
				$argstring .= ' ';
			}
			$argstring .= '-' . $name . ' ' . $value;
		}
		return array($args['i'], $command . ' ' . $argstring . ($data ? ' -- ' . base64_encode($data) : '') . "\0");
 	}

	protected function trace($msg) {
		$this->events->dispatch('trace', array($msg));
	}

	protected function traceIn($msg) {
		$this->trace('<<< ' . $msg);
	}

	protected function traceOut($msg) {
		$this->trace('>>> ' . $msg);
	}
}
