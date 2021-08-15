<?php 
/**
 * @package Curl Connect Plugin
 */

/*
* Plugin Name: 99Tipster
* Plugin URI: https://99tipster.com/
* Description: This plugin helps tipsters for football
* Version: 1.0.0
* Author: Web Master
* License: GPLv3 or later
*

*/

if( ! defined('ABSPATH') ) { die; }

require_once plugin_dir_path( __FILE__ ) . 'inc/Activate.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/Deactivate.php';

use Inc\Activate;
use Inc\Deactivate;

if ( !class_exists( 'Tipster99' ) ) {

	class Tipster99 {
		public $plugin;
		public function __construct() {
			$this->plugin = plugin_basename( __FILE__ );
			$this->enqueue();
//			$this->curl();
		}

		public function register() {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) ); // CSS & JS

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) ); // Admin CSS & JS

			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) ); // Settings Menu

			add_filter("plugin_action_links_$this->plugin" , array($this, 'settings_link') );
		}

		public function add_admin_pages() {
			add_menu_page('99Tipster', '99Tipster', 'manage_options', 'curl_connect', array( $this, 'admin_index'), 'dashicons-archive', 110 );
		}

		public function admin_index() {
			require_once plugin_dir_path( __FILE__ ) . 'templates/admin.php';
		}

		public function settings_link( $links ) {
			$settings_link = '<a href="options-general.php?page=curl_connect">Settings</a>';
			array_push($links, $settings_link );
			return $links;
		}

		function activate() {
			Activate::activate();
		}

		function deactivate() {
			Deactivate::deactivate();
		}

		public function uninstall() {
			// delete CPT
			// delete all the plugin data from the DB

		}

		public function enqueue() {
			// enqueue all our scripts
			wp_enqueue_style( 'sidestyles', plugins_url( '/assets/curlconnect.css', __FILE__ ) );
			wp_enqueue_script( 'sidestyles', plugins_url( '/assets/curlconnect.js', __FILE__ ) );
		}

		public function admin_enqueue() {
			// enqueue all our scripts
			wp_enqueue_style( 'sidestyles', plugins_url( '/assets/curlconnect_admin.css', __FILE__ ) );
			wp_enqueue_script( 'sidestyles', plugins_url( '/assets/curlconnect_admin.js', __FILE__ ) );
		}

		public function todayTip() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-1 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 6 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit'  ORDER BY `time` DESC ");
            //1602666401
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){
                  if($num > 10)
                      break;

                  $logo_path = json_decode($match->league, true)['data']['league_logo'];
                  $league_name = json_decode($match->league, true)['data']['league_name'];
                  $tips = json_decode($match->tips,true)['data']['predictions'];
                  $todayTip = [];

                  $oo = [];
                  $kk = [];
                  $todayOdds = [];
                  $todayTip['Home Team'] = $tips['home'];
                  $todayTip['Away Team'] = $tips['away'];

                  if($tips['over_2_5'] > 50)
                    $todayTip['Over2.5'] = $tips['over_2_5'];
                  if($tips['under_2_5'] >80)
                      $todayTip['Under2.5'] = $tips['under_2_5'];
                  $todayTip['Draw'] = $tips['draw'];
                  $todayOdds = json_decode($match->odds,true)['odds'];
                  if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                  else
                      $kk = [];
                  arsort($kk);


                  if(!empty($kk) &&!empty($todayOdds)){
                      foreach ($kk as $key => $ki){
                          foreach ( $todayOdds as $odd) {
                              if($key == $odd['id'])
                                  $oo[$key] = $odd;
                          }

                          if(count($oo) > 4)
                              break;

                      }
                  }

                  $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".$tt."</a><span class='tooltiptext'>".number_format($todayTip[$tt],1)."%</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                         foreach ( $oo as $odd) {

                             if(!empty($odd['bookmarker_log']))
                                 echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>                                          
   
                                            </a>
                                            </div>
                                            
                                         </li>";
                             else
                                 echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>                                          
   
                                            </a>
                                            </div>
                                            
                                         </li>";
                         }
                     }
                  echo "</ul></td>";
                  echo "</tr>";
                 }
                 echo  "</tbody>";
                 echo  "</table>";

		}
        public function allTip() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-1 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 6 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit'  ORDER BY `time` DESC ");
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){

                $logo_path = json_decode($match->league, true)['data']['league_logo'];
                $league_name = json_decode($match->league, true)['data']['league_name'];
                $tips = json_decode($match->tips,true)['data']['predictions'];
                $todayTip = [];
                $oo = [];
                $kk = [];
                $todayOdds = [];
                $todayTip['Home Team'] = $tips['home'];
                $todayTip['Away Team'] = $tips['away'];

                if($tips['over_2_5'] > 50)
                    $todayTip['Over2.5'] = $tips['over_2_5'];
                if($tips['under_2_5'] >80)
                    $todayTip['Under2.5'] = $tips['under_2_5'];

                $todayTip['Draw'] = $tips['draw'];
                $todayOdds = json_decode($match->odds,true)['odds'];
                if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                else
                    $kk = [];
                arsort($kk);


                if(!empty($kk) &&!empty($todayOdds)){
                    foreach ($kk as $key => $ki){
                        foreach ( $todayOdds as $odd) {
                            if($key == $odd['id'])
                                $oo[$key] = $odd;
                        }

                        if(count($oo) > 4)
                            break;

                    }
                }


                $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("m-d H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".$tt."</a><span class='tooltiptext'>".number_format($todayTip[$tt],1)."%</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                    foreach ( $oo as $odd) {

                        if(!empty($odd['bookmarker_log']))
                            echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                        else
                            echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                    }
                }
                echo "</ul></td>";
                echo "</tr>";
            }
            echo  "</tbody>";
            echo  "</table>";

        }
        public function tomoTip() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-1 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 1 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit' AND `time` >'$today'");
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){
                if($num > 10)
                    break;

                $logo_path = json_decode($match->league, true)['data']['league_logo'];
                $league_name = json_decode($match->league, true)['data']['league_name'];
                $tips = json_decode($match->tips,true)['data']['predictions'];
                $todayTip = [];
                $oo = [];
                $kk = [];
                $todayOdds = [];
                $todayTip['Home Team'] = $tips['home'];
                $todayTip['Away Team'] = $tips['away'];

                if($tips['over_2_5'] > 50)
                    $todayTip['Over2.5'] = $tips['over_2_5'];
                if($tips['under_2_5'] >80)
                    $todayTip['Under2.5'] = $tips['under_2_5'];

                $todayTip['Draw'] = $tips['draw'];
                $todayOdds = json_decode($match->odds,true)['odds'];
                if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                else
                    $kk = [];
                arsort($kk);


                if(!empty($kk) &&!empty($todayOdds)){
                    foreach ($kk as $key => $ki){
                        foreach ( $todayOdds as $odd) {
                            if($key == $odd['id'])
                                $oo[$key] = $odd;
                        }

                        if(count($oo) > 4)
                            break;

                    }
                }


                $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".$tt."</a><span class='tooltiptext'>".number_format($todayTip[$tt],1)."%</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                    foreach ( $oo as $odd) {

                        if(!empty($odd['bookmarker_log']))
                            echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                        else
                            echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                    }
                }
                echo "</ul></td>";
                echo "</tr>";
            }
            echo  "</tbody>";
            echo  "</table>";
        }
        public function over25Tip() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-6 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 6 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'   ORDER BY `time` DESC ");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit' AND `time` >'$before'   ORDER BY `time` DESC ");
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'   ORDER BY `time` DESC ");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){
                if($num > 20)
                    break;

                $logo_path = json_decode($match->league, true)['data']['league_logo'];
                $league_name = json_decode($match->league, true)['data']['league_name'];
                $tips = json_decode($match->tips,true)['data']['predictions'];
                $todayTip = [];
                $oo = [];
                $kk = [];
                $todayOdds = [];

                $todayTip['Over2.5'] = $tips['over_2_5'];

                $todayTip['Under2.5'] = $tips['under_2_5'];

                $todayTip['Draw'] = $tips['draw'];
                $todayOdds = json_decode($match->odds,true)['odds'];
                if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                else
                    $kk = [];
                arsort($kk);


                if(!empty($kk) &&!empty($todayOdds)){
                    foreach ($kk as $key => $ki){
                        foreach ( $todayOdds as $odd) {
                            if($key == $odd['id'])
                                $oo[$key] = $odd;
                        }

                        if(count($oo) > 4)
                            break;

                    }
                }


                $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("m-d H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".$tt."</a><span class='tooltiptext'>".number_format($todayTip[$tt],1)."%</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                    foreach ( $oo as $odd) {

                        if(!empty($odd['bookmarker_log']))
                            echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                        else
                            echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                    }
                }
                echo "</ul></td>";
                echo "</tr>";
            }
            echo  "</tbody>";
            echo  "</table>";
        }
        public function over15Tip() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-6 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 6 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'  ORDER BY `time` DESC");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit' AND `time` >'$before'  ORDER BY `time` DESC");
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'  ORDER BY `time` DESC");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){
                if($num > 10)
                    break;

                $logo_path = json_decode($match->league, true)['data']['league_logo'];
                $league_name = json_decode($match->league, true)['data']['league_name'];
                $tips = json_decode($match->tips,true)['data']['predictions'];
                $todayTip = [];
                $oo = [];
                $kk = [];
                $todayOdds = [];

                $todayTip['Under 1.5(HT)'] = $tips['HT_under_1_5'];
                $todayTip['Over 1.5(HT)'] = $tips['HT_over_1_5'];
                $todayTip['Under 1.5(AT)'] = $tips['AT_under_1_5'];
                $todayTip['Over 1.5(AT)'] = $tips['AT_over_1_5'];

                $todayOdds = json_decode($match->odds,true)['odds'];
                if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                else
                    $kk = [];
                arsort($kk);

                if(!empty($kk) &&!empty($todayOdds)){
                    foreach ($kk as $key => $ki){
                        foreach ( $todayOdds as $odd) {
                            if($key == $odd['id'])
                                $oo[$key] = $odd;
                        }

                        if(count($oo) > 4)
                            break;

                    }
                }


                $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("m-d H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".$tt."</a><span class='tooltiptext'>".number_format($todayTip[$tt],1)."%</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                    foreach ( $oo as $odd) {

                        if(!empty($odd['bookmarker_log']))
                            echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                        else
                            echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                    }
                }
                echo "</ul></td>";
                echo "</tr>";
            }
            echo  "</tbody>";
            echo  "</table>";
        }
        public function over35Tip() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-6 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 6 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'  ORDER BY `time` DESC");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit' AND `time` >'$before'  ORDER BY `time` DESC");
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'  ORDER BY `time` DESC");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){
                if($num > 10)
                    break;

                $logo_path = json_decode($match->league, true)['data']['league_logo'];
                $league_name = json_decode($match->league, true)['data']['league_name'];
                $tips = json_decode($match->tips,true)['data']['predictions'];
                $todayTip = [];
                $oo = [];
                $kk = [];
                $todayOdds = [];

                $todayTip['Over 3.5'] = $tips['over_3_5'];
                $todayTip['Under 3.5'] = $tips['under_3_5'];
                $todayOdds = json_decode($match->odds,true)['odds'];
                if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                else
                    $kk = [];
                arsort($kk);

                if(!empty($kk) &&!empty($todayOdds)){
                    foreach ($kk as $key => $ki){
                        foreach ( $todayOdds as $odd) {
                            if($key == $odd['id'])
                                $oo[$key] = $odd;
                        }

                        if(count($oo) > 4)
                            break;

                    }
                }


                $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("m-d H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".$tt."</a><span class='tooltiptext'>".number_format($todayTip[$tt],1)."%</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                    foreach ( $oo as $odd) {

                        if(!empty($odd['bookmarker_log']))
                            echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                        else
                            echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                    }
                }
                echo "</ul></td>";
                echo "</tr>";
            }
            echo  "</tbody>";
            echo  "</table>";
        }
        public function btts() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-6 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 6 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'  ORDER BY `time` DESC");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit' AND `time` >'$before'  ORDER BY `time` DESC");
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'  ORDER BY `time` DESC");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){
                if($num > 10)
                    break;

                $logo_path = json_decode($match->league, true)['data']['league_logo'];
                $league_name = json_decode($match->league, true)['data']['league_name'];
                $tips = json_decode($match->tips,true)['data']['predictions'];
                $todayTip = [];
                $oo = [];
                $kk = [];
                $todayOdds = [];

                $todayTip['BTTS/GG'] = $tips['btts'];

                $todayOdds = json_decode($match->odds,true)['odds'];
                if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                else
                    $kk = [];
                arsort($kk);

                if(!empty($kk) &&!empty($todayOdds)){
                    foreach ($kk as $key => $ki){
                        foreach ( $todayOdds as $odd) {
                            if($key == $odd['id'])
                                $oo[$key] = $odd;
                        }

                        if(count($oo) > 4)
                            break;

                    }
                }



                $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("m-d H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".number_format($todayTip[$tt],1)."</a><span class='tooltiptext'>".$tt."</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                    foreach ( $oo as $odd) {

                        if(!empty($odd['bookmarker_log']))
                            echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                        else
                            echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                    }
                }
                echo "</ul></td>";
                echo "</tr>";
            }
            echo  "</tbody>";
            echo  "</table>";
        }
        public function score() {

            global $wpdb;
            $before = strtotime(date("Y-m-d",strtotime("-6 day"))." 00:01");
            $limit = strtotime(date("Y-m-d",strtotime("+ 6 day"))." 00:01");
            $end = strtotime(date("Y-m-d",strtotime("+ 2 day"))." 00:01");
            $today = strtotime(date("Y-m-d",strtotime("now"))." 00:01");

            $yesterdayMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$today' AND `time` >'$before'  ORDER BY `time` DESC");
            $tomorrowMatches = $wpdb->get_results("SELECT * From today WHERE `time` < '$limit' AND `time` >'$before'  ORDER BY `time` DESC");
            $todayMatches = $wpdb->get_results("SELECT * From today WHERE `time` > '$limit' AND `time` <'$end'  ORDER BY `time` DESC");

            $num = 0;
            echo " <table class=\"table\">
                            <thead class=\"tipster99-thstyle\">
                              <tr>
                                <th class='tipster99-thstyle1'>&nbsp;&nbsp;&nbsp;&nbsp;Time</th>
                                <th class='tipster99-thstyle1'>League</th>
                                <th class='tipster99-thstyle1'>Match itself </th>
                                 <th class='tipster99-thstyle1'>Tip</th>
                                <th class='tipster99-thstyle1' style='min-width: 409px;'>Odds</th>
                              </tr>
                            </thead>
                            <tbody>";

            foreach ($tomorrowMatches as $match){
                if($num > 10)
                    break;

                $logo_path = json_decode($match->league, true)['data']['league_logo'];
                $league_name = json_decode($match->league, true)['data']['league_name'];
                $tips = json_decode($match->tips,true)['data']['predictions'];
                $todayTip = [];
                $oo = [];
                $kk = [];
                $todayOdds = [];

                $todayTip['BTTS/GG'] = $tips['btts'];

                $todayOdds = json_decode($match->odds,true)['odds'];
                if(!empty($todayOdds))
                    $kk = array_column($todayOdds,'odd_value','id');
                else
                    $kk = [];
                arsort($kk);

                if(!empty($kk) &&!empty($todayOdds)){
                    foreach ($kk as $key => $ki){
                        foreach ( $todayOdds as $odd) {
                            if($key == $odd['id'])
                                $oo[$key] = $odd;
                        }

                        if(count($oo) > 4)
                            break;

                    }
                }



                $tt =array_keys($todayTip,max($todayTip))[0];

                if(!empty($oo)){
                    $num++;

                    echo "<tr>";
                    echo "<td class='tipster99-tdstyle1'>".date("m-d H:i",$match->time)."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><img  width='40px' class='tipster99-leaue' src='".$logo_path."'><span class='tooltiptext'>".$league_name."</span></td>";
                    echo "<td class='tipster99-tdstyle1 tips-font1'>".$match->home."<b class='tipster99-col-red'>&nbsp;&nbsp;VS&nbsp;&nbsp;</b>".$match->away."</td>";
                    echo "<td class='tipster99-tdstyle1 tooltip'><a href='#'>".number_format($todayTip[$tt],1)."</a><span class='tooltiptext'>".$tt."</span></td>";
                    echo "<td class='tipster99-tdstyle1'><ul class='tipster99-top col-md-12'>";

                    foreach ( $oo as $odd) {

                        if(!empty($odd['bookmarker_log']))
                            echo " <li style='width: 20%;'>
                                            <div>
                                            <a href='#'>
                                                <div>
                                                    <img width='62px' class='tipster99-leaue' src='".home_url().$odd['bookmarker_log']."'>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                        else
                            echo " <li>
                                            <div>
                                            <a href='#' class='tipster99-style2'>
                                                <div>
                                                    <h5>".$odd['bookmaker']."</h5>
                                                </div>
                                                <div><span><strong>".$odd['odd_value']."</strong></span></div>

                                            </a>
                                            </div>

                                         </li>";
                    }
                }
                echo "</ul></td>";
                echo "</tr>";
            }
            echo  "</tbody>";
            echo  "</table>";
        }

	}

	$curlConnect = new Tipster99;
	$curlConnect->register();

	// activation
	register_activation_hook(__FILE__, array( $curlConnect, 'activate') );

	// deactivate
	register_deactivation_hook(__FILE__, array( $curlConnect, 'deactivate') );


	function shortcode_todayTips () {
		$curlConnect = new Tipster99;
		$curlConnect->todayTip();
	}
	add_shortcode('99tipster_today', 'shortcode_todayTips');

    function shortcode_tomoTips () {
        $curlConnect = new Tipster99;
        $curlConnect->tomoTip();
    }
    add_shortcode('99tipster_tomorrow', 'shortcode_tomoTips');

    function shortcode_25Tips () {
        $curlConnect = new Tipster99;
        $curlConnect->over25Tip();
    }

    add_shortcode('99tipster_over25Tip', 'shortcode_25Tips');


    function shortcode_15Tips () {
        $curlConnect = new Tipster99;
        $curlConnect->over15Tip();
    }
    add_shortcode('99tipster_over15Tip', 'shortcode_15Tips');

    function shortcode_35Tips () {
        $curlConnect = new Tipster99;
        $curlConnect->over35Tip();
    }
    add_shortcode('99tipster_over35Tip', 'shortcode_35Tips');

    function shortcode_btts () {
        $curlConnect = new Tipster99;
        $curlConnect->btts();
    }
    add_shortcode('99tipster_btts', 'shortcode_btts');

    function shortcode_all () {
        $curlConnect = new Tipster99;
        $curlConnect->allTip();
    }
    add_shortcode('99tipster_all', 'shortcode_all');

}