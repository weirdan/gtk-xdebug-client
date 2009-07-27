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
class View_Stack extends View_Widget {
	public function __construct($widget, $protocol, $application) {
		parent::__construct($widget, $protocol, $application);
		$this->list = new GtkListStore(GObject::TYPE_STRING);
		$this->tree = $this->application->ui->get_widget('stack_tree');
		$this->tree->append_column(new GtkTreeViewColumn('Function', new GtkCellRendererText, 'text', 0));
		$this->tree->set_model($this->list);
	}
	public function setStack($stack) {
		$this->list->clear();
		foreach ($stack as $frame) {
			$this->list->append(array($frame['where']));
		}
	}
}
