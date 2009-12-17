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


		$this->editor->marker_define_pixmap(self::MARKER_BREAKPOINT, file_get_contents(GTKXDEBUGCLIENT_BASEPATH . '/view/breakpoint.xpm'));
		$this->editor->set_lexer(GtkScintilla::SCINTILLA_LEX_PHPSCRIPT);
		$this->editor->set_style_bits($this->editor->get_style_bits_needed());

		$defaultBack = $this->phpStyles[GtkScintilla::SCINTILLA_HTML_PHP_DEFAULT]['back'];
		foreach ($this->phpStyles as $style => $desc) {
			$this->setStyle($style, call_user_func_array(array($this, 'color'), $desc['fore']), call_user_func_array(array($this, 'color'), isset($desc['back']) ? $desc['back'] : $defaultBack));
		}
		$this->editor->set_keywords(4, join(' ', $this->phpKeywords));
	}

	public $phpStyles = array(
		GtkScintilla::SCINTILLA_HTML_PHP_DEFAULT => array(
			'fore' => array(0, 0, 0),
			'back' => array(255, 255, 255),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_HSTRING => array(
			'fore' => array(0, 255, 0),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_SIMPLESTRING => array(
			'fore' => array(0, 0, 255),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_WORD => array(
			'fore' => array(255, 0, 0),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_NUMBER => array(
			'fore' => array(255, 255, 0),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_VARIABLE => array(
			'fore' => array(0, 255, 255),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_COMMENT => array(
			'fore' => array(255, 0, 255),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_COMMENTLINE => array(
			'fore' => array(255, 0, 255),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_HSTRING_VARIABLE => array(
			'fore' => array(0, 255, 255),
		),
		GtkScintilla::SCINTILLA_HTML_PHP_OPERATOR => array(
			'fore' => array(127, 127, 127),
		),
	);

	// http://www.php.net/manual/en/reserved.keywords.php
	public $phpKeywords = array(
		// PHP Keywords
		'abstract', // (as of PHP 5)	
		'and',
		'array',	
		'as',	
		'break',	
		'case',	
		'catch', // (as of PHP 5)	 
		'cfunction', // (PHP 4 only), same as function (as per http://www.phpbuilder.com/lists/php3-list/199807/2411.php)
		'class',
		'clone', // (as of PHP 5)
		'const',
		'continue',
		'declare', 
		'default', 
		'do',
		'else',
		'elseif',
		'enddeclare',
		'endfor',
		'endforeach',
		'endif',
		'endswitch',
		'endwhile', 
		'extends', 
		'final', // (as of PHP 5)
		'for', 
		'foreach', 
		'function',
		'global',
		'goto', // (as of PHP 5.3)
		'if', 
		'implements', // (as of PHP 5)	
		'interface', // (as of PHP 5)
		'instanceof', // (as of PHP 5)
		'namespace', // (as of PHP 5.3)	
		'new', 
		'old_function', // PHP2/FI leftover (PHP 4 only)
		'or', 
		'private', // (as of PHP 5)
		'protected', // (as of PHP 5)
		'public', // (as of PHP 5)
		'static', 
		'switch',
		'throw', // (as of PHP 5)
		'try', // (as of PHP 5)	
		'use', //	
		'var', 
		'while',
		'xor',
		
		// Compile-time constants
		'__class__',
		'__dir__', // (as of PHP 5.3)
		'__file__',	
		'__function__',
		'__method__',
		'__namespace__', // (as of PHP 5.3)				

		// Language constructs
		'die', 
		'echo',
		'empty',
		'exit',
		'eval',
		'include',
		'include_once',
		'isset',
		'list',
		'require',
		'require_once', 
		'return', 
		'print',
		'unset',

		// some internal functions
		'define',
	);

	public function setStyle($style, $fore, $back, $size = false, $face = false) {
		$this->editor->style_set_fore($style, $fore);	
		$this->editor->style_set_back($style, $back);

		if ($size) {
			$this->editor->style_set_size($style, $size);
		}

		if ($face) {
			$this->editor->style_set_font($style, $face);
		}
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
		$this->editor->clear_all();
		$this->editor->insert_text(-1, $contents);
		$this->setCurrentLine(0);
		$this->editor->colourise(0, -1);
	}

	public function addBreakpoint($file, $line, $id) {
		$this->editor->marker_add($line, self::MARKER_BREAKPOINT);
	}

	public function removeBreakpoint($file, $line) {
		$this->editor->marker_delete($line, self::MARKER_BREAKPOINT);
	}

	public function setCurrentByStack($stack) {
		list($frame,) = $stack;
		if ($frame) {
			$this->filename = $frame['filename'];
			$this->setCurrentLine($frame['lineno']);
		}
	}
}
