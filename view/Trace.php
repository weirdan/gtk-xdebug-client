<?
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
class View_Trace extends View_Widget {
	protected $list = null;

	public function __construct($widget, $protocol, $application) {
		parent::__construct($widget, $protocol, $application);
		$this->list = new GtkListStore(GObject::TYPE_STRING);
		$this->tree = $this->application->ui->get_widget('trace_tree');
		$this->tree->append_column(new GtkTreeViewColumn('Message', new GtkCellRendererText, 'text', 0));
		$this->tree->set_model($this->list);
	}

	public function addMessage($msg) {
		$this->list->append(array($msg));
	}

	public function showHide() {
		if ($this->visible) {
			$this->widget->hide();
			$this->visible = false;
		} else {
			$this->widget->show_all();
			$this->visible = true;
		}
	}
}
