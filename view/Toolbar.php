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
class View_Toolbar extends View_Widget {
	public function __construct($widget, $protocol, $application) {
		parent::__construct($widget, $protocol, $application);
		$this->runButton = $application->ui->get_widget('run_button');
		$this->stepIntoButton = $application->ui->get_widget('step_into_button');
		$this->stepOverButton = $application->ui->get_widget('step_over_button');
		$this->stepOutButton = $application->ui->get_widget('step_out_button');
		$this->stopButton = $application->ui->get_widget('stop_debugging_button');


		$this->runButton->connect_simple('clicked', array($this, 'runClicked'));
	}

	public function runClicked() {
		$this->protocol->run();
	}

	public function runStarted() {
		$this->runButton->set_sensitive(false);
		$this->stepIntoButton->set_sensitive(false);
		$this->stepOverButton->set_sensitive(false);
		$this->stepOutButton->set_sensitive(false);
		$this->stopButton->set_sensitive(false);
	}

	public function runStopped() {
		$this->runButton->set_sensitive(true);
		$this->stepIntoButton->set_sensitive(true);
		$this->stepOverButton->set_sensitive(true);
		$this->stepOutButton->set_sensitive(true);
		$this->stopButton->set_sensitive(true);
	}

	public function sourceReceived($filename, $source) {
		$this->runButton->set_sensitive(true);
		$this->stepIntoButton->set_sensitive(true);
		$this->stepOverButton->set_sensitive(true);
		$this->stepOutButton->set_sensitive(true);
		$this->stopButton->set_sensitive(true);
	}
}
