<?php
/*
Plugin Name: Sidebar Widget Calendar
Plugin URI: http://eventario.bravesites.com
Description: Add calendar in your website using shortcode or by drag and drop from your widget sidebar. Write your title, time slot, notes, date and let your visitors see when you are available for them using the sidebar widget calendar.
Version: 1.0.3
Author: Ralf Oberhausen
Author URI: http://eventario.bravesites.com
*/

/*  Copyright 2014 Ralf Oberhausen (E-mail: ralfisend@mail.com)

		This program is free software; you can redistribute it and/or
		modify it under the terms of the GNU General Public License
		as published by the Free Software Foundation; either version 2
		of the License, or (at your option) any later version.
		
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.
		
		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
include_once(ABSPATH.'wp-admin/admin-functions.php');

require_once(ABSPATH.'wp-content/plugins/sidebar-widget-calendar/functions.php'); 

global $wpdb, $entries_table;

$entries_table  = 'diary_and_availability_calendar';

// BEGIN CLASS

if(!class_exists('DiaryAndAvailabilityCalendar')) {
	class DiaryAndAvailabilityCalendar {
		function DiaryAndAvailabilityCalendar() {
			add_action('init', array($this, 'swgc_register_assets'));
			add_action('admin_init', array($this, 'swgc_admin_init'));
			add_action('admin_init', array($this, 'swgc_register_settings'));
			add_action('admin_menu', array($this, 'swgc_admin_menu'));
			add_action('widgets_init', array($this, 'swgc_widgets_init'));
			add_action('get_header', array($this, 'swgc_get_header'));
			add_shortcode('swgc', array($this, 'swgc_show_calendar_shortcode'));
		}
		function swgc_register_assets() {
			/**
				*	Init
				*/
			wp_register_style('swgc_admin_stylesheet', WP_PLUGIN_URL . '/sidebar-widget-calendar/css/admin.css');
			wp_register_style('swgc_calendar_stylesheet', WP_PLUGIN_URL . '/sidebar-widget-calendar/css/calendar.css');
			wp_register_script('swgc_calendar_script_backend', WP_PLUGIN_URL . '/sidebar-widget-calendar/js/calendar-backend.js');
			wp_register_script('swgc_calendar_script_frontend', WP_PLUGIN_URL . '/sidebar-widget-calendar/js/calendar-frontend.js', 'jquery');
		}
		function swgc_admin_init() {
			/**
				*	Admin initialize
				*/
			add_action('wp_ajax_swgc_fetch_booked_times', array($this, 'swgc_fetch_booked_times_callback'), 10);
			add_action('wp_ajax_swgc_fetch_available_slots', array($this, 'swgc_fetch_available_slots_callback'), 10);
			add_action('wp_ajax_nopriv_swgc_fetch_available_slots', array($this, 'swgc_fetch_available_slots_callback'), 10);
			add_action('wp_ajax_swgc_fetch_calendar', array($this, 'swgc_fetch_calendar_callback'), 10);
			add_action('wp_ajax_nopriv_swgc_fetch_calendar', array($this, 'swgc_fetch_calendar_callback'), 10);
			add_action('wp_ajax_swgc_validate_booking', array($this, 'swgc_validate_booking_callback'), 10);
			add_action('wp_ajax_swgc_fetch_booking_form', array($this, 'swgc_fetch_booking_form_callback'), 10);
			add_action('wp_ajax_swgc_delete_booking', array($this, 'swgc_delete_booking_callback'), 10);
			add_action('wp_ajax_swgc_refresh_calendar', array($this, 'swgc_refresh_calendar_callback'), 10);
			add_action('wp_ajax_swgc_make_day_unavailable', array($this, 'swgc_make_day_unavailable_callback'), 10);
		}
		
		/**
		 * SETTINGS
		 */
		 
		function swgc_register_settings() {
			/**
			 * Register plugin settings
			 */
			
			// Add sections
			add_settings_section('swgc_settings_main', 'General Settings', array($this, 'swgc_settings_main_output'), 'swgc-settings' );
			
			// Add fields
			add_settings_field('swgc_footer_note', 'Footer Note', array($this, 'swgc_footer_note_callback'), 'swgc-settings', 'swgc_settings_main');
			add_settings_field('swgc_start_hour', 'Earliest hour for entries', array($this, 'swgc_start_hour_callback'), 'swgc-settings', 'swgc_settings_main');
			add_settings_field('swgc_end_hour', 'Latest hour for entries', array($this, 'swgc_end_hour_callback'), 'swgc-settings', 'swgc_settings_main');
			add_settings_field('swgc_minutes_increment', 'Minutes field increment', array($this, 'swgc_minutes_increment_callback'), 'swgc-settings', 'swgc_settings_main');
			add_settings_field('swgc_shortest_slot', 'Shortest possible time slot', array($this, 'swgc_shortest_slot_callback'), 'swgc-settings', 'swgc_settings_main');
			
			// Register settings
			register_setting('swgc-settings', 'swgc_footer_note');
			register_setting('swgc-settings', 'swgc_start_hour');
			register_setting('swgc-settings', 'swgc_end_hour');
			register_setting('swgc-settings', 'swgc_minutes_increment');
			register_setting('swgc-settings', 'swgc_shortest_slot', array($this, 'swgc_shortest_slot_validate'));
		}
		function swgc_settings_main_output() {
			echo '<p>Adjust these settings according to your requirements.</p>';
		}
		function swgc_footer_note_callback() {
			echo '<textarea rows="4" cols="50" name="swgc_footer_note">'.get_option('swgc_footer_note').'</textarea>';
			echo '<p class="description">This is a short description shown beneath the calendar</p>';
		}
		function swgc_start_hour_callback() {
			$value = get_option('swgc_start_hour', 0);
			
			echo '<select name="swgc_start_hour">';
			for($i=0; $i<24; $i++) {
				echo '<option value="'.$i.'" '.($value == $i ? 'selected="selected"' : '').'>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
			}
			echo '</select>';
		}
		function swgc_end_hour_callback() {		
			$value = get_option('swgc_end_hour', 23);
				
			echo '<select name="swgc_end_hour">';			
			for($i=0; $i<24; $i++) {
				echo '<option value="'.$i.'" '.($value == $i ? 'selected="selected"' : '').'>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
			}
			echo '</select>';
			echo '<p class="description">These two fields are useful if you only intend to create entries between certain hours, from 9am to 5pm for example.<br />The 24 hour clock format is used, use default values (giving you the full 24 hour range) if you\'re unsure what might suit you best.</p>';
		}
		function swgc_minutes_increment_callback() {
			$value = get_option('swgc_minutes_increment', 15);
			
			echo '<select name="swgc_minutes_increment">';
			echo '<option value="1" '.($value == 1 ? 'selected="selected"' : '').'>1</option>';
			echo '<option value="5" '.($value == 5 ? 'selected="selected"' : '').'>5</option>';
			echo '<option value="10" '.($value == 10 ? 'selected="selected"' : '').'>10</option>';
			echo '<option value="15" '.($value == 15 ? 'selected="selected"' : '').'>15</option>';
			echo '<option value="30" '.($value == 30 ? 'selected="selected"' : '').'>30</option>';
			echo '</select>';
			echo '<p class="description">Choose how precise you\'d like your entries to be by specifying an increment for the minutes field.<br />For example, choosing <strong>15</strong> will let you specify times in the format: <strong>09:00</strong>, <strong>09:15</strong>, <strong>09:30</strong>, <strong>09:45</strong>.<br />You can always change this later.</p>';
		}
		function swgc_shortest_slot_callback() {
			echo '<input style="text-align:right" name="swgc_shortest_slot" size="5" maxlength="3" type="text" value="'.get_option('swgc_shortest_slot', 30).'" /> <em>minutes</em>';
			echo '<p class="description">The minimum length of time you need free in order for a slot to show as available.</p>';
		}
		function swgc_shortest_slot_validate($input) {
			if(!is_numeric($input)) {
				return get_option('swgc_shortest_slot', 30);
			}
			else if($input <= 0) {
				return get_option('swgc_shortest_slot', 30);
			}
			else {
				return number_format($input, 0, '', '');
			}
		}
		
		/**
		 * END SETTINGS 
		 */
		 

		function swgc_admin_menu() {
			/**
				* Admin menu initialize functions
				*/
			$swgcDashboard = add_menu_page('Sidebar Widget Calendar', 'Calendar', 'manage_options', 'swgc-calendar', array($this,'swgc_dashboard')); // Main plugin admin
			$swgcSettings = add_options_page('Sidebar Widget Calendar', 'Sidebar Widget Calendar', 'manage_options', 'swgc-settings', array($this,'swgc_settings')); // Plugin settings
			add_action('admin_print_styles-' . $swgcDashboard, array($this, 'swgc_admin_styles'));
			add_action('admin_print_scripts-' . $swgcDashboard, array($this, 'swgc_admin_scripts'));
		}
		function swgc_get_header() {
			/**
				*	Runs when the template calls the get_header function, just before the header.php template file is loaded.
				*/
			add_action('wp_print_styles', array($this,'swgc_wp_styles'));
			add_action('wp_print_scripts', array($this, 'swgc_wp_scripts'));
		}
		function swgc_admin_styles() {
			/**
				*	Admin / WP stylesheets
				*/
			wp_enqueue_style('swgc_admin_stylesheet');
			wp_enqueue_style('swgc_calendar_stylesheet');
		}
		function swgc_admin_scripts() {
			/**
				*	Admin scripts
				*/
			wp_enqueue_script('swgc_calendar_script_backend');
			wp_localize_script('swgc_calendar_script_backend', 'MyAjax', array('ajaxurl' => admin_url( 'admin-ajax.php' )));
		}
		function swgc_wp_styles() {
			/**
				* Frontend styles
				*/
			wp_enqueue_style('swgc_calendar_stylesheet');
		}
		function swgc_wp_scripts() {
			/**
				* Frontend scripts
				*/
			wp_enqueue_script('jquery');
			wp_enqueue_script('swgc_calendar_script_frontend');
			wp_localize_script('swgc_calendar_script_frontend', 'MyAjax', array('ajaxurl' => admin_url( 'admin-ajax.php' )));
		}
		function swgc_dashboard() {
			global $wpdb;
			
			echo '
				<div class="wrap">
					<h2>Sidebar Widget Calendar</h2>
					<div class="swgc-wrapper">
						<div class="column first">
							<div id="swgc-calendar">
								<div id="swgc-calendar-async">
									'.swgc_make_calendar(date('Y'), date('m'), '', 3, NULL, 1, NULL, false).'
								</div>
								'.(is_admin() ? '
								<div id="swgc-mark-days-wrapper">
									<a href="javascript:void(null);" id="swgc-mark-days">Mark Unavailable Days</a>
								</div>' : '').'
								'.$this->swgc_show_legend().'
							</div>
							<div id="swgc-bookings">
								<div id="swgc-bookings-async">
									'.$this->swgc_make_bookings_html(date('Y'), date('m'), date('d')).'
								</div>
							</div>
						</div>
						<div class="column last">
							<div id="swgc-form-container">
								'.$this->swgc_get_booking_form(false, date('Y'), date('m'), date('d')).'
							</div>
						</div>
					</div>
				</div>
			';
		}
		function swgc_settings() {
			echo '
				<div class="wrap">
					<h2>Sidebar Widget Calendar | Settings</h2>
					<form method="post" action="options.php">
			';
			
			settings_fields('swgc-settings');
			do_settings_sections('swgc-settings');
			submit_button();
			
			echo '
					</form>
				</div>
			';
						
		}
		function swgc_make_bookings_html($year, $month, $day) {
			$entries = swgc_get_entries($year, $month, $day);
			
			$entriesHTML = '<h3>Entries ('.implode('/', array($day,$month,$year)).')</h3>';
			
			if(count($entries) > 0) {
				foreach($entries as $booking) {
					$entriesHTML .= '
						<div class="swgc-bookings-booking">
							<span class="booking-start">
								'.wp_specialchars($booking["start"]).' - 
							</span>
							<span class="-swgc-bookings-booking-end">
								'.wp_specialchars($booking["end"]).'
							</span>
							<span class="swgc-bookings-booking-title">
								<a rel="'.$booking["id"].'" href="javascript:void(null);">
									'.wp_specialchars($booking["title"]).'
								</a>
							</span>
						</div>
					';
				}
			}
			else {
				$entriesHTML .= 'There are no entries for this day';
			}
						
			return $entriesHTML;
		}
		function swgc_fetch_booked_times_callback() {
			$date = explode('-', $_POST["date"]);
						
			print $this->swgc_make_bookings_html($date[0], $date[1], $date[2]);
			
			die();
		}
		function swgc_fetch_calendar_callback() {
			$direction = $_POST["direction"];
			$currentMonth = $_POST["currentMonth"];
			$currentYear = $_POST["currentYear"];
			$activeDay = $_POST["activeDay"];
			
			$currentMonth = $direction == 'forward' ? $currentMonth + 1 : $currentMonth - 1;
			
			print swgc_make_calendar($currentYear, $currentMonth, $activeDay, 3, NULL, 1, NULL, false);
			
			die();
		}
		function swgc_show_legend() {
			$legend = '';
			
			$legend .= '
				<div id="swgc-legend">
					<div class="legend-item">
						<span class="available">_</span>Available all day
					</div>
					<div class="legend-item">
						<span class="bookings">_</span>Some slots taken, see below
					</div>
					<div class="legend-item">
						<span class="unavailable">_</span>Unavailable
					</div>
				</div>
			';

			return $legend;
		}
		function swgc_make_day_unavailable_callback() {
			global $wpdb;
			
			$date = $_POST["date"];
			
			$unavailableDays = get_option('swgc_unavailable_days');
			
			if(array_search($date, $unavailableDays) !== false) {
				$key = array_search($date, $unavailableDays);
				
				if(count($unavailableDays) == 1) {
					$unavailableDays = array();
				}
				else {
					unset($unavailableDays[$key]);
				}
			}
			else {
				$unavailableDays[] = $date;
			}
			
			update_option('swgc_unavailable_days', $unavailableDays);
				
			die();
		}
		function swgc_get_booking_form($id=false, $year='', $month='', $day='', $start_h='', $end_h='', $increment_m='') {
			global $wpdb, $entries_table;
			
			if(!$start_h) {
				$start_h = get_option('swgc_start_hour', 0);
			}
			
			if(!$end_h) {
				$end_h = get_option('swgc_end_hour', 23);
			}
			
			if(!$increment_m) {
				$increment_m = get_option('swgc_minutes_increment', 15);
			}
			
			if($id) {
				// Query database
				$sql = "select * from ".$wpdb->prefix.$entries_table." where id = $id";
				$results = $wpdb->get_row($sql, ARRAY_A);
				
				if(count($results) > 0) {
					$edit = true;
					
					$editDate = explode('-', $results['date']);
					$editDate = array_reverse($editDate);
					$editDate = implode('/', $editDate);
					$editStart = explode(':',$results['start']);
					$editEnd = explode(':', $results['end']);
					$editTitle = $results['title'];
					$editDetails = $results['details'];
				}
			}
			
			$startHoursOpt = '';
			$startMinutesOpt = '';
			$endHoursOpt = '';
			$endMinutesOpt = '';
			
			// Start Hour
			for($i=$start_h; $i<=$end_h; $i++) {
				$paddedVal = str_pad($i, 2, '0', STR_PAD_LEFT);
				
				if($edit) {
					$selected = $editStart[0] == $paddedVal ? 'selected="selected"' : '';
				}
				$startHoursOpt .= '<option '.$selected.' value="'.$paddedVal.'">'.$paddedVal.'</option>';
			}
			
			// Start Minutes
			for($i=0, $j=0; $j<ceil(60 / $increment_m); $i+=$increment_m, $j++) {
				$paddedVal = str_pad($i, 2, '0', STR_PAD_LEFT);
				
				if($edit) {
					$selected = $editStart[1] == $paddedVal ? ' selected="selected" ' : '';
				}
				$startMinutesOpt .= '<option '.$selected.' value="'.$paddedVal.'">'.$paddedVal.'</option>';
			}
			
			// End Hour
			for($i=$start_h; $i<=$end_h; $i++) {
				$paddedVal = str_pad($i, 2, '0', STR_PAD_LEFT);
				
				if($edit) {
					$selected = $editEnd[0] == $paddedVal ? ' selected="selected" ' : '';
				}
				$endHoursOpt .= '<option '.$selected.' value="'.$paddedVal.'">'.$paddedVal.'</option>';
			}
			
			// End Minutes
			for($i=0, $j=0; $j<ceil(60 / $increment_m); $i+=$increment_m, $j++) {
				$paddedVal = str_pad($i, 2, '0', STR_PAD_LEFT);
				
				if($edit) {
					$selected = $editEnd[1] == $paddedVal ? ' selected="selected" ' : '';
				}
				$endMinutesOpt .= '<option '.$selected.' value="'.$paddedVal.'">'.$paddedVal.'</option>';
			}
			
			$form = '
				<h3>'.($edit ? 'Edit' : 'Create New').' Entry</h3>
				<form id="swgc-booking-form" action="#" method="post">
					<fieldset>
						<label for="swgc-booking-form-title">
							Entry Title *:
						</label>
						<input type="text" name="swgc-booking-form-title"'.($edit ? ' value="'.$editTitle.'"' : '').' />
					</fieldset>
					<fieldset>
						<label for="swgc-booking-form-date">
							Date (set using the calendar):
						</label>
						<input autocomplete="off" type="text" disabled="disabled" name="swgc-booking-form-date" id="swgc-booking-form-date" value="'.($edit ? $editDate : implode('/', array($day, $month, $year))).'" />
					</fieldset>
					<fieldset class="split-column">
						<fieldset class="first">
							<label for="swgc-booking-form-start-h">
								Start Time:
							</label>
							<select name="swgc-booking-form-start-h" id="swgc-booking-form-start-h">'.$startHoursOpt.'</select> : <select name="swgc-booking-form-start-m" id="swgc-booking-form-start-m">'.$startMinutesOpt.'</select>
						</fieldset>
						<fieldset class="last">
							<label for="swgc-booking-form-end">
								End Time:
							</label>
							<select name="swgc-booking-form-end-h" id="swgc-booking-form-end-h">'.$endHoursOpt.'</select> : <select name="swgc-booking-form-end-m" id="swgc-booking-form-end-m">'.$endMinutesOpt.'</select>
						</fieldset>
					</fieldset>
					<fieldset>
						<label for="swgc-booking-form-details">
							Notes:
						</label>
						<textarea name="swgc-booking-form-details">'.($edit ? $editDetails : '').'</textarea>
					</fieldset>
					<fieldset>
						<input class="button-primary" type="submit" value="'.($edit ? 'Update Booking' : 'Save Booking').'" />
						'.($edit ? '<input id="swgc-delete-booking" class="button-secondary" type="submit" value="Delete Booking" /><input type="hidden" name="swgc-edit-id" id="swgc-edit-id" value="'.$id.'" />' : '').'
					</fieldset>
				</form>
				<div id="swgc-error-log"><h4>There were problems with your entry:</h4></div>
			';
			
			return $form;
		}
		function swgc_validate_booking_callback() {
			global $wpdb;
		
			$errors = array();
			
			foreach($_POST as $name=>&$val) {
						
				if($name == 'swgc-booking-form-title') {
					$val = trim($val);
					if(empty($val)) {
						$errors[] = "The 'Booking Title' field may not be left blank";							
					}
				}
				if($name == 'swgc-booking-form-date') {
					// Shouldn't need to do anything here
				}
				if($name == 'swgc-booking-form-end-h') {
					if($val <= $_POST["swgc-booking-form-start-h"]) {
						if($val < $_POST["swgc-booking-form-start-h"]) {
							// End hour is before start hour
							$errors[] = "You may not specify an end time that is earlier than your start time";
						}
						if($val == $_POST["swgc-booking-form-start-h"]) {
							if($_POST["swgc-booking-form-end-m"] <= $_POST["swgc-booking-form-start-m"]) {
								$errors[] = "You may not specify an end time that is earlier than or equal to your start time";
							}
						}
					}
				}
				if($name == 'swgc-booking-form-details') {
					// Shouldn't need to do anything here
				}
			}
			
			if(count($errors) == 0) {
				if($this->swgc_insert_update_booking($_POST) <= 0) {
					$errors[] = 'It was not possible to create or update a record of this booking in the database.';
				}
			}
			
			$return = array(
				'num_errors' => count($errors),
				'errors' => $errors
			);
			
			print json_encode($return);

			die();
		}
		function swgc_insert_update_booking($postData) {
			global $wpdb, $entries_table;
			
			$ebcTitle = $postData['swgc-booking-form-title'];
			$ebcDate = explode('/',$postData['swgc-booking-form-date']);
			$ebcDate = array_reverse($ebcDate);
			$ebcDate = implode('-', $ebcDate);
			$ebcStartTime = implode(':', array($postData['swgc-booking-form-start-h'], $postData['swgc-booking-form-start-m'], '00'));
			$ebcEndTime = implode(':', array($postData['swgc-booking-form-end-h'], $postData['swgc-booking-form-end-m'], '00'));
			$ebcDetails = $postData['swgc-booking-form-details'];
			
			if(array_key_exists('swgc-edit-id', $postData)) {
				// We are editing an existing booking
				$ebcEditId = $postData['swgc-edit-id'];
				// Update
				return $wpdb->update($wpdb->prefix.$entries_table, array('date'=>$ebcDate, 'start'=>$ebcStartTime, 'end'=>$ebcEndTime, 'title'=>$ebcTitle, 'details'=>$ebcDetails), array('id'=>$ebcEditId), '%s', '%d');
			}
			else {
				// Insert
				$wpdb->insert($wpdb->prefix.$entries_table, array('date'=>$ebcDate, 'start'=>$ebcStartTime, 'end'=>$ebcEndTime, 'title'=>$ebcTitle, 'details'=>$ebcDetails)); // All treated as strings ('%s')
				
				return $wpdb->insert_id ? true : false;
			}
		}
		function swgc_fetch_booking_form_callback() {
			if(array_key_exists('id', $_POST)) {
				print $this->swgc_get_booking_form($_POST["id"]);
			}
			elseif(array_key_exists('date', $_POST)) {
				$date = explode('-', $_POST["date"]);
				print $this->swgc_get_booking_form(false, $date[0], $date[1], $date[2]);
			}
			
			die();
		}
		function swgc_delete_booking_callback() {
			global $wpdb, $entries_table;
			
			$id = $_POST["id"];
			
			$wpdb->query("DELETE FROM ".$wpdb->prefix.$entries_table." WHERE id = $id");
			
			die();
		}
		function swgc_refresh_calendar_callback() {
			$date = explode('-', $_POST["date"]);
			
			print swgc_make_calendar($date[0], $date[1], $_POST["date"], 3, NULL, 1, NULL, false);
			
			die();
		}
		function swgc_widgets_init() {
			wp_register_sidebar_widget('swgc-widget-show-calendar', 'Sidebar Widget Calendar', array($this, 'swgc_widget_show_calendar'), array('classname'=>'swgc_booking_calendar', 'description'=>'Display your Availability Calendar'));
			wp_register_widget_control('swgc-widget-show-calendar', 'Sidebar Widget Calendar Options', array($this, 'swgc_widget_show_calendar_control'));
		}
		function swgc_widget_show_calendar($args) {
			$opts = get_option('swgc_widget_show_calendar', false);
			
			$title = '';
			
			if($opts) {
				if(array_key_exists('swgc_widget_show_calendar_title', $opts)) {
					$title = $args['before_title'].$opts['swgc_widget_show_calendar_title'].$args['after_title'];
				}
			}
			
			$widget = $this->swgc_render_default_calendar_view();
				
			print	$args["before_widget"].$title.$widget.$args["after_widget"];
		}
		function swgc_widget_show_calendar_control() {
			$data = get_option('swgc_widget_show_calendar');
			
			?><p><label>Title: <input name="swgc_widget_show_calendar_title" type="text" value="<?php print $data['swgc_widget_show_calendar_title']; ?>" /></label></p><?php
			
			if(isset($_POST['swgc_widget_show_calendar_title'])){
				$data['swgc_widget_show_calendar_title'] = attribute_escape($_POST['swgc_widget_show_calendar_title']);
				update_option('swgc_widget_show_calendar', $data);
			}
		}
		function swgc_get_available_slots($year, $month, $day) {
global $wpdb, $entries_table;			
                        $entries = swgc_get_entries($year, $month, $day);
			
			asort($entries, SORT_NUMERIC);
			
			$minSlot = get_option('swgc_shortest_slot', 30) * 60; // xx mins in seconds
			$availableSlots = array();
			$out = $entries_table . " - " . $year . " - " . $month . " - " . $day;
			
			if(count($entries) > 0) {
				$open = mktime(get_option('swgc_start_hour', 0), 0, 0);
				$close = mktime(get_option('swgc_end_hour', 23), 0, 0);
				
				for($i=0, $j=1; $i<count($entries); $i++, $j++) {
					$bStart = explode(':', $entries[$i]["start"]);
					$bStart = mktime($bStart[0], $bStart[1], 0);
					$bEnd = explode(':', $entries[$i]["end"]);
					$bEnd = mktime($bEnd[0], $bEnd[1], 0);
					
					if($j<count($entries)) {
						$bNextStart = explode(':', $entries[$j]["start"]);
						$bNextStart = mktime($bNextStart[0], $bNextStart[1], 0);
					}
					
					if($i==0) {
						// Beginning
						if(($bStart - $open) >= $minSlot) {
							$availableSlots[] = array(
								"start" => $open,
								"end" => $bStart
							);
						}
						elseif(($bNextStart - $bEnd) >= $minSlot) {
							$availableSlots[] = array(
								"start" => $bEnd,
								"end" => $bNextStart
							);
							continue;
						}
					}
					
					if($i>0) {
						// Middle
						if(($bNextStart - $bEnd) >= $minSlot) {
							$availableSlots[] = array(
								"start" => $bEnd,
								"end" => $bNextStart
							);
						}
					}
					
					if($j==count($entries)) {
						// End
						if(($close - $bEnd) >= $minSlot) {
							$availableSlots[] = array(
								"start" => $bEnd,
								"end" => $close
							);
						}
					}
				}
				
				if(count($availableSlots) > 0) {
					foreach($availableSlots as $slot) {
						$out .= '<div class="advice swgc-slot-available">'.date('g:ia', $slot["start"]).' to '.date('g:ia', $slot["end"]).'</div>';
					}
				}
				else {
					$out .= '<div class="advice swgc-no-slots-available">No availability</div>';
				}
			}
			else {
				// No entries
				$out .= '<div class="advice swgc-all-slots-available">All day</div>';
			}
			
			$out = '<h6>Availability ('.date('d/m/Y', mktime(0,0,0,$month,$day,$year)).'):</h6>'.$out;
			
			return $out;
		}
		function swgc_fetch_available_slots_callback() {
			$date = explode('-', $_POST["date"]);
			
			print $this->swgc_get_available_slots($date[0], $date[1], $date[2]);
			
			die();
		}
		function swgc_render_default_calendar_view($location = 'widget-area') {
			return '<div id="swgc-calendar" class="swgc-location-'.$location.'">
					<div id="swgc-calendar-async">
						'.swgc_make_calendar(date('Y'), date('m'), '', 3, NULL, 1, NULL, false).'
					</div>
					'.$this->swgc_show_legend().'
					<div id="swgc-available-slots-async">
						'.$this->swgc_get_available_slots(date('Y'), date('m'), date('d')).
					'</div>
					<div id="swgc-note">
						<small>'.get_option('swgc_footer_note').'</small>
					</div>
				</div>';
		}
		function swgc_show_calendar_shortcode($atts) {
			return $this->swgc_render_default_calendar_view('post');
		}
	}
}

if(class_exists('DiaryAndAvailabilityCalendar')){
	$DiaryAndAvailabilityCalendar = new DiaryAndAvailabilityCalendar(); 
}

function DiaryAndAvailabilityCalendar_install() {
	global $wpdb, $entries_table;
	
	add_option('swgc_widget_show_calendar','');
	add_option('swgc_unavailable_days', array());

	// create table
	$entries_table = $wpdb->prefix.$entries_table;
	if($wpdb->get_var("SHOW TABLES LIKE '$entries_table'") != $entries_table) {
		$sql = "CREATE TABLE " . $entries_table . " (
			id INT NOT NULL AUTO_INCREMENT  PRIMARY KEY,
			date DATE NOT NULL,
			start TIME DEFAULT 0 NOT NULL,
			end TIME NOT NULL,
			title VARCHAR(255) NOT NULL,
			details TEXT
		);";
	}
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

function DiaryAndAvailabilityCalendar_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=swgc-settings.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'DiaryAndAvailabilityCalendar_settings_link' );

register_activation_hook(__FILE__,'DiaryAndAvailabilityCalendar_install');