<?php

namespace Weblo\QuickReturns\Domain;

class ReturnRequest {

	private string $request_number;
	private int $order_id;
	private string $order_key;
	private int $customer_id;
	private string $customer_email;
	private string $status;
	private string $submitted_at;
	private array $items;
	private array $totals;
	private string $source;

	public function __construct(
		string $request_number,
		int $order_id,
		string $order_key,
		int $customer_id,
		string $customer_email,
		array $items,
		array $totals,
		string $source,
		string $status = 'new'
	) {
		$this->request_number  = $request_number;
		$this->order_id          = $order_id;
		$this->order_key         = $order_key;
		$this->customer_id       = $customer_id;
		$this->customer_email    = $customer_email;
		$this->items             = $items;
		$this->totals            = $totals;
		$this->source            = $source;
		$this->status            = $status;
		$this->submitted_at      = gmdate( 'Y-m-d H:i:s' );
	}

	public function get_request_number(): string {
		return $this->request_number;
	}

	public function get_order_id(): int {
		return $this->order_id;
	}

	public function get_order_key(): string {
		return $this->order_key;
	}

	public function get_customer_id(): int {
		return $this->customer_id;
	}

	public function get_customer_email(): string {
		return $this->customer_email;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_submitted_at(): string {
		return $this->submitted_at;
	}

	public function get_items(): array {
		return $this->items;
	}

	public function get_totals(): array {
		return $this->totals;
	}

	public function get_source(): string {
		return $this->source;
	}
}
