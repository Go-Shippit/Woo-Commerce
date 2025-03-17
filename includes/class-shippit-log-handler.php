<?php

/**
* Mamis - https://www.mamis.com.au
* Copyright © Mamis 2023-present. All rights reserved.
* See https://www.mamis.com.au/license
*/

class Mamis_Shippit_Log_Handler extends WC_Log_Handler_File
{
	/**
	 * Handle a log entry.
	 *
	 * @param int $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array $context
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle($timestamp, $level, $message, $context)
	{
		$entry = self::format_entry($timestamp, $level, $message, $context);

		return $this->add($entry, $context['source']);
	}

	/**
	* Builds a log entry text from timestamp, level and message.
	*
	* @param int    $timestamp Log timestamp.
	* @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	* @param string $message Log message.
	* @param array  $context Additional information for log handlers.
	*
	* @return string Formatted log entry.
	*/
	protected static function format_entry($timestamp, $level, $message, $context)
	{
		$logDate = self::format_time($timestamp);
		$logLevel = strtoupper($level);
		$logContext = json_encode($context);
		$entry = "{$logDate} {$logLevel} {$message} - {$logContext}";

		return apply_filters(
			'woocommerce_format_log_entry',
			$entry,
			array(
				'timestamp' => $timestamp,
				'level' => $level,
				'message' => $message,
				'context' => $context,
			)
		);
	}
}
