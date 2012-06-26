<?php
/*
Plugin Name: 9th Circle Games Dice Roller
Plugin URI: http://code.9thcircle.it
Description: Admin-side dice roller for 9th Circle Games' play by forum campaigns
Version: 0.2
Authors: Gabriele Baldassarre, Federico Razzoli
Author URI: http://9thcircle.it
*/

/*  
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation; version 3 of the License.
	
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
	
    You should have received a copy of the GNU Affero General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/




// All Ninth Circle plugins go into this namespace.
namespace C9\WP_PLUGINS;


class DiceRoller
{
	//! Version of this plugin's tables structure.
	const DB_VERSION  = '0.1';
	//! All tables created by this plugin use this prefix. A sort of SQL schema, or namespace.
	const TAB_PREFIX  = 'pbf_';
	
	
	
	//! Name of campaign table.
	private static /*. string .*/  $tabCampaign   = '';
	//! Name of dice roll table.
	private static /*. string .*/  $tabRoll       = '';
	//! DB connection object.
	private static /*. wpdb .*/    $wpdb          = NULL;
	//! Number of rolls per page.
	private static /*. int .*/     $rollsPerPage  = 10;
	
	
	
	
	/**
	 *	\brief		Returns a querystring identical to the old one.
	 *				If $skipValues is passed, some values are excluded.
	 *	@param		string[]		$skipValues			Values to exlude.
	 *	@return		string
	 */
	private static function rebuildQueryString($skipValues)
	{
		$out = '';
		$old = explode('&', $_SERVER['QUERY_STRING']);
		$begun = FALSE;
		
		foreach ($old as $strPair) {
			$hashPair = explode('=', $strPair);
			if (in_array($hashPair[0], $skipValues) !== TRUE) {
				if ($begun !== FALSE) {
					$out .= '&';
				}
				$out .= $strPair;
				$begun = TRUE;
			}
		}
		
		return $out;
	}
	
	public static function handleActivation()
	{
		$installed_ver = get_option( 'ninthcircle_dr_db_version' );
		
		#if($installed_ver != self::DB_VERSION) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		self::get_wpdb();
		
		/* Campaign table */
		$sql = "CREATE TABLE " . self::$tabCampaign . " (
		id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
		master_ID BIGINT NOT NULL,
		campaign_name CHAR(128) NOT NULL,
		campaign_description CHAR(255) NOT NULL,
		campaign_url VARCHAR(400) NOT NULL,
		campaign_active ENUM('0','1') NOT NULL,
		campaign_start_date TIMESTAMP,
		campaign_end_date TIMESTAMP,
		PRIMARY KEY (id)
		);";
		dbDelta($sql);
		
		/* Roll table */
		$sql = "CREATE TABLE " . self::$tabRoll . " (
		id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
		roller_ID BIGINT NOT NULL,
		campaign_ID SMALLINT NOT NULL,
		roll_description CHAR(255),
		roll_setup VARCHAR(25) NOT NULL,
		roll_result SMALLINT NOT NULL,
		roll_datetime DATETIME,
		PRIMARY KEY (id)
		);";
		dbDelta($sql);
		
		## DBUG This is here for debug, until we implement campaign management!
		$sql = "INSERT INTO " . self::$tabCampaign . " (`id`, `campaign_name`, `campaign_description`, `campaign_active`) VALUES (1, 'Test Campaign', 'Let\'s test the Dice Roller!!!', '1');";
		dbDelta($sql);
		
		update_option( 'ninthcircle_dr_db_version', self::DB_VERSION);
		#}
	}
	
	/**
	 *	\brief		Called on plugin de-activation.
	 *				Drops all plugin tables.
	 *	@return		void
	 */
	public static function handleDeactivation()
	{
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		self::get_wpdb();
		
		/* Campaign table */
		$sql = 'DROP TABLE IF EXISTS ' . self::$tabCampaign . ';';	
		dbDelta($sql);
		
		/* Roll table */
		$sql = 'DROP TABLE IF EXISTS ' . self::$tabRoll . ';';	
		dbDelta($sql);
	}
	
	public static function mainPage()
	{
		self::get_wpdb();
		
		echo '<div class="wrap"><h2>' . __("9th Circle Games Dice Roller", 'dice-roller') . '</h2>';
		if ($_REQUEST['submit']) {
			self::roll();
		}
		self::form();
		self::printRolls(self::$rollsPerPage);
		echo '</div>';
	}
	
	private static function roll()
	{
		$N = (int)$_REQUEST['N'];
		$X = (int)$_REQUEST['X'];
		$M = (int)$_REQUEST['M'];
		
		$roll_setup = $_REQUEST['N'] . "d" . $_REQUEST['X'] . ($M != 0 ? $_REQUEST['M'] : '');

	$time = date("Y-m-d H:m:s");
		
			$result=0;
			for($i = 0; $i < $N; $i++){
				$result += rand(1,$X);
			}
			$result += $M;
			//$result = rand($N,$N*$X)+$M;
		
		$data = array(
			'roller_ID'         => get_current_user_id(),
			'campaign_ID'       => $_REQUEST['roll_campaign'],
			'roll_description'  => $_REQUEST['roll_description'],
			'roll_setup'        => $roll_setup,
			'roll_result'       => $result,
			'roll_datetime'     => $time
			);
		
		self::$wpdb->insert( self::$tabRoll, $data );

		if(self::$wpdb->insert_id != false) {
			echo '<div class="updated">';
			_e('Roll done!', 'dice-roller');
			echo '<br />';
			printf(__('Result: %s = %d', 'dice-roller'), $roll_setup, $result );
			echo '</div>';
		} else echo '<div class="error">' . _e('Error in updating roll table', 'dice-roller') . '</div>';

	}

	private static function printRolls($num)
	{
			$orderby      = array_key_exists('orderby', $_GET) === TRUE ? $_GET['orderby'] : 'r.id DESC';
			
			if (array_key_exists('num_page', $_GET) === TRUE) {
				$limitOffset  = ((int)$_GET['num_page'] - 1) * self::$rollsPerPage;
			} else {
				$limitOffset  = 0;
			}
			
			$query        = '
			SELECT SQL_CALC_FOUND_ROWS  
				r.roller_ID, c.campaign_name, r.roll_description, r.roll_setup, r.roll_result, r.roll_datetime, c.campaign_name
				FROM ' . self::$tabRoll . ' r INNER JOIN ' . self::$tabCampaign . ' c ON r.campaign_ID = c.id 
				ORDER BY ' . $orderby . '
				LIMIT ' . self::$rollsPerPage . ' OFFSET ' . (string)$limitOffset . ';';
			$result = self::$wpdb->get_results($query, OBJECT);
			
			$baseURL = $_SERVER['PHP_SELF'] . '?' . self::rebuildQueryString(array('orderby', 'num_page'));
			
			?>
			<div class="diceroller-element" id="latest">
			<h3 class="diceroller-init"><?php printf(__('Latest %d rolls', 'dice-roller'), $num); ?></h3>
			<div class="diceroller-container">
			<table class="widefat">
			<thead>
			<tr>
				<th><a href="<?php echo $baseURL; ?>&orderby=roller_ID"><?php _e('User', 'dice-roller'); ?></a></th>
				<th><a href="<?php echo $baseURL; ?>&orderby=campaign_name"><?php _e('Campaign Name', 'dice-roller'); ?></a></th>
				<th><a href="<?php echo $baseURL; ?>&orderby=roll_setup"><?php _e('Setup', 'dice-roller'); ?></a></th>
				<th><a href="<?php echo $baseURL; ?>&orderby=roll_description"><?php _e('Description', 'dice-roller'); ?></a></th>
				<th><a href="<?php echo $baseURL; ?>&orderby=roll_result"><?php _e('Result', 'dice-roller'); ?></a></th>
				<th><a href="<?php echo $baseURL; ?>&orderby=roll_datetime"><?php _e('Executed at', 'dice-roller'); ?></a></th>
			</tr>
			</thead>
			<tbody>
			<?php
			
			foreach ($result as $roll) {
				?>
				<tr>
					<td><?php echo get_userdata($roll->roller_ID)->user_login; ?></td>
					<td><?php echo $roll->campaign_name; ?></td>
					<td><?php echo $roll->roll_setup; ?></td>
					<td><?php echo $roll->roll_description; ?></td>
					<td><?php echo $roll->roll_result; ?></td>
					<td><?php echo $roll->roll_datetime; ?></td>
				</tr>
				<?php
			}
			
			?>
			</tbody>
			</table>
			<?php
			
			$query   = 'SELECT FOUND_ROWS() AS total;';
			$result  = self::$wpdb->get_results($query, OBJECT);
			$total   = $result[0]->total;
			
			if ($total > self::$rollsPerPage) {
				$numPages  = ceil($total / self::$rollsPerPage);
				$curPage   = array_key_exists('num_page', $_GET) === TRUE ? (int)$_GET['num_page'] : 1;
				
				$baseURL = $_SERVER['PHP_SELF'] . '?' . self::rebuildQueryString(array('num_page')) . '&num_page=';
				
				echo '<p>' . __('Pages') . ':<br/>';
				
				for ($i = 1; $i <= $numPages; $i++) {
					if ($i === $curPage) {
						echo ' <strong>' . (string)$i . '</strong>';
					} else {
						echo ' <a href="' . $baseURL . (string)$i . '" '
						     . 'title="' . __('Go to page') .  ' ' . (string)$i . '">' . (string)$i . '</a>';
					}
				}
				
				echo '</p>';
			}
			
			echo '<p>' . $total . ' ' . __('Dice Rolls found') . '</p>';
			
			?>
			</div>
			</div>
			<?php
	}
	
	private static function form()
	{
			$defaults = array(
					'roll_description'  => __('Roll description', 'dice-roller'),
					'M'                 => __('0', 'dice-roller'),
					'N'                 => __('1', 'dice-roller'),
					'result'            => __(0, 'dice-roller')
				);
			
			$instance   = wp_parse_args( $_REQUEST, $defaults );
			$query      = '
				SELECT id, campaign_name 
					FROM ' . self::$tabCampaign . "
					WHERE campaign_active = '1';";
			
			$campaigns = self::$wpdb->get_results($query, OBJECT);
			$action = $_SERVER['PHP_SELF'] . '?' . self::rebuildQueryString(array('orderby', 'num_page'));
			
			?>
			<div class="diceroller-element" id="roll">
			<h3 class="diceroller-init"><?php _e('Make a new roll', 'dice-roller'); ?></h3>
			<form method="post" action="<?php echo $action; ?>">
				<div class="diceroller-container">
				<h4 class="diceroller-title"><?php _e('Campaign:', 'dice-roller'); ?></h4>
				<select id="roll_campaign" name="roll_campaign" class="widefat" style="width: 12em;">
				<?php
				foreach ($campaigns as $campaign) {
					?>
					<option value="<?php echo $campaign->id; ?>" <?php if ($campaign->id === $instance['roll_campaign'] ) echo 'selected="selected"'; ?>><?php echo $campaign->campaign_name; ?></option>
				<?php } ?>
				</select>
				<span class="diceroller-option-description"><?php _e('Select the campaign where the roll actually applies', 'dice-roller'); ?></span>
				<!-- Number of dice N: Text Input -->
				<h4 class="diceroller-title"><?php _e('Number of dice', 'dice-roller'); ?></h4>
				<input id="N" name="N" value="<?php echo $instance['N']; ?>" style="width: 5em;" />
				<span class="diceroller-option-description"><?php _e('Select the number of dice to roll', 'dice-roller'); ?></span>
				<!-- Dice X: Select Box -->
				<h4 class="diceroller-title"><?php _e('Die to roll:', 'dice-roller'); ?></h4>
				<select id="X" name="X" class="widefat" style="width:5em;">
					<option <?php if ( '10' == $instance['X'] ) echo 'selected="selected"'; ?>>10</option>
					<option <?php if ( '100' == $instance['X'] ) echo 'selected="selected"'; ?>>100</option>
				</select>
				<span class="diceroller-option-description"><?php _e('Select the kind of die to roll', 'dice-roller'); ?></span>
				<!-- Modifier N: Text Input -->
				<h4 class="diceroller-title"><?php _e('Modifier:', 'dice-roller'); ?></h4>
				<select id="M" name="M" class="widefat" style="width: 5em;">
					<option <?php if ( '-40' == $instance['M'] ) echo 'selected="selected"'; ?>>-40</option>
					<option <?php if ( '-30' == $instance['M'] ) echo 'selected="selected"'; ?>>-30</option>
					<option <?php if ( '-20' == $instance['M'] ) echo 'selected="selected"'; ?>>-20</option>
					<option <?php if ( '-10' == $instance['M'] ) echo 'selected="selected"'; ?>>-10</option>
	<option <?php if ( '-2' == $instance['M'] ) echo 'selected="selected"'; ?>>-2</option>				
	<option <?php if ( '0' == $instance['M'] ) echo 'selected="selected"'; ?>>0</option>
	<option <?php if ( '+2' == $instance['M'] ) echo 'selected="selected"'; ?>>+2</option>
					<option <?php if ( '10' == $instance['M'] ) echo 'selected="selected"'; ?>>+10</option>
					<option <?php if ( '20' == $instance['M'] ) echo 'selected="selected"'; ?>>+20</option>
					<option <?php if ( '30' == $instance['M'] ) echo 'selected="selected"'; ?>>+30</option>
					<option <?php if ( '40' == $instance['M'] ) echo 'selected="selected"'; ?>>+40</option>
				</select>
				<span class="diceroller-option-description"><?php _e('Select the modifier to apply to the roll', 'dice-roller'); ?></span>
				<h4 class="diceroller-title"><?php _e('Roll description', 'dice-roller'); ?></h4>
				<input id="roll_description" name="roll_description" value="<?php echo $instance['roll_description']; ?>" style="width: 90%;" />
				<span class="diceroller-option-description"><?php _e('Insert a short description for the roll', 'dice-roller'); ?></span>
				<input class="button-primary" type="submit" name="submit" value="<?php _e('Roll', 'dice-roller'); ?>" />
				</div>
			</form>
			</div>
		<?php
	}
	
	public static function handleAdminInit()
	{
		wp_register_style( 'ninthcircle_dr_style', WP_PLUGIN_URL . '/9thcircle_dice_roller/screen.css' );
	}
	
	public static function handleAdminMenu()
	{
		if (function_exists('add_submenu_page')) {
			add_management_page(
					__('9th Circle Games Dice Roller', 'dice-roller'),
					__('Dice Roller', 'dice-roller'),
					7,
					__FILE__,
					'C9\WP_PLUGINS\DiceRoller::mainPage'
				);
		}
		add_action( 'admin_print_styles' . $page, 'C9\WP_PLUGINS\DiceRoller::handleAdminStyles' );	
	}
	
	/**
	 *	\brief		Will be called only on your plugin admin page, enqueue our stylesheet here.
	 *	@return		void
	 */
	public static function handleAdminStyles()
	{
		   wp_enqueue_style( 'ninthcircle_dr_style' );
	}
	   
	public static function handleLatestShortcode($atts)
	{
			self::get_wpdb();
			
			extract(shortcode_atts(array(
					'num'    => 10,
					'class'  => '',
				), $atts));
			
			$query = '
				SELECT c.campaign_name, c.campaign_url, r.roll_description, r.roll_setup, r.roll_result, r.roll_datetime, c.campaign_name  
				FROM ' . self::$tabRoll . ' r INNER JOIN ' . self::$tabCampaign . ' c 
				ON r.campaign_ID = c.id 
				ORDER BY r.id DESC
				LIMIT 0, ' . $num . ';';
			$latest_rolls = self::$wpdb->get_results($query, OBJECT);
			
			?>
			<div class="diceroller-latest-shortcode">
			<table class="<?php echo $class; ?>">
			<thead>
			<tr>
				<th><?php _e('Campaign', 'dice-roller'); ?></th>
				<th><?php _e('Setup', 'dice-roller'); ?></th>
				<th><?php _e('Description', 'dice-roller'); ?></th>
				<th><?php _e('Result', 'dice-roller'); ?></th>
				<th><?php _e('Executed at', 'dice-roller'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($latest_rolls as $roll) { ?>
			<tr>
				<td><?php echo '<a href="' . $roll->campaign_url . '" rel="nofollow">' . $roll->campaign_name . '</a>'; ?></td>
				<td><?php echo $roll->roll_setup; ?></td>
				<td><?php echo $roll->roll_description; ?></td>
				<td><?php echo $roll->roll_result; ?></td>
				<td><?php echo $roll->roll_datetime; ?></td>
			</tr>
				<?php } ?>
			</tbody>
			</table>
			</div>
			<?php
	}
	
	/**
	 *	\brief		If self::$wpdb is not set still, copies global $wpdb variable and prefix 
	 *				into class private properties.
	 *	@return		void
	 */
	private static function get_wpdb()
	{
		global $wpdb;
		
		if (is_object(self::$wpdb) !== TRUE) {
			self::$wpdb          = $wpdb;
			self::$tabCampaign   = $wpdb->prefix . self::TAB_PREFIX . 'campaign';
			self::$tabRoll       = $wpdb->prefix . self::TAB_PREFIX . 'dice_roll';
		}
	}
	
	/**
	 *	\brief		Initializes plugin.
	 *				Registers wp hooks.
	 *	@return		void
	 */
	public static function init()
	{
		register_activation_hook(__FILE__,        'C9\WP_PLUGINS\DiceRoller::handleActivation');
		register_deactivation_hook(__FILE__,      'C9\WP_PLUGINS\DiceRoller::handleDeactivation');
		add_action('admin_menu',                  'C9\WP_PLUGINS\DiceRoller::handleAdminMenu');
		add_action('admin_init',                  'C9\WP_PLUGINS\DiceRoller::handleAdminInit');
		add_shortcode('ninthcircle_latest_roll',  'C9\WP_PLUGINS\DiceRoller::handleLatestShortcode');
	}
}


// pass needed wp vars
DiceRoller::init();

?>