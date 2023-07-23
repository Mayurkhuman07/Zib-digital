<?php
/*
Plugin Name: Custom Product Filter
Description: Adds custom taxonomies and filters for the 'product' post type.
Version: 1.0
Author: Mayur Khuman
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}


// Add link to Plugin Settings
function wpdevplugin_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=custom-product-settings">' . __('Settings Page', 'custom-product-settings') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

$filter_name = "plugin_action_links_" . plugin_basename(__FILE__);
add_filter($filter_name, 'wpdevplugin_add_settings_link');

function custom_product_settings_page() {
    ?>
<div class="wrap">
    <h1><?php esc_html_e('Show All products Shortcode Syntax', 'custom-product'); ?></h1>
    <p><?php esc_html_e('NOTE : Please Do not install Woocommerce Plugin in the website it may Affect the plugin Functionality, If having woocommerce please remove it after that install this plugin', 'custom-product'); ?>
    </p>
    <p><?php esc_html_e('Use the following shortcode to display your products in page:', 'custom-product'); ?></p>
    <code>[product_filter] </code>
</div>
<?php
}
// Add the settings page to the admin menu
function custom_product_settings_menu() {
    add_menu_page(
        esc_html__('Custom product Settings', 'custom-product'),
        esc_html__('Product Settings', 'custom-product'),
        'manage_options',
        'custom-product-settings',
        'custom_product_settings_page',
        'dashicons-admin-generic',
        30
    );
}
add_action('admin_menu', 'custom_product_settings_menu');

// Register custom post type 'product'
add_action('init', 'custom_product_post_type');
function custom_product_post_type() {
    $labels = array(
        'name' => __('Products'),
        'singular_name' => __('Product'),
        'add_new' => __('Add New Product'),
        'add_new_item' => __('Add New Product'),
        'edit_item' => __('Edit Product'),
        'new_item' => __('New Product'),
        'view_item' => __('View Product'),
        'search_items' => __('Search Products'),
        'not_found' => __('No products found'),
        'not_found_in_trash' => __('No products found in trash'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
    );

    register_post_type('product', $args);
}

// Register custom taxonomies for 'product' post type
add_action('init', 'custom_product_taxonomies');
function custom_product_taxonomies() {
    // Taxonomy for Colors
    register_taxonomy('product_color', 'product', array(
        'label' => __('Colors'),
        'hierarchical' => true,
        'public' => true,
    ));

    // Taxonomy for Sizes
    register_taxonomy('product_size', 'product', array(
        'label' => __('Sizes'),
        'hierarchical' => true,
        'public' => true,
    ));

    // Taxonomy for Categories
    register_taxonomy('product_category', 'product', array(
        'label' => __('Categories'),
        'hierarchical' => true,
        'public' => true,
    ));
}

// Enqueue scripts and styles for the frontend
add_action('wp_enqueue_scripts', 'custom_product_filter_enqueue_scripts');
function custom_product_filter_enqueue_scripts() {
    // Enqueue the plugin's JS file
    wp_enqueue_script('custom-product-filter', plugin_dir_url(__FILE__) . 'js/custom-product-filter.js', array('jquery'), '1.0', true);
    wp_enqueue_style('custom-product-filter-style', plugin_dir_url(__FILE__) . 'css/custom-product-filter.css', array(), '1.0');

    // Create a JavaScript object containing the AJAX URL and pass it to the script
    wp_localize_script('custom-product-filter', 'customProductFilterAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}

// Create a shortcode for displaying the filter options and all products
add_shortcode('product_filter', 'display_product_filter');
function display_product_filter() {
    ob_start();
    ?>
<div class="product-filter">
    <h4>Colors:</h4>
    <?php $colors = get_terms('product_color', array('hide_empty' => false)); ?>
    <?php foreach ($colors as $color) : ?>
    <label>
        <input type="checkbox" class="filter-checkbox" data-taxonomy="product_color"
            data-term="<?php echo $color->slug; ?>">
        <?php echo $color->name; ?>
    </label><br>
    <?php endforeach; ?>

    <h4>Sizes:</h4>
    <?php $sizes = get_terms('product_size', array('hide_empty' => false)); ?>
    <?php foreach ($sizes as $size) : ?>
    <label>
        <input type="checkbox" class="filter-checkbox" data-taxonomy="product_size"
            data-term="<?php echo $size->slug; ?>">
        <?php echo $size->name; ?>
    </label><br>
    <?php endforeach; ?>
    <div class="filter-btn-plugin">
        <button id="filter-button">Filter</button>
    </div>
</div>

<div class="filtered-products product-grid">
    <?php
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        $products = new WP_Query($args);

        if ($products->have_posts()) {
            while ($products->have_posts()) {
                $products->the_post();
                $post_id = get_the_ID();
                echo '<div class="product">';
                echo get_the_post_thumbnail( $post_id, array( 100, 100));
                echo '<h2>' . get_the_title() . '</h2>';
                $content = get_the_content();
                $trimmed_content = wp_trim_words($content, 10, '...');
                echo '<p>' . $trimmed_content . '</p>';
                // echo get_the_content();
                echo '</div>';
            }
            wp_reset_postdata();
        } else {
            echo '<p>No products found.</p>';
        }
        ?>
</div>
<?php
    return ob_get_clean();
}
  

// Handle AJAX filter requests
add_action('wp_ajax_product_filter', 'product_filter');
add_action('wp_ajax_nopriv_product_filter', 'product_filter');
function product_filter() {
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'tax_query' => array(
            'relation' => 'AND',
        ),
    );

    if (!empty($_POST['colors'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'product_color',
            'field' => 'slug',
            'terms' => $_POST['colors'],
        );
    }

    if (!empty($_POST['sizes'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'product_size',
            'field' => 'slug',
            'terms' => $_POST['sizes'],
        );
    }

    $products = new WP_Query($args);

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            echo '<div class="product">';
            echo get_the_post_thumbnail( $post_id, array( 100, 100));
            echo '<h2>' . get_the_title() . '</h2>';
            $content = get_the_content();
            $trimmed_content = wp_trim_words($content, 10, '...');

            echo '<p>' . $trimmed_content . '</p>';

            // echo get_the_content();
            echo '</div>';
        }
        wp_reset_postdata();
    } else {
        echo '<p>No products found.</p>';
    }

    die();
}