<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class MP_Loader
 *
 * Handles registration of all plugin hooks (actions & filters).
 * This class is responsible for maintaining lists of all hooks
 * and executing them through WordPress when required.
 *
 * @package    MetaPusher
 * @subpackage MetaPusher/includes
 */
class MP_Loader {

    /**
     * @var array $actions Registered action hooks.
     */
    protected $actions = [];

    /**
     * @var array $filters Registered filter hooks.
     */
    protected $filters = [];

    /**
     * Register a new action hook.
     *
     * @param string   $hook          The hook name.
     * @param object   $component     Reference to the component instance.
     * @param string   $callback      The callback method.
     * @param int      $priority      Priority for execution.
     * @param int      $accepted_args Number of arguments accepted.
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Register a new filter hook.
     *
     * @param string   $hook          The hook name.
     * @param object   $component     Reference to the component instance.
     * @param string   $callback      The callback method.
     * @param int      $priority      Priority for execution.
     * @param int      $accepted_args Number of arguments accepted.
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Internal helper to store hooks.
     */
    private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = [
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        ];
        return $hooks;
    }

    /**
     * Execute all registered actions and filters via WordPress.
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                [ $hook['component'], $hook['callback'] ],
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                [ $hook['component'], $hook['callback'] ],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
