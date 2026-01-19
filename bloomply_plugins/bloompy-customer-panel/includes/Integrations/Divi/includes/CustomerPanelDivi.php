<?php

namespace Bloompy\CustomerPanel\Integrations\Divi\includes;

use DiviExtension;

class CustomerPanelDivi extends DiviExtension {

	/**
	 * CustomerPanelDivi constructor.
	 *
	 * @param string $name
	 * @param array  $args
	 */
	public function __construct( $name = 'booknetic_cp', $args = array() ) {
		$this->plugin_dir     = plugin_dir_path( __FILE__ );
		$this->plugin_dir_url = plugin_dir_url( $this->plugin_dir );
		parent::__construct( $name, $args );
	}
}
