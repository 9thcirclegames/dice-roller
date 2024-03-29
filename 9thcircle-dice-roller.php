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



// include needed core files
require_once __DIR__ . '/libs/exceptions.php';  // custom exceptions
require_once __DIR__ . '/libs/types.php';       // generic types for static analysis
require_once __DIR__ . '/libs/types_c9.php';    // C9 types for static analysis




/**
 *	\class		InfoComponent
 *	\brief		Meta info about components to be loaded.
 */
class InfoComponent
{
	//! Name of the Component.
	private /*. string .*/  $name;
	//! File to be included to load the Component.
	private /*. string .*/  $file;
	
	
	
	/**
	 *	Constructor.
	 *	@param		string		$name		Name of the Component.
	 *	@param		string		$file		Path + name of the (main) file to be loaded.
	 *										If the Component uses more files, it has to include them.
	 *	@return		void
	 */
	public function __construct($name, $file)
	{
		$this->name  = $name;
		$this->file  = $file;
	}
	
	/**
	 *	Returns name of the Component.
	 *	@return		string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 *	Returns path of main file of the Component.
	 *	@return		string
	 */
	public function getFile()
	{
		return $this->file;
	}
}



/**
 *	\brief		Extended by all Components.
 */
class Component
{
	// DB
	
	//! Version of this plugin's tables structure.
	const DB_VERSION  = '0.1';
	//! All options and tables created by this plugin use this prefix.
	//!	It's a sort of namespace, or SQL schema.
	const PREFIX      = 'pbf_';
	
	
	
	// props
	
	//! Names of active Components (must be their main class).
	//! This is an ordered array: Components will be installed in its order,
	//! and uninstalled in reverse order.
	private   static /*. string[int] .*/  $components    = array('Campaign', 'DiceRoller');
	//! Names of SQL Tables.
	protected static /*. string[int] .*/  $SQLTables     = array();
	//! Name of dice roll table.
	protected static /*. string .*/       $tabRoll       = '';
	//! DB connection object.
	protected static /*. wpdb .*/         $wpdb          = NULL;
	
	
	
	
	/**
	 *	\brief		Registers info about a SQL Table, so it can be used in the future
	 *				without hardcoding it.
	 *	@return		void
	 */
	final protected static function registerSQLTable($name)
	{
		if (in_array($name, self::$SQLTables) === FALSE) {
			self::$SQLTables[$name] = self::$wpdb->prefix . self::PREFIX . $name;
		} else {
			throw new \C9\C9Exception('C9 Component Manager', 'SQL Table already registered: ' . $name);
		}
	}
	
	/**
	 *	\brief		Returns a SQL table name, with the wp prefix and the plugin prefix.
	 *	@return		string
	 */
	final protected static function getSQLTableName($name)
	{
		try {
			return self::$SQLTables[$name];
		}
		catch (Exception $e) {
			throw new \C9\C9Exception('C9 Component Manager', 'SQL Table not (yet?) registered: ' . $name);
		}
	}
	
	/**
	 *	\brief		Gets from the environment and the Components all info needed to use DB.
	 *	@return		void
	 */
	final protected static function setDBInfo()
	{
		global $wpdb;
		
		$tables = array();
		
		if (is_object(self::$wpdb) !== TRUE) {
			self::$wpdb = $wpdb;
			
			#self::registerSQLTable('campaign');
			#self::registerSQLTable('dice_roll');
			
			// register Components' SQL tables
			foreach (self::$components as $comp) {
				$tables = call_user_func('C9\\WP_PLUGINS\\' . $comp . '::getDeclaredSQLTables');
				foreach ($tables as $tab) {
					self::registerSQLTable($tab);
				}
			}
		}
	}
	
	/**
	 *	\brief		Called on plugin activation (install).
	 *				Creates all plugin tables and set options.
	 *	@return		void
	 */
	final public static function handleActivation()
	{
		// set generic options
		
		update_option(  self::PREFIX . 'db_version',      self::DB_VERSION);
		
		//db info
		
		$installed_ver = get_option(self::PREFIX . 'db_version');
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		self::setDBInfo();
		
		// install Components
		
		$numComponents = count(self::$components);
		for ($i = 0; $i < $numComponents; $i++) {
			// get actions to be executed
			$actions = call_user_func('C9\\WP_PLUGINS\\' . self::$components[$i] . '::getInstallActions');
			
			$numActions = count($actions);
			for ($j = 0; $j < $numActions; $j++) {
				$curAction = $actions[$j];
				if ($curAction['type'] === 'sql-install') {
					dbDelta($curAction['value']);
				} elseif ($curAction['type'] === 'option') {
					add_option($curAction['name'], $curAction['value']);
				} elseif ($curAction['type'] === 'sql-uninstall') { } 
				  else {
					throw new \C9\C9Exception('C9 Plugin Manager', 'Component ' . self::$components[$i] . ' required an install action of an unknown type: ' . $curAction['type']);
				}
			}
		}
	}
	
	/**
	 *	\brief		Called on plugin de-activation (uninstall).
	 *				Drops all plugin tables and options.
	 *	@return		void
	 */
	final public static function handleUninstall()
	{
		// DB info
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		self::setDBInfo();
		
		// uninstall Components
		
		$numComponents = count(self::$components);
		for ($i = 0; $i < $numComponents; $i++) {
			// get actions to be executed
			$actions = call_user_func('C9\\WP_PLUGINS\\' . self::$components[$i] . '::getInstallActions');
			
			$numActions = count($actions);
			for ($j = 0; $j < $numActions; $j++) {
				$curAction = $actions[$j];
				if ($curAction['type'] === 'sql-uninstall') {
					dbDelta($curAction['value']);
				} elseif ($curAction['type'] === 'option') {
					delete_option($curAction['name']);
				} elseif ($curAction['type'] === 'sql-install') { } 
				  else {
					throw new \C9\C9Exception('C9 Plugin Manager', self::$components[$i] . ' required an uninstall action of an unknown type: ' . $curAction['type']);
				}
			}
		}
		
		// remove plugin options
		
		delete_option(self::PREFIX . 'db_version');
		delete_option(self::PREFIX . 'rolls_per_page');
	}
	
	/**
	 *	\brief		Initializes plugin.
	 *				Registers wp hooks.
	 *	@return		void
	 */
	public static function init()
	{
		// generic hooks
		register_activation_hook(__FILE__,  'C9\\WP_PLUGINS\\Component::handleActivation');
		register_uninstall_hook(__FILE__,   'C9\\WP_PLUGINS\\Component::handleUninstall');
		
		$numComponents = count(self::$components);
		for ($i = 0; $i < $numComponents; $i++) {
			// get actions to be executed
			$actions = call_user_func('C9\\WP_PLUGINS\\' . self::$components[$i] . '::getInitActions');
			
			$numActions = count($actions);
			for ($j = 0; $j < $numActions; $j++) {
				$curAction = $actions[$j];
				if ($curAction['type'] === 'action') {
					add_action($curAction['key'], $curAction['value']);
				} elseif ($curAction['type'] === 'shortcode') {
					add_shortcode($curAction['key'], $curAction['value']);
				} else {
					throw new \C9\C9Exception('C9 Plugin Manager', 'Component ' . self::$components[$i] . ' required an init action of an unknown type: ' . $curAction['type']);
				}
			}
		}
		
		// call components initializers
		foreach (self::$components as $cName) {
			call_user_func('C9\\WP_PLUGINS\\' . $cName . '::init');
		}
	}
	
	/**
	 *	\brief		Returns a querystring identical to the old one.
	 *				If $skipValues is passed, some values are excluded.
	 *	@param		string[]		$skipValues			Values to exlude.
	 *	@return		string
	 */
	final protected static function rebuildQueryString($skipValues)
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
	
	/**
	 *	\brief		Error if you try to instantiate static class.
	 *	@return		void
	 */
	final public function __construct()
	{
		throw new \C9\C9Exception('Class is static and can not be instantiated: ' . __CLASS__);
	}
}



/**
 *	\interface		iComponent
 *	\brief			All Component's MUST implement this interface.
 */
interface iComponent
{
	//! Version of this interface.
	const API_VERSION  = '0.1';
	
	
	/**
	 *	\brief		Returns names of SQL Tables used by the Component.
	 *	@return		string[]
	 */
	public static function getDeclaredSQLTables();
	
	/**
	 *	\brief		Returns actions required to install/uninstall the Component.
	 *	@return		array
	 */
	public static function getInstallActions();
	
	/**
	 *	\brief		Returns actions required to initialize the Component.
	 *	@return		array
	 */
	public static function getInitActions();
	
	/**
	 *	\brief		Executes actions required to initialize the Component, or does nothing.
	 *				For example, this can be used to set class properties.
	 *	@return		void
	 */
	public static function init();
	
	/**
	 *	\brief		Returns Component's version.
	 *				There is no way to require this, but please, add a COMPONENT_VERSION constant.
	 *	@return		string
	 */
	public static function getVersion();
}



class DiceRoller
	extends Component
	implements iComponent
{
	//!	This Component's version.
	const COMPONENT_VERSION  = '0.1';
	
	//!	Default value for 'rolls_per_page' option.
	const DEFAULT_ROLLS_PER_PAGE  = 10;
	
	
	
	// props
	
	//! Number of rolls per page.
	private static /*. int .*/     $rollsPerPage  = 0;
	
	
	
	
	/**
	 *	\brief		See iComponent.
	 *	@return		string
	 */
	public static function getVersion()
	{
		return self::COMPONENT_VERSION;
	}
	
	public static function mainPage()
	{
		self::setDBInfo();
		
		echo '<div class="wrap"><h2>' . __("9th Circle Games Dice Roller", 'dice-roller') . '</h2>';
		if ($_REQUEST['submit']) {
			self::roll();
		}
		self::form();
		self::printRolls();
		echo '</div>';
	}
	
	private static function roll()
	{
		$dices   = (int)$_REQUEST['N'];  // number of dices to be rolled
		$faces   = (int)$_REQUEST['X'];  // faces of dices
		$result  = (int)$_REQUEST['M'];  // bonus/malus (initial value)
		
		$rollSetup = $_REQUEST['N'] . "d" . $_REQUEST['X'] . ($_REQUEST['M'] === '0' ? '' : $_REQUEST['M']);
		
		while ($dices > 0) {
			$result += mt_rand(1, $faces);
			$dices--;
		}
		
		$data = array(
			'roller_ID'         => get_current_user_id(),
			'campaign_ID'       => $_REQUEST['roll_campaign'],
			'roll_description'  => $_REQUEST['roll_description'],
			'roll_setup'        => $rollSetup,
			'roll_result'       => $result,
			'roll_datetime'     => date('Y-m-d H:m:s')
			);
		
		self::$wpdb->insert(self::getSQLTableName('dice_roll'), $data);
		
		if(self::$wpdb->insert_id !== FALSE) {
			echo '<div class="updated">';
			_e('Roll done!', 'dice-roller');
			echo '<br />';
			printf(__('Result: %s = %d', 'dice-roller'), $rollSetup, $result );
			echo '</div>';
		} else {
			echo '<div class="error">' . __('Error in updating roll table', 'dice-roller') . '</div>';
		}
	}

	private static function printRolls()
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
				FROM ' . self::getSQLTableName('dice_roll') . ' r INNER JOIN ' . self::getSQLTableName('campaign') . ' c ON r.campaign_ID = c.id 
				ORDER BY ' . $orderby . '
				LIMIT ' . self::$rollsPerPage . ' OFFSET ' . (string)$limitOffset . ';';
			$result = self::$wpdb->get_results($query, OBJECT);
			
			$baseURL = $_SERVER['PHP_SELF'] . '?' . self::rebuildQueryString(array('orderby', 'num_page'));
			
			?>
			<div class="diceroller-element" id="latest">
			<h3 class="diceroller-init"><?php printf(__('Latest %d rolls', 'dice-roller'), self::$rollsPerPage); ?></h3>
			<div class="diceroller-container">
			<table class="widefat">
			<thead>
			<tr>
				<th><a href="<?php echo $baseURL; ?>&orderby=roller_ID"><?php _e('User', 'dice-roller'); ?></a></th>
				<th><a href="<?php echo $baseURL; ?>&orderby=campaign_name"><?php _e('Campaign Name', 'dice-roller'); ?></a></th>
				<th><a href="<?php echo $baseURL; ?>&orderby=roll_setup" class="c9-nobr"><?php _e('Setup', 'dice-roller'); ?></a></th>
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
					FROM ' . self::getSQLTableName('campaign') . "
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
		wp_register_style('ninthcircle_dr_style', WP_PLUGIN_URL . '/9thcircle_dice_roller/screen.css');
	}
	
	public static function handleAdminMenu()
	{
		if (function_exists('add_submenu_page')) {
			add_management_page(
					__('9th Circle Games Dice Roller', 'dice-roller'),
					__('Dice Roller', 'dice-roller'),
					7,
					__FILE__,
					'C9\\WP_PLUGINS\\DiceRoller::mainPage'
				);
		}
		add_action( 'admin_print_styles' . $page, 'C9\\WP_PLUGINS\\DiceRoller::handleAdminStyles' );	
	}
	
	/**
	 *	\brief		Will be called only on your plugin admin page, enqueue our stylesheet here.
	 *	@return		void
	 */
	public static function handleAdminStyles()
	{
		   wp_enqueue_style('ninthcircle_dr_style');
	}
	
	public static function handleLatestShortcode($atts)
	{
			self::setDBInfo();
			
			extract(shortcode_atts(array(
					'count'    => self::$rollsPerPage,
					'class'  => '',
				), $atts));
			
			$query = '
				SELECT c.campaign_name, c.campaign_url, r.roll_description, r.roll_setup, r.roll_result, r.roll_datetime, c.campaign_name  
				FROM ' . self::getSQLTableName('dice_roll') . ' r INNER JOIN ' . self::getSQLTableName('campaign') . ' c 
				ON r.campaign_ID = c.id 
				ORDER BY r.id DESC
				LIMIT 0, ' . $count . ';';
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
	 *	\brief		See iComponent.
	 *	@return		array
	 */
	public static function getInstallActions()
	{
		return array(
				0 => array(
						'type'   => 'sql-install',
						'value'  => 
							'CREATE TABLE ' . self::getSQLTableName('dice_roll') . ' (
							id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
							roller_ID BIGINT NOT NULL,
							campaign_ID SMALLINT NOT NULL,
							roll_description CHAR(255),
							roll_setup VARCHAR(25) NOT NULL,
							roll_result SMALLINT NOT NULL,
							roll_datetime DATETIME,
							PRIMARY KEY (id)
							);'
					),
				1 => array(
						'type'   => 'sql-uninstall',
						'value'  => 'DROP TABLE IF EXISTS ' . self::getSQLTableName('dice_roll') . ';'
					),
				2 => array(
						'type'   => 'option',
						'name'   => 'rolls_per_page',
						'value'  => DiceRoller::DEFAULT_ROLLS_PER_PAGE
					)
			);
	}
	
	/**
	 *	\brief		See iComponent.
	 *	@return		array
	 */
	public static function getInitActions()
	{
		return array(
				0 => array(
						'type'      => 'action',
						'key'       => 'admin_menu',
						'value'     => 'C9\\WP_PLUGINS\\DiceRoller::handleAdminMenu'
					),
				1 => array(
						'type'      => 'action',
						'key'       => 'admin_init',
						'value'     => 'C9\\WP_PLUGINS\\DiceRoller::handleAdminInit'
					),
				2 => array(
						'type'      => 'shortcode',
						'key'       => 'c9_dice_rolls',
						'value'     => 'C9\\WP_PLUGINS\\DiceRoller::handleLatestShortcode'
					)
			);
	}
	
	/**
	 *	\brief		Gets options or defaults.
	 *	@return		void
	 */
	public static function init()
	{
		self::$rollsPerPage = get_option(self::PREFIX . 'rolls_per_page', self::DEFAULT_ROLLS_PER_PAGE);
	}
	
	/**
	 *	\brief		See iComponent.
	 *	@return		string[]
	 */
	public static function getDeclaredSQLTables()
	{
		return array('dice_roll');
	}
}



/*
 *	\brief		Component for Campaign management.
 */
class Campaign
	extends Component
	implements iComponent
{
	const COMPONENT_VERSION  = '0.2';
	
	
	/**
	 *	\brief		See iComponent.
	 *	@return		string
	 */
	public static function getVersion()
	{
		return self::COMPONENT_VERSION;
	}
	
	/**
	 *	\brief		See iComponent.
	 *	@return		array
	 */
	public static function getInstallActions()
	{
		return array(
				0 => array(
					'type'   => 'sql-install',
					'value'  => 
						'CREATE TABLE ' . self::getSQLTableName('campaign') . ' (
						id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
						master_ID BIGINT NOT NULL,
						campaign_name CHAR(128) NOT NULL,
						campaign_description CHAR(255) NOT NULL,
						campaign_url VARCHAR(400) NOT NULL,
						campaign_active ENUM(\'0\',\'1\') NOT NULL,
						campaign_start_date TIMESTAMP,
						campaign_end_date TIMESTAMP,
						PRIMARY KEY (id)
						);'
					),
				1 => array(
						'type'   => 'sql-uninstall',
						'value'  => 'DROP TABLE IF EXISTS ' . self::getSQLTableName('campaign') . ';'
					),
				
				## DBUG
				2 => array(
						'type'   => 'sql-install',
						'value'  => "INSERT INTO 9th_pbf_campaign (`id`, `campaign_name`, `campaign_description`, `campaign_active`) VALUES (1, 'Le montagne della follia', 'Let\'s test the Dice Roller!!!', '1');"
					),
				3 => array(
						'type'   => 'sql-install',
						'value'  => "INSERT INTO 9th_pbf_campaign (`id`, `campaign_name`, `campaign_description`, `campaign_active`) VALUES (2, 'L\'ombra calata dal tempo', 'We all love rolling', '1');"
					),
				4 => array(
						'type'   => 'sql-install',
						'value'  => "INSERT INTO 9th_pbf_campaign (`id`, `campaign_name`, `campaign_description`, `campaign_active`) VALUES (3, 'NON ATTIVA', 'errore!!! non dovresti vedermi!!!', '0');"
					)
			);
	}
	
	/**
	 *	\brief		See iComponent.
	 *	@return		array
	 */
	public static function getInitActions()
	{
		return array();
	}
	
	/**
	 *	\brief		Gets options or defaults.
	 *	@return		void
	 */
	public static function init() { }
	
	/**
	 *	\brief		See iComponent.
	 *	@return		string[]
	 */
	public static function getDeclaredSQLTables()
	{
		return array('campaign');
	}
}



// init plugin and components
Component::init();

?>