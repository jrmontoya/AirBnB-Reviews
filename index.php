<?php
/*
* Plugin Name: AirBnB Reviews
* Description: Display reviews for a given AirBnB listing ID
* Version: 1.0
* Author: José Reinaldo Montoya
* Author URI: https://www.jrmontoya.com
*/

/**
 * Avoid direct calls to this file where WP core files are not present
 */
if(!function_exists('add_action')) :
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
endif;

class WP_AirbnbReviews {
    
    private $api_key;
    private $file_expires;
    
    protected $plugin_slug;
    protected $version;
    
    protected $plugin;
    
    public function __construct()
    {
        
        $this->plugin_slug = 'airbnb-reviews';
        $this->version = '1.0';
        $this->plugin = plugin_basename( __FILE__ );
        
        //Include Styles
        wp_register_style($this->plugin_slug, plugins_url('WP_AirbnbReviews.min.css',__FILE__ ));
        wp_enqueue_style($this->plugin_slug);
        
        //Admin Menu Item
        add_action("admin_menu", array($this, 'adminAddSubmenu'));
        add_filter( "plugin_action_links_".$this->plugin, array($this, 'adminAddSettingsLink') );
        
        //Add shortcode
        add_shortcode('airbnb-reviews', array($this, 'WP_AirbnbReviews_Shortcode'));
        
        // Add shortcode support for widgets
        add_filter('widget_text', 'do_shortcode');   
        
        //
        $this->api_key      = get_option( $this->plugin_slug . '-api_key', '' );
        $this->file_expires = get_option( $this->plugin_slug . '-file-expires', '7' );
        
    }
    
    public function adminAddSettingsLink( $links ) {
        $settings_link = '<a href="options-general.php?page=' . $this->plugin_slug . '">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }
    
    public function adminAddSubmenu()
    {
        add_submenu_page("options-general.php", "Airbnb Reviews", "Airbnb Reviews", "manage_options", $this->plugin_slug, array($this, 'adminPage')); 
    }
    
    public function adminPage(){
        
        if($_POST){
        
            if(!isset( $_POST['ms_nonce']) || ! wp_verify_nonce( $_POST['ms_nonce'], 'ms_airbnb_reviews')) :
                wp_die(new WP_Error(
                    'invalid_nonce', __('Sorry, I\'m afraid you\'re not authorised to do this.')
                ));
                exit;
            endif;

            $action = trim(stripslashes($_POST['action']));

            if($action=="general_options"){

                $api_key      = trim(stripslashes($_POST['api_key']));
                $file_expires = intval($_POST['file_expires']);

                if(update_option( $this->plugin_slug . '-api_key', $api_key)){
                    $this->api_key = $api_key;
                }
                if(update_option( $this->plugin_slug . '-file_expires', $file_expires)){
                    $this->file_expires = $file_expires;
                }
            }
            else{



            }
            
        }
        
        if( isset( $_GET[ 'tab' ] ) ):
            $active_tab = $_GET[ 'tab' ];
        else:
            $active_tab = 'options';
        endif;
        
        ?>
        <div class="wrap">
            
            <div id="icon-themes" class="icon32"></div>
            <h1>Airbnb Reviews</h1>
            <?php settings_errors(); ?>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo($this->plugin_slug);?>&tab=options" class="nav-tab <?php echo $active_tab == 'options' ? 'nav-tab-active' : ''; ?>">Options</a>
                <a href="?page=<?php echo($this->plugin_slug);?>&tab=about" class="nav-tab <?php echo $active_tab == 'about' ? 'nav-tab-active' : ''; ?>">About</a>
            </h2>
            
            <?php if( $active_tab == 'options' ): ?>
            
            <form method="post">
                <input type="hidden" name="action" value="general_options">
                <?php wp_nonce_field('ms_airbnb_reviews', 'ms_nonce'); ?>

                <table class="form-table">

                    <tbody>

                        <tr>
                            <th scope="row"><label for="api_key">Airbnb API Key</label></th>
                            <td><input type="text" id="api_key" name="api_key" placeholder="API Key" value="<?php echo($this->api_key);?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Update reviews</label></th>
                            <td>
                                <label>
        <input type="radio" name="file_expires" value="1"<?php if($this->file_expires==1): ?> checked="checked"<?php endif;?>>
        Daily&nbsp;
        </label>
                                <label>
        <input type="radio" name="file_expires" value="7"<?php if($this->file_expires==7): ?> checked="checked"<?php endif;?>>
        Weekly&nbsp;
        </label>
                                <label>
        <input type="radio" name="file_expires" value="30"<?php if($this->file_expires==30): ?> checked="checked"<?php endif;?>>
        Monthly&nbsp;
        </label>
                            </td>
                        </tr>

                    </tbody>

                </table>

                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
            </form>
            
            <?php elseif ( $active_tab == 'about' ): ?>
            
                <h2>About Airbnb Reviews</h2>
                
                <p>Plugin</p>
            
            <?php endif; ?>
            
        </div>
       <?php
        
    }
    
    private function DateDiff($interval,$date1,$date2) {
        
        $timediff = $date2 - $date1;

        switch ($interval) {
            case 'w':
                $retval = bcdiv($timediff,604800);
                break;
            case 'd':
                $retval = bcdiv($timediff,86400);
                break;
            case 'h':
                $retval = bcdiv($timediff,3600);
                break;
            case 'n':
                $retval = round($timediff / 60);
                break;
            case 's':
                $retval = $timediff;
                break;

        }
        return $retval;

    }

    private function cURL_query($url){

        //BEGIN Get data with cURL
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $result = curl_exec($ch);
        curl_close($ch);            
        //END Get data with cURL

        return $result;

    }

    /*
    * Function Name: reviewsFile
    * Description: Writes data from Airbnb Reviews into a file
    * Version: 1.0
    * Author: José Reinaldo Montoya
    * Author URI: https://www.jrmontoya.com
    */
    private function reviewsFile($listingID){

        $filepath = wp_upload_dir();
        $filepath = $filepath['basedir'].'/'.$this->plugin_slug.'/' . $listingID . '.json';

        $url = "https://api.airbnb.com/v2/reviews?key=".$this->api_key."&_format=v1_legacy_short&_limit=20&_offset=0&listing_id=".$listingID."&role=all";

        if (file_exists($filepath)){

            $currentdate = time();
            $filedate    = filemtime($filepath);
            
            $diffdate = $this->DateDiff("d",$filedate,$currentdate);

            if($diffdate > $this->file_expires){

                $handle = fopen($filepath, "w+");
                fwrite($handle,$this->cURL_query($url));
                fclose($handle);		
            }
        }
        else{
            $handle = fopen($filepath, "w+");
            fwrite($handle,$this->cURL_query($url));
            fclose($handle);
        }

        return $filepath;
    }
    
    /*
    * Function Name: WP_AirbnbReviews_Shortcode
    * Description: Custom shortcode to show AirBnb Listing Reviews
    * Version: 1.0
    * Author: José Reinaldo Montoya
    * Author URI: https://www.jrmontoya.com
    */
    public function WP_AirbnbReviews_Shortcode($atts){

        if($this->api_key!=""){
        
            $atts       = array_change_key_case((array)$atts, CASE_LOWER);
            $listingID  = intval($atts["id"]);
            $output     = "";

            if($listingID>0){

                $filepath = $this->reviewsFile($listingID);

                if(file_exists($filepath)){

                    $baseurl = content_url() . "/uploads/".$this->plugin_slug."/$listingID.json";
                    $reviews = json_decode(file_get_contents($baseurl));
                    $count   = intval($reviews->metadata->reviews_count);

                    if($count>0){

                        $reviews = $reviews->reviews;
                        $output .= '<div id="airbnb-reviews">';

                        for($i=0;$i<sizeof($reviews);$i++){

                            $r_comment = $reviews[$i]->comments;
                            $r_usrname = $reviews[$i]->reviewer->user->smart_name;
                            $r_usrthmb = $reviews[$i]->reviewer->user->thumbnail_url;
                            $r_rvwdate = date("M Y",strtotime($reviews[$i]->created_at));

                            $output .= '<div class="item">
                                            <div class="icon-box testimonial-box icon-box-left text-left">
                                                <div class="icon-box-img testimonial-image circle"><img src="'.$r_usrthmb.'" class="attachment-thumbnail size-thumbnail" /></div>
                                                <div class="icon-box-text p-last-0">
                                                    <div class="testimonial-text line-height-small italic first-reset last-reset is-italic"><p>'.$r_comment.'</p></div>
                                                    <div class="testimonial-meta pt-half">
                                                        <strong class="testimonial-name test_name">'.$r_usrname.'</strong>
                                                        <small> / '.$r_rvwdate.'</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
                        }

                        $output .= '</div>';

                    }
                    else $output = "No reviews available.";
                }
                else $output = "Failed to reach file.";
            }
            else $output = "Listing ID not found.";
        }
        else $output = "You must to set an API Key in the admin page.";

        return(do_shortcode($output));
    }
    
}
 
$WP_AirbnbReviews = new WP_AirbnbReviews();
