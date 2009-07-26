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
class View_Editor extends View_Widget {
	const MARKER_CURRENT = 1;
	const MARKER_CURRENT_HIGHLIGHT = 2;
	const MARKER_BREAKPOINT = 4;

	public function color($r, $g, $b) {
		return $r | ($g << 8) | ($b << 16);
	}

	public function mask($num) {
		return 1 << $num;
	}

	public function __construct($widget, $protocol, $application) {
		parent::__construct($widget, $protocol, $application);
		$this->editor = new GtkScintilla;
		$widget->pack_start($this->editor, true, true);
		$this->editor->marker_define(self::MARKER_CURRENT, 2);
		$this->editor->marker_set_fore(self::MARKER_CURRENT, $this->color(0, 255, 0));
		$this->editor->marker_set_back(self::MARKER_CURRENT, $this->color(0, 255, 0));

		$this->editor->marker_define(self::MARKER_CURRENT_HIGHLIGHT, 0);
		$this->editor->marker_set_back(self::MARKER_CURRENT_HIGHLIGHT, $this->color(0, 255, 0));
		$this->editor->set_margin_mask_n(1, ~$this->mask(self::MARKER_CURRENT_HIGHLIGHT));

		$this->editor->set_margin_sensitive_n(1, true);
		$this->editor->connect('margin_click', array($this, 'marginClicked'));


		$this->editor->marker_define(self::MARKER_BREAKPOINT, 0);
		$this->editor->marker_set_fore(self::MARKER_BREAKPOINT, $this->color(255, 0, 0));
		$this->editor->marker_set_back(self::MARKER_BREAKPOINT, $this->color(255, 0, 0));
	}

	public function setCurrentLine($num) {
		if (isset($this->currentLine)) {
			$this->editor->marker_delete($this->currentLine, self::MARKER_CURRENT);
			$this->editor->marker_delete($this->currentLine, self::MARKER_CURRENT_HIGHLIGHT);
		}
		$this->currentLine = $num;

		$this->editor->marker_add($this->currentLine, self::MARKER_CURRENT);
		$this->editor->marker_add($this->currentLine, self::MARKER_CURRENT_HIGHLIGHT);
	}

	public function marginClicked($editor, $mods, $position, $margin) {
		$line = $this->editor->line_from_position($position);
		if ($this->editor->marker_get($line) & $this->mask(self::MARKER_BREAKPOINT)) {
			unset($this->protocol->breakpoints[$this->filename . ':' . $line]);
		} else {
			$this->protocol->breakpoints[$this->filename . ':' . $line] = true;
		}
	}

	public function setSource($filename, $contents) {
		$this->filename = $filename;
		$this->editor->insert_text(-1, $contents);
		$this->setCurrentLine(0);
	}

	public function addBreakpoint($file, $line, $id) {
		$this->editor->marker_add($line, self::MARKER_BREAKPOINT);
	}

	public function removeBreakpoint($file, $line) {
		$this->editor->marker_delete($line, self::MARKER_BREAKPOINT);
	}
}
