<?php
/** Bounded, streaming insertion at the document's actual doctype. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Doctype_Inserter_Output {
	private $snippet;
	private $pending = '';
	private $finished = false;
	const PREFIX_LIMIT = 65536;

	public function __construct( $snippet ) {
		$this->snippet = $snippet;
	}

	/** Skip non-HTML, downloads, encoded bodies and responses with a fixed length. */
	public static function is_html_response( $headers, $status ) {
		if ( 204 === $status || ( $status >= 300 && $status < 400 ) ) {
			return false;
		}
		foreach ( $headers as $header ) {
			$parts = explode( ':', $header, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			$name  = strtolower( trim( $parts[0] ) );
			$value = strtolower( trim( $parts[1] ) );
			if ( 'content-type' === $name && 'text/html' !== trim( explode( ';', $value )[0] ) ) {
				return false;
			}
			if ( in_array( $name, array( 'content-disposition', 'content-length' ), true )
				|| ( 'content-encoding' === $name && 'identity' !== $value ) ) {
				return false;
			}
		}
		return true;
	}

	/** Buffer only the preamble, then pass later chunks through unchanged. */
	public function handle( $buffer, $phase = PHP_OUTPUT_HANDLER_FINAL ) {
		if ( $phase & PHP_OUTPUT_HANDLER_CLEAN ) {
			$this->pending = '';
			return $buffer;
		}
		if ( $this->finished ) {
			return $buffer;
		}
		$buffer        = $this->pending . $buffer;
		$this->pending = '';
		if ( ! self::is_html_response( headers_list(), http_response_code() ) ) {
			$this->finished = true;
			return $buffer;
		}
		// Only whitespace, a UTF-8 BOM and comments may precede the document doctype.
		// Anchoring prevents matching a doctype example inside a script or body.
		$prefix = substr( $buffer, 0, self::PREFIX_LIMIT );
		$match  = preg_match( '~\A(?:\xEF\xBB\xBF)?[\t\n\r\f ]*(?:<!--.*?-->[\t\n\r\f ]*)*<!doctype[\t\n\r\f ]+html(?:[\t\n\r\f ]+[^<>]*)?>~is', $prefix, $matches );
		if ( 1 === $match ) {
			$this->finished = true;
			$offset         = strlen( $matches[0] );
			// Concatenation preserves $1, backslashes and other replacement metacharacters.
			return substr( $buffer, 0, $offset ) . "\n" . $this->snippet . substr( $buffer, $offset );
		}
		if ( false === $match || strlen( $buffer ) >= self::PREFIX_LIMIT || ( $phase & PHP_OUTPUT_HANDLER_FINAL ) ) {
			$this->finished = true;
			return $buffer;
		}
		$this->pending = $buffer;
		return '';
	}
}
