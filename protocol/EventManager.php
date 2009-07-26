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
class XdebugProtocol_EventManager {
	protected $events = array();

	public function &__get($name) {
		return $this->events[$name];	
	}

	public function dispatch($name, $args) {
		if (isset($this->events[$name]) && is_array($this->events[$name]) && !empty($this->events[$name])) {
			foreach ($this->events[$name] as $handler) {
				call_user_func_array($handler, $args);
			}
		}
	}
}
