<?php

namespace BitForm\Common;

use BitForm\Context;
use BitForm\Common\Shortcode;

class Widget extends \WP_Widget
{

    private $formRepository;

    public function __construct()
    {
        $this->formRepository = Context::$formRepository;
        $options = array(
            'classname'   => 'bitform-widget',
            'description' => __('Display a BitForm', 'bitform')
        );
        parent::__construct('bitform-widget', 'BitForm', $options);
    }

    public static function register()
    {
        register_widget(__CLASS__);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        echo Shortcode::shortcode($instance);
        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $forms = $this->formRepository->findAllFormTitles();
        $width = $instance['width'] ? $instance['width'] : '';
        $height = $instance['height'] ? $instance['height'] : '';
?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('id')); ?>">
                <?php esc_html_e('Form:', 'bitform'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('id')); ?>" name="<?php echo esc_attr($this->get_field_name('id')); ?>">
                <option value=""><?php esc_html_e('Select a form', 'bitform'); ?></option>
                <?php foreach ($forms as $form) : ?>
                    <option value="<?php echo $form['id']; ?>" <?php selected($instance['id'], $form['id']); ?>><?php esc_html_e($form['title']); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('width'); ?>">
                <?php esc_html_e('Width:', 'bitform'); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo esc_attr($width); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('height'); ?>">
                <?php esc_html_e('Height:', 'bitform'); ?>
            </label>
            <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>" />
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['id'] = is_numeric($new_instance['id']) ? (int) $new_instance['id'] : '';
        $instance['width'] = $new_instance['width'];
        $instance['height'] = $new_instance['height'];
        return $instance;
    }
}
