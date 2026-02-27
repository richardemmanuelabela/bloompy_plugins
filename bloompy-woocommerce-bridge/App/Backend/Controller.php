<?php
namespace BloompyAddon\WooCommerceBridge\Backend;

use BookneticApp\Providers\Core\Controller as BaseController;
use function \BloompyAddon\WooCommerceBridge\bkntc__;

class Controller extends BaseController
{
    public function index()
    {
        $this->view('index', [
            'title' => bkntc__('WooCommerce Bridge Settings')
        ]);
    }
} 