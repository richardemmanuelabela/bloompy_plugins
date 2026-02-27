<?php

class CustomerPanel extends ET_Builder_Module {

	public $slug       = 'booknetic_cp';
	public $vb_support = 'on';
    private $data;

	protected $module_credits = array(
		'module_uri' => '',
		'author'     => '',
		'author_uri' => '',
	);

	public function init() {
		$this->name = bkntc__( 'Booknetic Customer Panel');
	}

	public function get_fields() {
		return array();
	}

	public function render( $attrs, $content = null, $render_slug ) {
        return do_shortcode( "[booknetic-cp]" );
	}
}

new CustomerPanel;
