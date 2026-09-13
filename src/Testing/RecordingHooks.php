<?php

declare(strict_types=1);

namespace ComposePress\Core\Testing;

use ComposePress\Core\Hooks;
use WP_Hook;

/**
 * Hooks recorder whose registration state is a real WP_Hook engine: every
 * registration, removal, and lookup goes through WP_Hook::add_filter(),
 * ::remove_filter(), and ::has_filter(), so WordPress semantics (unique
 * callback ids, replace-on-re-add, exact-priority removal, priority lookups)
 * cannot drift from production. The recorder adds only call history.
 *
 * WP_Hook must be loadable: real WordPress when it runs, or the
 * roots/wordpress-no-content dev package in plain unit tests.
 */
final class RecordingHooks implements Hooks
{
    /** @var array<string, WP_Hook> hook name => engine shared by action and filter registrations */
    private array $engines = [];

    /** @var list<HookRegistration> every action call, including later replaced or removed ones */
    private array $actionHistory = [];

    /** @var list<HookRegistration> every filter call, including later replaced or removed ones */
    private array $filterHistory = [];

    /** @var list<HookRegistration> */
    private array $removedActions = [];

    /** @var list<HookRegistration> */
    private array $removedFilters = [];

    public function action(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void
    {
        $this->record($this->actionHistory, $hook, $callback, $priority, $arguments);
    }

    public function filter(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void
    {
        $this->record($this->filterHistory, $hook, $callback, $priority, $arguments);
    }

    public function removeAction(string $hook, callable $callback, int $priority = 10): bool
    {
        return $this->remove($this->removedActions, $hook, $callback, $priority);
    }

    public function removeFilter(string $hook, callable $callback, int $priority = 10): bool
    {
        return $this->remove($this->removedFilters, $hook, $callback, $priority);
    }

    /** @return list<HookRegistration> */
    public function recordedActions(): array
    {
        return $this->actionHistory;
    }

    /** @return list<HookRegistration> */
    public function recordedFilters(): array
    {
        return $this->filterHistory;
    }

    /** @return list<HookRegistration> */
    public function recordedRemovals(): array
    {
        return [...$this->removedActions, ...$this->removedFilters];
    }

    /**
     * Hook names of every recorded action registration, in call order.
     *
     * @return list<string>
     */
    public function actionNames(): array
    {
        return array_map(static fn (HookRegistration $registration): string => $registration->hook, $this->actionHistory);
    }

    /**
     * Hook names of every recorded filter registration, in call order.
     *
     * @return list<string>
     */
    public function filterNames(): array
    {
        return array_map(static fn (HookRegistration $registration): string => $registration->hook, $this->filterHistory);
    }

    /** @return list<HookRegistration> */
    public function actionsFor(string $hook): array
    {
        return $this->matching($this->actionHistory, $hook);
    }

    /** @return list<HookRegistration> */
    public function filtersFor(string $hook): array
    {
        return $this->matching($this->filterHistory, $hook);
    }

    /** @return list<HookRegistration> */
    public function removedActionsFor(string $hook): array
    {
        return $this->matching($this->removedActions, $hook);
    }

    /** @return list<HookRegistration> */
    public function removedFiltersFor(string $hook): array
    {
        return $this->matching($this->removedFilters, $hook);
    }

    /**
     * Mirrors has_action(): the priority of the lowest priority bucket holding
     * the callback, or false when unregistered. Compare with ===; priority 0
     * is falsy.
     */
    public function hasAction(string $hook, callable $callback): int|false
    {
        $engine = $this->engines[$hook] ?? null;

        if ($engine === null) {
            return false;
        }

        $priority = $engine->has_filter($hook, $callback);

        return is_int($priority) ? $priority : false;
    }

    /**
     * Mirrors has_filter(); see hasAction() for the return semantics.
     */
    public function hasFilter(string $hook, callable $callback): int|false
    {
        $engine = $this->engines[$hook] ?? null;

        if ($engine === null) {
            return false;
        }

        $priority = $engine->has_filter($hook, $callback);

        return is_int($priority) ? $priority : false;
    }

    public function reset(): void
    {
        $this->engines = [];
        $this->actionHistory = [];
        $this->filterHistory = [];
        $this->removedActions = [];
        $this->removedFilters = [];
    }

    /**
     * @param list<HookRegistration> $history
     */
    private function record(array &$history, string $hook, callable $callback, int $priority, int $arguments): void
    {
        $history[] = new HookRegistration($hook, $callback, $priority, $arguments);
        $this->engine($hook)->add_filter($hook, $callback, $priority, $arguments);
    }

    /**
     * Returns true only when the exact callback id exists inside the exact
     * priority bucket, exactly as WP_Hook::remove_filter() does, and records
     * the removed registration afterwards.
     *
     * @param list<HookRegistration> $removals
     */
    private function remove(array &$removals, string $hook, callable $callback, int $priority): bool
    {
        $engine = $this->engine($hook);
        $id = _wp_filter_build_unique_id($hook, $callback, $priority);
        $entry = $engine->callbacks[$priority][$id] ?? null;

        if ($entry === null || !$engine->remove_filter($hook, $callback, $priority)) {
            return false;
        }

        $removals[] = new HookRegistration($hook, $entry['function'], $priority, (int) $entry['accepted_args']);

        return true;
    }

    private function engine(string $hook): WP_Hook
    {
        if (!class_exists(WP_Hook::class)) {
            throw new \LogicException(
                'RecordingHooks delegates to WP_Hook. Load WordPress, or add roots/wordpress-no-content as a dev dependency.',
            );
        }

        // WP_Hook's methods call the unqualified global function; it exists
        // whenever real WordPress loaded plugin.php, and the guarded shim
        // provides it otherwise. Never eager-load: defining the global while
        // WordPress later loads plugin.php would be a fatal redeclaration.
        if (!function_exists('_wp_filter_build_unique_id')) {
            require_once __DIR__ . '/wp_hook_globals.php';
        }

        return $this->engines[$hook] ??= new WP_Hook();
    }

    /**
     * @param list<HookRegistration> $registrations
     * @return list<HookRegistration>
     */
    private function matching(array $registrations, string $hook): array
    {
        return array_values(array_filter($registrations, static fn (HookRegistration $registration): bool => $registration->hook === $hook));
    }
}
