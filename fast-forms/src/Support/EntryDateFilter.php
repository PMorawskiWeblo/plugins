<?php
/**
 * Date range filtering for form entries.
 *
 * @package FastForms
 */

declare(strict_types=1);

namespace Weblo\FastForms\Support;

use Weblo\FastForms\PostTypes\EntryPostType;

/**
 * Normalizuje i buduje zapytania po dacie wysłania zgłoszenia (meta submitted_at).
 */
final class EntryDateFilter {

	/**
	 * Normalizuje datę do formatu Y-m-d (np. z pola type="date").
	 */
	public static function normalize( string $date ): string {
		$date = trim( $date );

		if ( '' === $date ) {
			return '';
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return $date;
		}

		if ( preg_match( '/^(\d{4}-\d{2}-\d{2})/', $date, $matches ) ) {
			return $matches[1];
		}

		$timestamp = strtotime( $date );

		if ( false === $timestamp ) {
			return '';
		}

		return wp_date( 'Y-m-d', $timestamp );
	}

	/**
	 * @return array{0: string, 1: string}
	 */
	public static function normalize_range( string $from, string $to ): array {
		$from = self::normalize( $from );
		$to   = self::normalize( $to );

		if ( '' !== $from && '' !== $to && $from > $to ) {
			return array( $to, $from );
		}

		return array( $from, $to );
	}

	/**
	 * Klauzule meta_query dla zakresu dat wysłania.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function meta_query_clauses( string $from, string $to ): array {
		list( $from, $to ) = self::normalize_range( $from, $to );

		$clauses = array();

		if ( '' !== $from ) {
			$clauses[] = array(
				'key'     => EntryPostType::META_SUBMITTED_AT,
				'value'   => $from . ' 00:00:00',
				'compare' => '>=',
				'type'    => 'DATETIME',
			);
		}

		if ( '' !== $to ) {
			$clauses[] = array(
				'key'     => EntryPostType::META_SUBMITTED_AT,
				'value'   => $to . ' 23:59:59',
				'compare' => '<=',
				'type'    => 'DATETIME',
			);
		}

		return $clauses;
	}

	/**
	 * @param array<int|string, array<string, mixed>> $meta_query Istniejące klauzule.
	 * @return array<int|string, array<string, mixed>>
	 */
	public static function merge_meta_query( array $meta_query, string $from, string $to ): array {
		$date_clauses = self::meta_query_clauses( $from, $to );

		if ( empty( $date_clauses ) ) {
			return $meta_query;
		}

		if ( empty( $meta_query ) ) {
			return $date_clauses;
		}

		if ( ! isset( $meta_query['relation'] ) ) {
			$meta_query = array_merge( array( 'relation' => 'AND' ), $meta_query );
		}

		foreach ( $date_clauses as $clause ) {
			$meta_query[] = $clause;
		}

		return $meta_query;
	}
}
