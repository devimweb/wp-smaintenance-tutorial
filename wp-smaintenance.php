<?php
/*
 * Plugin Name: Wordpress Simple Maintenance
 * Plugin URI: https://github.com/devimbr/wp-smaintenance
 * Description: Add a page that prevents your site's content view. Ideal to report a scheduled maintenance or coming soon page.
 * Version: 0.0.1
 * Author: Miguel Müller
 * Author URI: https://github.com/miguelsneto
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-smaintenance
 * Domain Path: /languages/
 *
 * Referências:
 * http://code.tutsplus.com/articles/design-patterns-in-wordpress-the-singleton-pattern--wp-31621
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_SMaintenance' ) ) :

/**
 * Wordpress Simple Maintenance Main class
 */
class WP_SMaintenance
{
    /**
     * Instance of this class.
     *
     * @var object
     */
    private static $instance = null;

    protected $smaintenance_settings;

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {

        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

    /**
     * Initialize the plugin public actions.
     */
    private function __construct() {
        // Load plugin text domain
        add_action( 'init', array( $this, 'load_textdomain' ) );

        add_action( 'init', array( $this, 'plugin_settings' ) );
        add_action( 'admin_init', array( &$this, 'admin_init' ));
        add_action( 'admin_menu', array( &$this, 'admin_menu' ));

        if ($this->smaintenance_settings['status'] == 'TRUE') {
            //add_filter( 'loginMessage', array( &$this, 'loginMessage' ));
            //add_action( 'admin_notices', array( &$this, 'admin_notices' ));
            //add_action( 'wp_loaded', array( &$this, 'apply_manut_mode' ));
            //add_action( 'after_body', array( &$this, 'after_body' ));
            //add_filter( 'wp_title', array( &$this, 'wpTitle' ), 9999, 2 );
        }
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_textdomain() {
        $domain = 'wp-smaintenance';
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . 'wp-smaintenance/wp-smaintenance-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     *
     */
    public function plugin_settings() {
        if( false == get_option( 'smaintenance_settings' )) {
            add_option( 'smaintenance_settings' );
            $default = array(
                'status'           => FALSE,
                'description'      => '', // Why the maintenance mode is active
                'time_activated'   => '', // Time that has been activated
                'duration_days'    => '', // Days suspended
                'duration_hours'   => '', // Hours suspended
                'duration_minutes' => '', // Minutos suspended
                'url_allowed'      => '',
                'role_allow_front' => '',
                'role_allow_back'  => ''
            );
            update_option( 'smaintenance_settings', $default );
        }
        $this->smaintenance_settings = get_option( 'smaintenance_settings' );
    }

    /**
     * Cria um formulário pra ser usada pra configuração do tema
     */
    function admin_init(){
        add_settings_section(
            'section_maintenance',
            __('Configure the details of the maintenance mode'),
            '__return_false',
            'wp-smaintenance'
        );

        add_settings_field(
            'status',
            __('Enable maintenance mode:'),
            array( &$this, 'html_input_status' ),
            'wp-smaintenance',
            'section_maintenance'
        );

        add_settings_field(
            'description',
            __('Motive of maintenance:'),
            array( &$this, 'html_input_description' ),
            'wp-smaintenance',
            'section_maintenance'
        );

        add_settings_field(
            'url_allowed',
            __('The following pages have free access:'),
            array( &$this, 'html_input_url_allowed' ),
            'wp-smaintenance',
            'section_maintenance'
        );

        add_settings_field(
            'role_allow',
            __('Who can access:'),
            array( &$this, 'html_input_role_allow' ),
            'wp-smaintenance',
            'section_maintenance'
        );

        register_setting(
            'wp-smaintenance',
            'smaintenance_settings'
        );
    }

    /**
     *
     */
    public function html_input_status(){
        if ($this->smaintenance_settings['status'] == TRUE) :
            $return    = $this->calc_time_maintenance();

            $message = sprintf( __("The maintenance mode will end in <strong>%s</strong>"), $return['return-date'] );
            echo ("<p><span class='description'>$message</span></p><br/>");
        endif;

        $days  = $this->smaintenance_settings['status'] == TRUE ? $return['remaining-array']['days'] : '1';
        $hours = $this->smaintenance_settings['status'] == TRUE ? $return['remaining-array']['hours'] : '0';
        $mins  = $this->smaintenance_settings['status'] == TRUE ? $return['remaining-array']['mins'] : '0';
        ?>

        <input type="hidden" name="smaintenance_settings[time_activated]" value="<?php echo current_time('timestamp'); ?>">

        <label>
            <input type="checkbox" id="status" name="smaintenance_settings[status]" value="TRUE" <?php checked( 'TRUE', $this->smaintenance_settings['status'] ) ?> /> <?php _e('I want to enable'); ?>
        </label>

        <br/>
        <table>
            <tbody>
                <tr>
                    <td><strong><?php _e('Back in:'); ?></strong></td>
                    <td><input type="text" id="duration_days" name="smaintenance_settings[duration_days]" value="<?php echo $days; ?>" size="4" maxlength="5"> <label for="duration_days"><?php _e('Days'); ?></label></td>
                    <td><input type="text" id="duration_hours" name="smaintenance_settings[duration_hours]" value="<?php echo $hours; ?>" size="4" maxlength="5"> <label for="duration_hours"><?php _e('Hours'); ?></label></td>
                    <td><input type="text" id="duration_minutes" name="smaintenance_settings[duration_minutes]" value="<?php echo $mins; ?>" size="4" maxlength="5"> <label for="duration_minutes"><?php _e('Minutes'); ?></label></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     *
     */
    public function html_input_description(){
        $html = '<textarea id="description" name="smaintenance_settings[description]" cols="80" rows="5" class="large-text">'.$this->smaintenance_settings['description'].'</textarea>';
        echo $html;
    }

    /**
     *
     */
    public function html_input_url_allowed(){
        $html = '<textarea id="url_allowed" name="smaintenance_settings[url_allowed]" cols="80" rows="5" class="large-text">'.$this->smaintenance_settings['url_allowed'].'</textarea>';
        $html .= '<br/><span class="description">Digite os caminhos que devem estar acessíveis mesmo em modo de manutenção. Separe os vários caminhos com quebras de linha.<br/>Exemplo: Se você quer liberar acesso á pagina <strong>http://site.com/sobre/</strong>, você deve digitar <strong>/sobre/</strong>.<br/>Dica: Se você quiser liberar acesso a página inicial digite <strong>[HOME]</strong>.</span>';
        echo $html;
    }

    /**
     *
     */
    public function html_input_role_allow(){
        //INPUT FOR ALLOW BACK
        $html = '<label>'. __('Access the administrative panel:');
        $html .= ' <select id="role_allow_back" name="smaintenance_settings[role_allow_back]">
                    <option value="manage_options" ' . selected( $this->smaintenance_settings['role_allow_back'], 'manage_options', false) . '>Ninguém</option>
                    <option value="manage_categories" ' . selected( $this->smaintenance_settings['role_allow_back'], 'manage_categories', false) . '>Editor</option>
                    <option value="publish_posts" ' . selected( $this->smaintenance_settings['role_allow_back'], 'publish_posts', false) . '>Autor</option>
                    <option value="edit_posts" ' . selected( $this->smaintenance_settings['role_allow_back'], 'edit_posts', false) . '>Colaborador</option>
                    <option value="read" ' . selected( $this->smaintenance_settings['role_allow_back'], 'read', false) . '>Visitante</option>
                </select>';
        $html .= '</label><br />';

        //INPUT FOR ALLOW FRONT
        $html .= '<label>'. __('Access the public site:');
        $html .= ' <select id="role_allow_front" name="smaintenance_settings[role_allow_front]">
                    <option value="manage_options" ' . selected( $this->smaintenance_settings['role_allow_front'], 'manage_options', false) . '>Ninguém</option>
                    <option value="manage_categories" ' . selected( $this->smaintenance_settings['role_allow_front'], 'manage_categories', false) . '>Editor</option>
                    <option value="publish_posts" ' . selected( $this->smaintenance_settings['role_allow_front'], 'publish_posts', false) . '>Autor</option>
                    <option value="edit_posts" ' . selected( $this->smaintenance_settings['role_allow_front'], 'edit_posts', false) . '>Colaborador</option>
                    <option value="read" ' . selected( $this->smaintenance_settings['role_allow_front'], 'read', false) . '>Visitante</option>
                </select>';
        $html .= '</label><br />';
        echo $html;
    }


    /**
     *
     */
    function admin_menu(){
        add_submenu_page(
            'options-general.php',
            __('Maintenance mode'),
            __('Maintenance mode'),
            'administrator',
            'wp-smaintenance',
            array( &$this, 'html_form_settings' )
        );
    }

    /**
     *
     */
    public function html_form_settings(){
    ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h2><?php _e('General Settings'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'wp-smaintenance' );
                do_settings_sections( 'wp-smaintenance' );
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     *
     */
    public function calc_time_maintenance(){
        // How long will it stay off in seconds
        $time_duration = 0;
        $time_duration += intval($this->smaintenance_settings['duration_days']) * 24 * 60;
        $time_duration += intval($this->smaintenance_settings['duration_hours']) * 60;
        $time_duration += intval($this->smaintenance_settings['duration_minutes']);
        $time_duration = intval($time_duration * 60);

        // Timestamp of time activated, time finished, time current e time remaining
        $time_activated = intval($this->smaintenance_settings['time_activated']);
        $time_finished  = intval($time_activated + $time_duration);
        $time_current   = current_time('timestamp');
        $time_remaining = $time_finished - $time_current;

        // Format the date in the format defined by the system
        $return_day  = date_i18n( get_option('date_format'), $time_finished );
        $return_time = date_i18n( get_option('time_format'), $time_finished );
        $return_date = $return_day . ' ' . $return_time;

        $time_calculated = $this->calc_separate_time($time_remaining);

        return array(
            'return-date'       => $return_date,
            'remaining-seconds' => $time_remaining,
            'remaining-array'   => $time_calculated,
        );
    }


    /**
     * Calculates the days, hours and minutes remaining based on the number of seconds
     *
     * @return array Array containing the values of days, hours and minutes remaining
     */
    private function calc_separate_time($seconds){
        $minutes = round(($seconds/(60)), 0);

        $minutes = intval($minutes);
        $vals_arr = array(  'days' => (int) ($minutes / (24*60) ),
                            'hours' => $minutes / 60 % 24,
                            'mins' => $minutes % 60);
        $return_arr = array();
        $is_added = false;
        foreach ($vals_arr as $unit => $amount) {
            $return_arr[$unit] = 0;

            if ( ($amount > 0) || $is_added ) {
                $is_added          = true;
                $return_arr[$unit] = $amount;
            }
        }
        return $return_arr;
    }

}
add_action( 'plugins_loaded', array( 'WP_SMaintenance', 'get_instance' ), 0 );

endif;