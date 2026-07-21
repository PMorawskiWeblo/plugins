<?php
/**
 * Hook loader utility.
 *
 * @package FastMetaManager
 */

declare(strict_types=1);

namespace FFMM\Core;

class Loader
{
	/** @var array<int, array<string, mixed>> */
	private array $actions = [];

	public function add_action(string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1): void
	{
		$this->actions[] = [
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];
	}

	public function run(): void
	{
		foreach ($this->actions as $action) {
			add_action(
				$action['hook'],
				[$action['component'], $action['callback']],
				$action['priority'],
				$action['accepted_args']
			);
		}
	}
}
