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
class GtkXdebugClientApplication {

	public $views = null;

	public function run() {
		$this->initAutoloader();
		$this->initializeProtocol();
		$this->loadUI();
		$this->main();
	}

	public function initAutoloader() {
		spl_autoload_register(array($this, 'loadClass'));		
	}

	public function loadClass($className) {
		if (!defined('GTKXDEBUGCLIENT_BASEPATH')) {
			define('GTKXDEBUGCLIENT_BASEPATH', realpath(dirname(__FILE__) . '/..'));
		}
		list($type, $name) = explode('_', $className, 2);
		if (empty($name)) {
			$name = $className;
		}
		$file = '';
		switch (strtolower($type)) {
			case 'view':
				$file = realpath(GTKXDEBUGCLIENT_BASEPATH . '/view/' . $name . '.php');
				break;
			case 'xdebugprotocol':
				$file = realpath(GTKXDEBUGCLIENT_BASEPATH . '/protocol/' . (empty($name) ? 'XdebugProtocol' : $name) . '.php');
				break;
			default:
				$file = realpath(GTKXDEBUGCLIENT_BASEPATH . '/application/' . $name . '.php');
				break;
		}
		if (!file_exists($file)) {
			return;
		}
		include ($file);
	}

	public function quit() {
		Gtk::main_quit();
	}

	public function initializeProtocol() {
		$this->protocol = new XdebugProtocol($this);
		Gtk::timeout_add(200, array($this->protocol, 'main'));
	}

	public function loadUI() {
		$this->ui = new GladeXML(GTKXDEBUGCLIENT_BASEPATH . '/view/interface.glade');
		$this->window = $this->ui->get_widget('rootWnd');
		$this->window->connect_simple('destroy', array($this, 'quit'));
		$this->ui->get_widget('quit_menu_item')->connect_simple('activate', array($this, 'quit'));

		$this->views = new stdClass;

		$this->views->watches = new View_Watch($this->ui->get_widget('watch_tree'), $this->protocol, $this);

		$this->views->breakpoints = new View_Breakpoints($this->ui->get_widget('breakpoints_tree'), $this->protocol, $this);
		$this->protocol->events->breakpointSet[] = array($this->views->breakpoints, 'addBreakpoint');
		$this->protocol->events->breakpointRemoved[] = array($this->views->breakpoints, 'removeBreakpoint');

		$this->views->stack = new View_Stack($this->ui->get_widget('stack_tree'), $this->protocol, $this);
		$this->protocol->events->stackReceived[] = array($this->views->stack, 'setStack');

		$this->views->editor = new View_Editor($this->ui->get_widget('scintilla_placeholder'), $this->protocol, $this);
		$this->protocol->events->sourceReceived[] = array($this->views->editor, 'setSource');
		$this->protocol->events->breakpointSet[] = array($this->views->editor, 'addBreakpoint');
		$this->protocol->events->breakpointRemoved[] = array($this->views->editor, 'removeBreakpoint');
		$this->protocol->events->stackReceived[] = array($this->views->editor, 'setCurrentByStack');


		$this->views->toolbar = new View_Toolbar($this->ui->get_widget('controls_toolbar'), $this->protocol, $this);
		$this->protocol->events->sourceReceived[] = array($this->views->toolbar, 'sourceReceived');
		$this->protocol->events->runStopped[] = array($this->views->toolbar, 'runStopped');

		// trace window
		$this->views->trace = new View_Trace($this->ui->get_widget('trace_window'), $this->protocol, $this);
		$this->protocol->events->trace[] = array($this->views->trace, 'addMessage');
		$this->ui->get_widget('trace_menu_item')->connect_simple('toggled', array($this->views->trace, 'showHide'));
		

	}

	public function main() {
		Gtk::main();
	}
}
