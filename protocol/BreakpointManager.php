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
class XdebugProtocol_BreakpointManager implements ArrayAccess {
	protected $_breakpoints = array();

	public function __construct($protocol) {
		$this->protocol = $protocol;
	}

	public function offsetExists($offset) {
		list($file, $line) = $this->offsetToPosition($offset);
		return isset($this->_breakpoints[$file][$line]);
	}

	public function offsetGet($offset) {
		list($file, $line) = $this->offsetToPosition($offset);
		return $this->_breakpoints[$file][$line];
	}

	public function offsetSet($offset, $value) {
		list($file, $line) = $this->offsetToPosition($offset);
		if (!isset($this->_breakpoints[$file][$line])) {
			// dbgp uses 1-based line numbers
			$this->protocol->send(array($this, 'breakpointSet'), 'breakpoint_set', array('t' => 'line', 'f' => $file, 'n' => $line + 1));
		}
	}

	public function offsetUnset($offset) {
		list($file, $line) = $this->offsetToPosition($offset);
		if (isset($this->_breakpoints[$file][$line])) {
			// dbgp uses 1-based line numbers
			$this->protocol->send(array($this, 'breakpointRemoved'), 'breakpoint_remove', array('f' => $file, 'n' => $line + 1, 'd' => $this->_breakpoints[$file][$line]));
		}
	}

	public function offsetToPosition($offset) {
		$pos = strrpos($offset, ':');
		$line = substr($offset, $pos + 1);
		$file = substr($offset, 0, $pos);
		return array($file, $line);
	}

	public function breakpointSet($message, $command, $params, $data) {
		$this->_breakpoints[$params['f']][$params['n'] - 1] = (string) $message['id'];
		$this->protocol->events->dispatch('breakpointSet', array($params['f'], $params['n'] - 1, (string)$message['id']));
	}

	public function breakpointRemoved($message, $command, $params, $data) {
		$file = $params['f'];
		$line = $params['n'];
		$breakpointId = $this->_breakpoints[$file][$line - 1];
		unset($this->_breakpoints[$file][$line - 1]);
		$this->protocol->events->dispatch('breakpointRemoved', array($file, $line - 1, $breakpointId));
	}
}
