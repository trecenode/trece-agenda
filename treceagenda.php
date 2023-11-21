<?php
/**
 * Plugin Name: Trece Agenda
 * Plugin URI: https://13node.com/informatica/wordpress/trece-agenda/
 * Description: A simple agenda plugin for manage internal events.
 * Version: 1.0
 * Author: 13Node.com
 * Author URI: https://13node.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function trece_agenda_init() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
	
    $table_name = $wpdb->prefix . 'trece_agenda';

    $sql = "CREATE TABLE " . $table_name . " (
        id INT NOT NULL AUTO_INCREMENT,
        fecha_hora DATETIME NOT NULL,
        profesional VARCHAR(255) NOT NULL,
        servicio VARCHAR(255) NOT NULL,
        detalles TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";
 
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $role = get_role('subscriber');
    $capabilities = $role->capabilities;
    $capabilities['read'] = true;

    // $capabilities['edit_trece_agenda'] = true;
    $capabilities['view_trece_agenda'] = true;

    add_role('trece_agenda_role', 'Gestor de Agenda', $capabilities);
}
register_activation_hook(__FILE__, 'trece_agenda_init');

function trece_agenda_script_init() {
    wp_enqueue_script( 'trece-agenda', plugin_dir_url( __FILE__ ) . 'js/admin.js', array(), '1.0' );
    wp_enqueue_script('fullcalendar', plugin_dir_url( __FILE__ ) . 'js/fullcalendar.js', array('jquery'), '5.5.0', true);
}
add_action('admin_enqueue_scripts','trece_agenda_script_init');
add_action( 'admin_head', function () { ?>
<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 60%;
}

.trece-close {
    color: #aaaaaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.trece-close:hover {
    color: #000;
    text-decoration: none;
    cursor: pointer;
}
</style>
<?php } );
function trece_agenda_events() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'trece_agenda';
    $events = $wpdb->prepare("SELECT * FROM $table_name");
    return rest_ensure_response($events);
}
function trece_agenda_api_endpoint() {
    register_rest_route('trece-agenda/v1', '/events', array(
        'methods' => 'GET',
        'callback' => 'trece_agenda_events',
    ));
}
add_action('rest_api_init', 'trece_agenda_api_endpoint');

function trece_agenda_menu() {
    if (current_user_can('view_trece_agenda')) {
        $trece_capability = 'view_trece_agenda';
    } else {
        $trece_capability = 'manage_options';
    }
    add_menu_page('Citas', 'Citas', $trece_capability, 'trece-agenda', 'trece_agenda_admin', 'dashicons-calendar-alt');
}
add_action('admin_menu', 'trece_agenda_menu');

function trece_agenda_save($fecha_hora, $profesional, $servicio, $detalles) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'trece_agenda';

    $data = array(
        'fecha_hora' => $fecha_hora,
        'profesional' => $profesional,
        'servicio' => $servicio,
        'detalles' => $detalles,
    );

    // Define the format for saving data (datatypes)
    $format = array('%s', '%s', '%s', '%s');

    $wpdb->insert($table_name, $data, $format);

    if ($wpdb->insert_id) {
        return true;
    } else {
        return false;
    }
}
function trece_agenda_delete($item_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'trece_agenda';
    $delete_query = $wpdb->prepare("DELETE FROM $table_name WHERE id = %d", $item_id);
    $wpdb->query($delete_query);
    if ($wpdb->rows_affected > 0) {
        return true;
    } else {
        return false;
    }
}

function trece_agenda_admin() {
    $trecenode_credit = plugin_dir_url(__FILE__) . 'images/trecenode.png';
    if (isset($_POST['submit_cita'])) {
        if (isset($_POST['trece_agenda_add_nonce']) && wp_verify_nonce($_POST['trece_agenda_add_nonce_field'], 'trece_agenda_add_nonce')) {
            $fecha_hora = sanitize_text_field($_POST['fecha_hora']);
            $profesional = sanitize_text_field($_POST['profesional']);
            $servicio = sanitize_text_field($_POST['servicio']);
            $detalles = sanitize_textarea_field($_POST['detalles']);

            $result = trece_agenda_save($fecha_hora, $profesional, $servicio, $detalles);

            if ($result) {
                echo '<div class="updated"><p>Cita guardada con éxito.</p></div>';
            } else {
                echo '<div class="error"><p>Error al guardar la cita.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Error de seguridad. Por favor, inténtalo de nuevo.</p></div>';
        }
    }
    if (isset($_POST['delete_item'])) {
        $item_id_to_delete = sanitize_text_field($_POST['trece-event-id']);
    
        $deleted = trece_agenda_delete($item_id_to_delete);
    
        if ($deleted) {
            echo '<div class="updated"><p>Cita eliminada con éxito.</p></div>';
        } else {
            echo '<div class="error"><p>Error al eliminar la cita.</p></div>';
        }
    }
    echo '<div id="trece-agenda-calendar"></div>';
    ?>
    <div class="wrap">
        <h2>Agendar Cita</h2>
        <form method="post" action="">
            <?php wp_nonce_field('trece_agenda_add_nonce', 'trece_agenda_add_nonce_field'); ?>
            <label for="fecha_hora">Fecha y Hora:</label><br>
            <input type="datetime-local" id="fecha_hora" name="fecha_hora" required><br><br>

            <label for="profesional">Profesional:</label><br>
            <input type="text" id="profesional" name="profesional" required><br><br>

            <label for="servicio">Servicio:</label><br>
            <input type="text" id="servicio" name="servicio" required><br><br>

            <label for="detalles">Detalles:</label><br>
            <textarea id="detalles" name="detalles" rows="4" cols="50"></textarea><br><br>

            <input type="submit" name="submit_cita" class="button button-primary" value="Guardar Cita">
        </form>
    </div>
    <div id="trece-event-modal" class="modal">
        <div class="modal-content">
            <span class="trece-close">&times;</span>
            <h2 id="trece-event-title"></h2>
            <p id="trece-event-start"></p>
            <p id="trece-event-details"></p>
            <form method="post" action="">
                <input type="hidden" name="trece-event-id" id="trece-event-id" value="">
                <button type="submit" name="delete_item" class="button button-secondary"><span class="dashicons dashicons-trash"></span> Eliminar Cita</button>
            </form>

        </div>
    </div>
    <p><img src="<?php echo esc_url($trecenode_credit); ?>" height="10"> Este plugin ha sido desarrollado por <a href="https://13node.com">13Node.com</a>.</p>
<?php
}