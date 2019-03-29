<?php
/**
 * CSSTidy Optimising PHP Class
 *
 * @package TablePress
 * @subpackage CSS
 * @author Florian Schmitz, Brett Zamir, Nikolay Matsievsky, Cedric Morin, Christopher Finke, Mark Scherer, Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * CSSTidy - CSS Parser and Optimiser
 *
 * CSS Optimising Class
 * This class optimises CSS data generated by CSSTidy.
 *
 * Copyright 2005, 2006, 2007 Florian Schmitz
 *
 * This file is part of CSSTidy.
 *
 *   CSSTidy is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or
 *   (at your option) any later version.
 *
 *   CSSTidy is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 *
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @license https://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @package CSSTidy
 * @author Florian Schmitz (floele at gmail dot com) 2005-2007
 * @author Brett Zamir (brettz9 at yahoo dot com) 2007
 * @author Nikolay Matsievsky (speed at webo dot name) 2009-2010
 * @author Cedric Morin (cedric at yterium dot com) 2010-2012
 */

/**
 * CSS Optimising Class
 *
 * This class optimises CSS data generated by CSSTidy.
 *
 * @package CSSTidy
 * @author Florian Schmitz (floele at gmail dot com) 2005-2006
 * @version 1.0
 */
class TablePress_CSSTidy_optimise {

	/**
	 * CSSTidy instance.
	 *
	 * @since 1.0.0
	 * @var CSSTidy
	 */
	public $parser;

	/**
	 * The parsed CSS.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $css = array();

	/**
	 * The current sub-value.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $sub_value = '';

	/**
	 * The current at rule (@media).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $at = '';

	/**
	 * The current selector.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $selector = '';

	/**
	 * The current property.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $property = '';

	/**
	 * The current value.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $value = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CSSTidy $css Instance of the CSSTidy class.
	 */
	public function __construct( $css ) {
		$this->parser = $css;
		$this->css = &$css->css;
		$this->sub_value = &$css->sub_value;
		$this->at = &$css->at;
		$this->selector = &$css->selector;
		$this->property = &$css->property;
		$this->value = &$css->value;
	}

	/**
	 * Optimises $css after parsing.
	 *
	 * @since 1.0.0
	 */
	public function postparse() {
		if ( $this->parser->get_cfg( 'preserve_css' ) ) {
			return;
		}

		if ( 2 === (int) $this->parser->get_cfg( 'merge_selectors' ) ) {
			foreach ( $this->css as $medium => $value ) {
				$this->merge_selectors( $this->css[ $medium ] );
			}
		}

		if ( $this->parser->get_cfg( 'discard_invalid_selectors' ) ) {
			foreach ( $this->css as $medium => $value ) {
				$this->discard_invalid_selectors( $this->css[ $medium ] );
			}
		}

		if ( $this->parser->get_cfg( 'optimise_shorthands' ) > 0 ) {
			foreach ( $this->css as $medium => $value ) {
				foreach ( $value as $selector => $value1 ) {
					$this->css[ $medium ][ $selector ] = $this->merge_4value_shorthands( $this->css[ $medium ][ $selector ] );

					if ( $this->parser->get_cfg( 'optimise_shorthands' ) < 2 ) {
						continue;
					}

					$this->css[ $medium ][ $selector ] = $this->merge_font( $this->css[ $medium ][ $selector ] );

					if ( $this->parser->get_cfg( 'optimise_shorthands' ) < 3 ) {
						continue;
					}

					$this->css[ $medium ][ $selector ] = $this->merge_bg( $this->css[ $medium ][ $selector ] );
					if ( empty( $this->css[ $medium ][ $selector ] ) ) {
						unset( $this->css[ $medium ][ $selector ] );
					}
				}
			}
		}
	}

	/**
	 * Optimises values
	 *
	 * @since 1.0.0
	 */
	public function value() {
		$shorthands = &$this->parser->data['csstidy']['shorthands'];

		// Optimise shorthand properties.
		if ( isset( $shorthands[ $this->property ] ) && $this->parser->get_cfg( 'optimise_shorthands' ) > 0 ) {
			$temp = $this->shorthand( $this->value ); // FIXME - move
			if ( $temp !== $this->value ) {
				$this->parser->log( 'Optimised shorthand notation (' . $this->property . '): Changed "' . $this->value . '" to "' . $temp . '"', 'Information' );
			}
			$this->value = $temp;
		}

		// Remove whitespace at !important
		if ( $this->value !== $this->compress_important( $this->value ) ) {
			$this->parser->log( 'Optimised !important', 'Information' );
		}
	}

	/**
	 * Optimises shorthands.
	 *
	 * @since 1.0.0
	 */
	public function shorthands() {
		$shorthands = &$this->parser->data['csstidy']['shorthands'];

		if ( ! $this->parser->get_cfg( 'optimise_shorthands' ) || $this->parser->get_cfg( 'preserve_css' ) ) {
			return;
		}

		if ( 'font' === $this->property && $this->parser->get_cfg( 'optimise_shorthands' ) > 1 ) {
			$this->css[ $this->at ][ $this->selector ]['font'] = '';
			$this->parser->merge_css_blocks( $this->at, $this->selector, $this->dissolve_short_font( $this->value ) );
		}
		if ( 'background' === $this->property && $this->parser->get_cfg( 'optimise_shorthands' ) > 2 ) {
			$this->css[ $this->at ][ $this->selector ]['background'] = '';
			$this->parser->merge_css_blocks( $this->at, $this->selector, $this->dissolve_short_bg( $this->value ) );
		}
		if ( isset( $shorthands[ $this->property ] ) ) {
			$this->parser->merge_css_blocks( $this->at, $this->selector, $this->dissolve_4value_shorthands( $this->property, $this->value ) );
			if ( is_array( $shorthands[ $this->property ] ) ) {
				$this->css[ $this->at ][ $this->selector ][ $this->property ] = '';
			}
		}
	}

	/**
	 * Optimises a sub-value.
	 *
	 * @since 1.0.0
	 */
	public function subvalue() {
		$replace_colors = &$this->parser->data['csstidy']['replace_colors'];

		$this->sub_value = trim( $this->sub_value );
		if ( '' === $this->sub_value ) { // caution : '0'
			return;
		}

		$important = '';
		if ( $this->parser->is_important( $this->sub_value ) ) {
			$important = ' !important';
		}
		$this->sub_value = $this->parser->gvw_important( $this->sub_value );

		// Compress font-weight.
		if ( 'font-weight' === $this->property && $this->parser->get_cfg( 'compress_font-weight' ) ) {
			if ( 'bold' === $this->sub_value ) {
				$this->sub_value = '700';
				$this->parser->log( 'Optimised font-weight: Changed "bold" to "700"', 'Information' );
			} elseif ( 'normal' === $this->sub_value ) {
				$this->sub_value = '400';
				$this->parser->log( 'Optimised font-weight: Changed "normal" to "400"', 'Information' );
			}
		}

		$temp = $this->compress_numbers( $this->sub_value );
		if ( 0 !== strcasecmp( $temp, $this->sub_value ) ) {
			if ( strlen( $temp ) > strlen( $this->sub_value ) ) {
				$this->parser->log( 'Fixed invalid number: Changed "' . $this->sub_value . '" to "' . $temp . '"', 'Warning' );
			} else {
				$this->parser->log( 'Optimised number: Changed "' . $this->sub_value . '" to "' . $temp . '"', 'Information' );
			}
			$this->sub_value = $temp;
		}
		if ( $this->parser->get_cfg( 'compress_colors' ) ) {
			$temp = $this->cut_color( $this->sub_value );
			if ( $temp !== $this->sub_value ) {
				if ( isset( $replace_colors[ $this->sub_value ] ) ) {
					$this->parser->log( 'Fixed invalid color name: Changed "' . $this->sub_value . '" to "' . $temp . '"', 'Warning' );
				} else {
					$this->parser->log( 'Optimised color: Changed "' . $this->sub_value . '" to "' . $temp . '"', 'Information' );
				}
				$this->sub_value = $temp;
			}
		}
		$this->sub_value .= $important;
	}

	/**
	 * Compresses shorthand values.
	 *
	 * Example: `margin: 1px 1px 1px 1px` will become `margin: 1px`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Shorthand value.
	 * @return string Compressed value.
	 */
	public function shorthand( $value ) {
		$important = '';
		if ( $this->parser->is_important( $value ) ) {
			$values = $this->parser->gvw_important( $value );
			$important = ' !important';
		} else {
			$values = $value;
		}

		$values = explode( ' ', $values );
		switch ( count( $values ) ) {
			case 4:
				if ( $values[0] === $values[1] && $values[0] === $values[2] && $values[0] === $values[3] ) {
					return $values[0] . $important;
				} elseif ( $values[1] === $values[3] && $values[0] === $values[2] ) {
					return $values[0] . ' ' . $values[1] . $important;
				} elseif ( $values[1] === $values[3] ) {
					return $values[0] . ' ' . $values[1] . ' ' . $values[2] . $important;
				}
				break;
			case 3:
				if ( $values[0] === $values[1] && $values[0] === $values[2] ) {
					return $values[0] . $important;
				} elseif ( $values[0] === $values[2] ) {
					return $values[0] . ' ' . $values[1] . $important;
				}
				break;
			case 2:
				if ( $values[0] === $values[1] ) {
					return $values[0] . $important;
				}
				break;
		}

		return $value;
	}

	/**
	 * Removes unnecessary whitespace in ! important.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string String.
	 * @return string Cleaned string.
	 */
	public function compress_important( &$string ) {
		if ( $this->parser->is_important( $string ) ) {
			$string = $this->parser->gvw_important( $string ) . ' !important';
		}
		return $string;
	}

	/**
	 * Color compression function. Converts all rgb() values to #-values and uses the short-form if possible. Also replaces 4 color names by #-values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $color Color value.
	 * @return string Compressed color.
	 */
	public function cut_color( $color ) {
		$replace_colors = &$this->parser->data['csstidy']['replace_colors'];

		// If it's a string, don't touch!
		if ( 0 === strncmp( $color, "'", 1 ) || 0 === strncmp( $color, '"', 1 ) ) {
			return $color;
		}

		// Complex gradient expressions
		if ( false !== strpos( $color, '(' ) && 0 !== strncmp( $color, 'rgb(', 4 ) ) {
			// Don't touch properties within MSIE filters, those are to sensitive.
			if ( false !== stripos( $color, 'progid:' ) ) {
				return $color;
			}
			preg_match_all( ',rgb\([^)]+\),i', $color, $matches, PREG_SET_ORDER );
			if ( count( $matches ) ) {
				foreach ( $matches as $m ) {
					$color = str_replace( $m[0], $this->cut_color( $m[0] ), $color );
				}
			}
			preg_match_all( ',#[0-9a-f]{6}(?=[^0-9a-f]),i', $color, $matches, PREG_SET_ORDER );
			if ( count( $matches ) ) {
				foreach ( $matches as $m ) {
					$color = str_replace( $m[0], $this->cut_color( $m[0] ), $color );
				}
			}
			return $color;
		}

		// rgb(0,0,0) -> #000000 (or #000 in this case later)
		if ( 0 === strncasecmp( $color, 'rgb(', 4 ) ) {
			$color_tmp = substr( $color, 4, strlen( $color ) - 5 );
			$color_tmp = explode( ',', $color_tmp );
			for ( $i = 0; $i < count( $color_tmp ); $i++ ) {
				$color_tmp[ $i ] = trim( $color_tmp[ $i ] );
				if ( '%' === substr( $color_tmp[ $i ], -1 ) ) {
					$color_tmp[ $i ] = round( ( 255 * $color_tmp[ $i ] ) / 100 );
				}
				if ( $color_tmp[ $i ] > 255 ) {
					$color_tmp[ $i ] = 255;
				}
			}
			$color = '#';
			for ( $i = 0; $i < 3; $i++ ) {
				if ( $color_tmp[ $i ] < 16 ) {
					$color .= '0' . dechex( $color_tmp[ $i ] );
				} else {
					$color .= dechex( $color_tmp[ $i ] );
				}
			}
		}

		// Fix bad color names.
		if ( isset( $replace_colors[ strtolower( $color ) ] ) ) {
			$color = $replace_colors[ strtolower( $color ) ];
		}

		// #aabbcc -> #abc
		if ( 7 === strlen( $color ) ) {
			$color_temp = strtolower( $color );
			if ( '#' === $color_temp[0] && $color_temp[1] === $color_temp[2] && $color_temp[3] === $color_temp[4] && $color_temp[5] === $color_temp[6] ) {
				$color = '#' . $color[1] . $color[3] . $color[5];
			}
		}

		switch ( strtolower( $color ) ) {
			/* color name -> hex code */
			case 'black':
				return '#000';
			case 'fuchsia':
				return '#f0f';
			case 'white':
				return '#fff';
			case 'yellow':
				return '#ff0';

			/* hex code -> color name */
			case '#800000':
				return 'maroon';
			case '#ffa500':
				return 'orange';
			case '#808000':
				return 'olive';
			case '#800080':
				return 'purple';
			case '#008000':
				return 'green';
			case '#000080':
				return 'navy';
			case '#008080':
				return 'teal';
			case '#c0c0c0':
				return 'silver';
			case '#808080':
				return 'gray';
			case '#f00':
				return 'red';
		}

		return $color;
	}

	/**
	 * Compresses numbers (ie. 1.0 becomes 1 or 1.100 becomes 1.1).
	 *
	 * @since 1.0.0
	 *
	 * @param string $subvalue Value.
	 * @return string Compressed value.
	 */
	public function compress_numbers( $subvalue ) {
		$unit_values = &$this->parser->data['csstidy']['unit_values'];
		$color_values = &$this->parser->data['csstidy']['color_values'];

		// for font:1em/1em sans-serif...;
		if ( 'font' === $this->property ) {
			$temp = explode( '/', $subvalue );
		} else {
			$temp = array( $subvalue );
		}

		for ( $l = 0; $l < count( $temp ); $l++ ) {
			// If we are not dealing with a number at this point, do not optimize anything.
			$number = $this->AnalyseCssNumber( $temp[ $l ] );
			if ( false === $number ) {
				return $subvalue;
			}

			// Fix bad colors.
			if ( in_array( $this->property, $color_values ) ) {
				if ( 3 === strlen( $temp[ $l ] ) || 6 === strlen( $temp[ $l ] ) ) {
					$temp[ $l ] = '#' . $temp[ $l ];
				} else {
					$temp[ $l ] = '0';
				}
				continue;
			}

			if ( abs( $number[0] ) > 0 ) {
				if ( '' === $number[1] && in_array( $this->property, $unit_values, true ) ) {
					$number[1] = 'px';
				}
			} elseif ( 's' !== $number[1] && 'ms' !== $number[1] ) {
				$number[1] = '';
			}

			$temp[ $l ] = $number[0] . $number[1];
		}

		return ( count( $temp ) > 1 ) ? $temp[0] . '/' . $temp[1] : $temp[0];
	}

	/**
	 * Checks if a given string is a CSS valid number. If it is, an array containing the value and unit is returned.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string String.
	 * @return array ('unit' if unit is found or '' if no unit exists, number value) or false if no number.
	 */
	public function analyseCssNumber( $string ) {
		// most simple checks first
		if ( 0 === strlen( $string ) || ctype_alpha( $string[0] ) ) {
			return false;
		}

		$units = &$this->parser->data['csstidy']['units'];
		$return = array( 0, '' );

		$return[0] = floatval( $string );
		if ( abs( $return[0] ) > 0 && abs( $return[0] ) < 1 ) {
			if ( $return[0] < 0 ) {
				$return[0] = '-' . ltrim( substr( $return[0], 1 ), '0' );
			} else {
				$return[0] = ltrim( $return[0], '0' );
			}
		}

		// Look for unit and split from value if exists
		foreach ( $units as $unit ) {
			$expectUnitAt = strlen( $string ) - strlen( $unit );
			if ( ! ( $unitInString = stristr( $string, $unit ) ) ) { // mb_strpos() fails with "false"
				continue;
			}
			$actualPosition = strpos( $string, $unitInString );
			if ( $expectUnitAt === $actualPosition ) {
				$return[1] = $unit;
				$string = substr( $string, 0, - strlen( $unit ) );
				break;
			}
		}
		if ( ! is_numeric( $string ) ) {
			return false;
		}
		return $return;
	}

	/**
	 * Merges selectors with same properties. Example: a{color:red} b{color:red} -> a,b{color:red}
	 * Very basic and has at least one bug. Hopefully there is a replacement soon.
	 *
	 * @since 1.0.0
	 *
	 * @param array $array
	 * @return array
	 */
	public function merge_selectors( array &$array ) {
		$css = $array;
		foreach ( $css as $key => $value ) {
			if ( ! isset( $css[ $key ] ) ) {
				continue;
			}

			// Check if properties also exist in another selector.
			$keys = array();
			// PHP bug (?) without $css = $array; here
			foreach ( $css as $selector => $vali ) {
				if ( $selector === $key ) {
					continue;
				}

				if ( $css[ $key ] === $vali ) {
					$keys[] = $selector;
				}
			}

			if ( ! empty( $keys ) ) {
				$newsel = $key;
				unset( $css[ $key ] );
				foreach ( $keys as $selector ) {
					unset( $css[ $selector ] );
					$newsel .= ',' . $selector;
				}
				$css[ $newsel ] = $value;
			}
		}
		$array = $css;
	}

	/**
	 * Removes invalid selectors and their corresponding rule-sets as
	 * defined by 4.1.7 in REC-CSS2. This is a very rudimentary check
	 * and should be replaced by a full-blown parsing algorithm or
	 * regular expression.
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $array [description]
	 */
	public function discard_invalid_selectors( &$array ) {
		foreach ( $array as $selector => $decls ) {
			$ok = true;
			$selectors = array_map( 'trim', explode( ',', $selector ) );
			foreach ( $selectors as $s ) {
				$simple_selectors = preg_split( '/\s*[+>~\s]\s*/', $s );
				foreach ( $simple_selectors as $ss ) {
					if ( '' === $ss ) {
						$ok = false;
					}
					// could also check $ss for internal structure,
					// but that probably would be too slow
				}
			}
			if ( ! $ok ) {
				unset( $array[ $selector ] );
			}
		}
	}

	/**
	 * Dissolves properties like padding:10px 10px 10px to padding-top:10px;padding-bottom:10px;...
	 *
	 * @since 1.0.0
	 *
	 * @param string $property [description]
	 * @param string $value    [description]
	 *
	 * @return [type] [description]
	 */
	public function dissolve_4value_shorthands( $property, $value ) {
		$return = array();

		$shorthands = &$this->parser->data['csstidy']['shorthands'];
		if ( ! is_array( $shorthands[ $property ] ) ) {
			$return[ $property ] = $value;
			return $return;
		}

		$important = '';
		if ( $this->parser->is_important( $value ) ) {
			$value = $this->parser->gvw_important( $value );
			$important = ' !important';
		}
		$values = explode( ' ', $value );

		if ( 4 === count( $values ) ) {
			for ( $i = 0; $i < 4; $i++ ) {
				$return[ $shorthands[ $property ][ $i ] ] = $values[ $i ] . $important;
			}
		} elseif ( 3 === count( $values ) ) {
			$return[ $shorthands[ $property ][0] ] = $values[0] . $important;
			$return[ $shorthands[ $property ][1] ] = $values[1] . $important;
			$return[ $shorthands[ $property ][3] ] = $values[1] . $important;
			$return[ $shorthands[ $property ][2] ] = $values[2] . $important;
		} elseif ( 2 === count( $values ) ) {
			for ( $i = 0; $i < 4; $i++ ) {
				$return[ $shorthands[ $property ][ $i ] ] = ( 0 !== $i % 2 ) ? $values[1] . $important : $values[0] . $important;
			}
		} else {
			for ( $i = 0; $i < 4; $i++ ) {
				$return[ $shorthands[ $property ][ $i ] ] = $values[0] . $important;
			}
		}

		return $return;
	}

	/**
	 * Explodes a string as explode() does, however, not if $sep is escaped or within a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $sep    Separator.
	 * @param string  $string String.
	 * @return array
	 */
	public function explode_ws( $sep, $string ) {
		$status = 'st';
		$to = '';

		$output = array();
		$num = 0;
		for ( $i = 0, $len = strlen( $string ); $i < $len; $i++ ) {
			switch ( $status ) {
				case 'st':
					if ( $string[ $i ] === $sep && ! $this->parser->escaped( $string, $i ) ) {
						++$num;
					} elseif ( '"' === $string[ $i ] || "'" === $string[ $i ] || '(' === $string[ $i ] && ! $this->parser->escaped( $string, $i ) ) {
						$status = 'str';
						$to = ( '(' === $string[ $i ] ) ? ')' : $string[ $i ];
						( isset( $output[ $num ] ) ) ? $output[ $num ] .= $string[ $i ] : $output[ $num ] = $string[ $i ];
					} else {
						( isset( $output[ $num ] ) ) ? $output[ $num ] .= $string[ $i ] : $output[ $num ] = $string[ $i ];
					}
					break;

				case 'str':
					if ( $string[ $i ] === $to && ! $this->parser->escaped( $string, $i ) ) {
						$status = 'st';
					}
					( isset( $output[ $num ] ) ) ? $output[ $num ] .= $string[ $i ] : $output[ $num ] = $string[ $i ];
					break;
			}
		}

		if ( isset( $output[0] ) ) {
			return $output;
		} else {
			return array( $output );
		}
	}

	/**
	 * Merges Shorthand properties again, the opposite of dissolve_4value_shorthands().
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $array [description]
	 * @return [type] [description]
	 */
	public function merge_4value_shorthands( $array ) {
		$return = $array;
		$shorthands = &$this->parser->data['csstidy']['shorthands'];

		foreach ( $shorthands as $key => $value ) {
			if ( isset( $array[ $value[0] ] ) && isset( $array[ $value[1] ] )
				&& isset( $array[ $value[2] ] ) && isset( $array[ $value[3] ] ) && 0 !== $value ) {
				$return[ $key ] = '';

				$important = '';
				for ( $i = 0; $i < 4; $i++ ) {
					$val = $array[ $value[ $i ] ];
					if ( $this->parser->is_important( $val ) ) {
						$important = ' !important';
						$return[ $key ] .= $this->parser->gvw_important( $val ) . ' ';
					} else {
						$return[ $key ] .= $val . ' ';
					}
					unset( $return[ $value[ $i ] ] );
				}
				$return[ $key ] = $this->shorthand( trim( $return[ $key ] . $important ) );
			}
		}
		return $return;
	}

	/**
	 * Dissolve background property.
	 *
	 * @TODO Full CSS3 compliance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $str_value String value.
	 * @return array Array.
	 */
	public function dissolve_short_bg( $str_value ) {
		// Don't try to explose background gradient!
		if ( false !== stripos( $str_value, 'gradient(' ) ) {
			return array( 'background' => $str_value );
		}

		$background_prop_default = &$this->parser->data['csstidy']['background_prop_default'];
		$repeat = array( 'repeat', 'repeat-x', 'repeat-y', 'no-repeat', 'space' );
		$attachment = array( 'scroll', 'fixed', 'local' );
		$clip = array( 'border', 'padding' );
		$origin = array( 'border', 'padding', 'content' );
		$pos = array( 'top', 'center', 'bottom', 'left', 'right' );
		$important = '';
		$return = array(
			'background-image'      => null,
			'background-size'       => null,
			'background-repeat'     => null,
			'background-position'   => null,
			'background-attachment' => null,
			'background-clip'       => null,
			'background-origin'     => null,
			'background-color'      => null,
		);

		if ( $this->parser->is_important( $str_value ) ) {
			$important = ' !important';
			$str_value = $this->parser->gvw_important( $str_value );
		}

		$have = array();
		$str_value = $this->explode_ws( ',', $str_value );
		for ( $i = 0; $i < count( $str_value ); $i++ ) {
			$have['clip'] = false;
			$have['pos'] = false;
			$have['color'] = false;
			$have['bg'] = false;

			if ( is_array( $str_value[ $i ] ) ) {
				$str_value[ $i ] = $str_value[ $i ][0];
			}
			$str_value[ $i ] = $this->explode_ws( ' ', trim( $str_value[ $i ] ) );

			for ( $j = 0; $j < count( $str_value[ $i ] ); $j++ ) {
				if ( false === $have['bg'] && ( 'url(' === substr( $str_value[ $i ][ $j ], 0, 4 ) || 'none' === $str_value[ $i ][ $j ] ) ) {
					$return['background-image'] .= $str_value[ $i ][ $j ] . ',';
					$have['bg'] = true;
				} elseif ( in_array( $str_value[ $i ][ $j ], $repeat, true ) ) {
					$return['background-repeat'] .= $str_value[ $i ][ $j ] . ',';
				} elseif ( in_array( $str_value[ $i ][ $j ], $attachment, true ) ) {
					$return['background-attachment'] .= $str_value[ $i ][ $j ] . ',';
				} elseif ( in_array( $str_value[ $i ][ $j ], $clip, true ) && ! $have['clip'] ) {
					$return['background-clip'] .= $str_value[ $i ][ $j ] . ',';
					$have['clip'] = true;
				} elseif ( in_array( $str_value[ $i ][ $j ], $origin, true ) ) {
					$return['background-origin'] .= $str_value[ $i ][ $j ] . ',';
				} elseif ( '(' === $str_value[ $i ][ $j ][0] ) {
					$return['background-size'] .= substr( $str_value[ $i ][ $j ], 1, -1 ) . ',';
				} elseif ( in_array( $str_value[ $i ][ $j ], $pos, true ) || is_numeric( $str_value[ $i ][ $j ][0] ) || null === $str_value[ $i ][ $j ][0] || '-' === $str_value[ $i ][ $j ][0] || '.' === $str_value[ $i ][ $j ][0] ) {
					$return['background-position'] .= $str_value[ $i ][ $j ];
					if ( ! $have['pos'] ) {
						$return['background-position'] .= ' ';
					} else {
						$return['background-position'] .= ',';
					}
					$have['pos'] = true;
				} elseif ( ! $have['color'] ) {
					$return['background-color'] .= $str_value[ $i ][ $j ] . ',';
					$have['color'] = true;
				}
			}
		}

		foreach ( $background_prop_default as $bg_prop => $default_value ) {
			if ( null !== $return[ $bg_prop ] ) {
				$return[ $bg_prop ] = substr( $return[ $bg_prop ], 0, -1 ) . $important;
			} else {
				$return[ $bg_prop ] = $default_value . $important;
			}
		}
		return $return;
	}

	/**
	 * Merges all background properties.
	 *
	 * @TODO Full CSS3 compliance.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input_css CSS.
	 * @return array Array.
	 */
	public function merge_bg( array $input_css ) {
		$background_prop_default = &$this->parser->data['csstidy']['background_prop_default'];
		// Max number of background images. CSS3 not yet fully implemented.
		$number_of_values = @max( count( $this->explode_ws( ',', $input_css['background-image'] ) ), count( $this->explode_ws( ',', $input_css['background-color'] ) ), 1 );
		// Array with background images to check if BG image exists.
		$bg_img_array = @$this->explode_ws( ',', $this->parser->gvw_important( $input_css['background-image'] ) );
		$new_bg_value = '';
		$important = '';

		// If background properties is here and not empty, don't try anything.
		if ( isset( $input_css['background'] ) && $input_css['background'] ) {
			return $input_css;
		}

		for ( $i = 0; $i < $number_of_values; $i++ ) {
			foreach ( $background_prop_default as $bg_property => $default_value ) {
				// Skip if property does not exist.
				if ( ! isset( $input_css[ $bg_property ] ) ) {
					continue;
				}

				$cur_value = $input_css[ $bg_property ];
				// Skip all optimisation if gradient() somewhere.
				if ( false !== stripos( $cur_value, 'gradient(' ) ) {
					return $input_css;
				}

				// Skip some properties if there is no background image.
				if ( ( ! isset( $bg_img_array[ $i ] ) || 'none' === $bg_img_array[ $i ] )
					&& ( 'background-size' === $bg_property || 'background-position' === $bg_property || 'background-attachment' === $bg_property || 'background-repeat' === $bg_property ) ) {
					continue;
				}

				// Remove !important.
				if ( $this->parser->is_important( $cur_value ) ) {
					$important = ' !important';
					$cur_value = $this->parser->gvw_important( $cur_value );
				}

				// Do not add default values.
				if ( $cur_value === $default_value ) {
					continue;
				}

				$temp = $this->explode_ws( ',', $cur_value );

				if ( isset( $temp[ $i ] ) ) {
					if ( 'background-size' === $bg_property ) {
						$new_bg_value .= '(' . $temp[ $i ] . ') ';
					} else {
						$new_bg_value .= $temp[ $i ] . ' ';
					}
				}
			}

			$new_bg_value = trim( $new_bg_value );
			if ( $i !== $number_of_values - 1 ) {
				$new_bg_value .= ',';
			}
		}

		// Delete all background properties.
		foreach ( $background_prop_default as $bg_property => $default_value ) {
			unset( $input_css[ $bg_property ] );
		}

		// Add new background property.
		if ( '' !== $new_bg_value ) {
			$input_css['background'] = $new_bg_value . $important;
		} elseif ( isset( $input_css['background'] ) ) {
			$input_css['background'] = 'none';
		}

		return $input_css;
	}

	/**
	 * Dissolve font property.
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $str_value [description]
	 * @return [type] [description]
	 */
	public function dissolve_short_font( $str_value ) {
		$font_prop_default = &$this->parser->data['csstidy']['font_prop_default'];
		$font_weight = array( 'normal', 'bold', 'bolder', 'lighter', 100, 200, 300, 400, 500, 600, 700, 800, 900 );
		$font_variant = array( 'normal', 'small-caps' );
		$font_style = array( 'normal', 'italic', 'oblique' );
		$important = '';
		$return = array(
			'font-style'   => null,
			'font-variant' => null,
			'font-weight'  => null,
			'font-size'    => null,
			'line-height'  => null,
			'font-family'  => null,
		);

		if ( $this->parser->is_important( $str_value ) ) {
			$important = ' !important';
			$str_value = $this->parser->gvw_important( $str_value );
		}

		$have = array();
		$have['style'] = false;
		$have['variant'] = false;
		$have['weight'] = false;
		$have['size'] = false;
		// Detects if font-family consists of several words w/o quotes.
		$multiwords = false;

		// Workaround with multiple font-families.
		$str_value = $this->explode_ws( ',', trim( $str_value ) );

		$str_value[0] = $this->explode_ws( ' ', trim( $str_value[0] ) );

		for ( $j = 0; $j < count( $str_value[0] ); $j++ ) {
			if ( false === $have['weight'] && in_array( $str_value[0][ $j ], $font_weight ) ) {
				$return['font-weight'] = $str_value[0][ $j ];
				$have['weight'] = true;
			} elseif ( false === $have['variant'] && in_array( $str_value[0][ $j ], $font_variant ) ) {
				$return['font-variant'] = $str_value[0][ $j ];
				$have['variant'] = true;
			} elseif ( false === $have['style'] && in_array( $str_value[0][ $j ], $font_style ) ) {
				$return['font-style'] = $str_value[0][ $j ];
				$have['style'] = true;
			} elseif ( false === $have['size'] && ( is_numeric( $str_value[0][ $j ][0] ) || null === $str_value[0][ $j ][0] || '.' === $str_value[0][ $j ][0] ) ) {
				$size = $this->explode_ws( '/', trim( $str_value[0][ $j ] ) );
				$return['font-size'] = $size[0];
				if ( isset( $size[1] ) ) {
					$return['line-height'] = $size[1];
				} else {
					$return['line-height'] = ''; // Don't add 'normal'!
				}
				$have['size'] = true;
			} else {
				if ( isset( $return['font-family'] ) ) {
					$return['font-family'] .= ' ' . $str_value[0][ $j ];
					$multiwords = true;
				} else {
					$return['font-family'] = $str_value[0][ $j ];
				}
			}
		}
		// Add quotes if we have several words in font-family.
		if ( false !== $multiwords ) {
			$return['font-family'] = '"' . $return['font-family'] . '"';
		}
		$i = 1;
		while ( isset( $str_value[ $i ] ) ) {
			$return['font-family'] .= ',' . trim( $str_value[ $i ] );
			$i++;
		}

		// Fix for font-size 100 and higher.
		if ( false === $have['size'] && isset( $return['font-weight'] ) && is_numeric( $return['font-weight'][0] ) ) {
			$return['font-size'] = $return['font-weight'];
			unset( $return['font-weight'] );
		}

		foreach ( $font_prop_default as $font_prop => $default_value ) {
			if ( null !== $return[ $font_prop ] ) {
				$return[ $font_prop ] = $return[ $font_prop ] . $important;
			} else {
				$return[ $font_prop ] = $default_value . $important;
			}
		}
		return $return;
	}

	/**
	 * Merges all fonts properties.
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $input_css [description]
	 * @return [type] [description]
	 */
	public function merge_font( $input_css ) {
		$font_prop_default = &$this->parser->data['csstidy']['font_prop_default'];
		$new_font_value = '';
		$important = '';
		// Skip if no font-family and font-size set.
		if ( isset( $input_css['font-family'] ) && isset( $input_css['font-size'] ) && 'inherit' !== $input_css['font-family'] ) {
			// Fix several words in font-family - add quotes.
			if ( isset( $input_css['font-family'] ) ) {
				$families = explode( ',', $input_css['font-family'] );
				$result_families = array();
				foreach ( $families as $family ) {
					$family = trim( $family );
					$len = strlen( $family );
					if ( strpos( $family, ' ' ) &&
						! ( ( '"' === $family[0] && '"' === $family[ $len - 1 ] ) ||
						( "'" === $family[0] && "'" === $family[ $len - 1 ] ) ) ) {
						$family = '"' . $family . '"';
					}
					$result_families[] = $family;
				}
				$input_css['font-family'] = implode( ',', $result_families );
			}
			foreach ( $font_prop_default as $font_property => $default_value ) {
				// Skip if property does not exist.
				if ( ! isset( $input_css[ $font_property ] ) ) {
					continue;
				}

				$cur_value = $input_css[ $font_property ];

				// Skip if default value is used.
				if ( $cur_value === $default_value ) {
					continue;
				}

				// Remove !important.
				if ( $this->parser->is_important( $cur_value ) ) {
					$important = ' !important';
					$cur_value = $this->parser->gvw_important( $cur_value );
				}

				$new_font_value .= $cur_value;
				// Add delimiter.
				$new_font_value .= ( 'font-size' === $font_property && isset( $input_css['line-height'] ) ) ? '/' : ' ';
			}

			$new_font_value = trim( $new_font_value );

			// Delete all font properties.
			foreach ( $font_prop_default as $font_property => $default_value ) {
				if ( 'font' !== $font_property || ! $new_font_value ) {
					unset( $input_css[ $font_property ] );
				}
			}

			// Add new font property.
			if ( '' !== $new_font_value ) {
				$input_css['font'] = $new_font_value . $important;
			}
		}

		return $input_css;
	}

} // class TablePress_CSSTidy_optimise

/**
 * Sanitization class
 */
class TablePress_CSSTidy_custom_sanitize extends TablePress_CSSTidy_optimise {

	/**
	 * [$props_w_urls description]
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $props_w_urls = array( 'background', 'background-image', 'list-style', 'list-style-image' );

	/**
	 * [$allowed_protocols description]
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $allowed_protocols = array( 'http', 'https' );

	/**
	 * [__construct description]
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $css [description]
	 */
	public function __construct( $css ) {
		return parent::__construct( $css );
	}

	/**
	 * [postparse description]
	 *
	 * @since 1.0.0
	 *
	 * @return [type] [description]
	 */
	public function postparse() {
		if ( ! empty( $this->parser->import ) ) {
			$this->parser->import = array();
		}
		if ( ! empty( $this->parser->charset ) ) {
			$this->parser->charset = array();
		}

		return parent::postparse();
	}

	/**
	 * [subvalue description]
	 *
	 * @since 1.0.0
	 *
	 * @return [type] [description]
	 */
	public function subvalue() {
		$this->sub_value = trim( $this->sub_value );

		// Send any urls through our filter
		if ( preg_match( '!^\\s*url\\s*(?:\\(|\\\\0028)(.*)(?:\\)|\\\\0029).*$!Dis', $this->sub_value, $matches ) ) {
			$this->sub_value = $this->clean_url( $matches[1] );
		}

		// Strip any expressions
		if ( preg_match( '!^\\s*expression!Dis', $this->sub_value ) ) {
			$this->sub_value = '';
		}

		return parent::subvalue();
	}

	/**
	 * [clean_url description]
	 *
	 * @since 1.0.0
	 *
	 * @param [type] $url [description]
	 * @return [type] [description]
	 */
	protected function clean_url( $url ) {
		// Clean up the string.
		$url = trim( $url, "'\"\r\n " );

		// Check against whitelist for properties allowed to have URL values.
		if ( ! in_array( $this->property, $this->props_w_urls ) ) {
			return '';
		}

		$url = wp_kses_bad_protocol_once( $url, $this->allowed_protocols );

		if ( empty( $url ) ) {
			return '';
		}

		return "url('$url')";
	}

} // class TablePress_CSSTidy_custom_sanitize
