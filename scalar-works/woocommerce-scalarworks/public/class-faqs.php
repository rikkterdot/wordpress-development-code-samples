<?php
/**
 * Custom Post Type: FAQs.
 * 
 * @package PluginBoilerplate\Books
 */

namespace WooCommerceScalarworks\Public;
use WooCommerceScalarworks\Utility\CPT as Base_CPT;
class faqs_cpt extends Base_CPT {

    /**
     * Post Type Name.
     * 
     * @var string
     */
    protected string $post_type = 'faq';

    /**
     * Constructor.
     */
    public function __construct() {

        // Set the labels.
        $this->labels = [
            'name'          => 'FAQ',
            'singular_name' => 'FAQ',
            'menu_name'     => 'FAQ',
            'add_new_item'  => 'Add New FAQ',
            'edit_item'     => 'Edit FAQ',
        ];

        // Set the arguments.
        $this->args = [
            'public'        => true,
            'show_in_menu'  => true,
            'menu_icon'     => 'dashicons-book', // Uses WordPress Dashicons
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
            'has_archive'   => true,
            'show_in_rest'  => true,
        ];

        // Call the parent constructor.
        parent::__construct();

    }

}