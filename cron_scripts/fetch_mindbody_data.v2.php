<?php

/* A NOTE TO FUTURE MAINTAINERS:  
 * WHEN A COMMENT HERE REFERS TO "class" DON'T BE CONFUSED.  IT MEANS A GATHERING OF STUDENTS, TAUGHT BY A TEACHER.  
 * NOTHING TO DO WITH PHP CLASSES.  LIKEWISE WITH "class" IN NAMES OF VARS, METHODS, FILES, ETC. JUST THOUGHT I'D MENTION THAT.  MSH  
 NOTES:
 Need changes in wp and theme:
 Will need to change css for popup in calendar.  Then can increase excerpt length.
 API Keys should be stored outside /var/www
 
 
 Need Policy decisions:
  Do we update past events?
  How often do we update events that are more than, say, 60 days away?
   
 TO-DO HERE:
  Handling of images generally:
  as of 11/18/2015 the oly check for duplicate images is within a run.  
  Have to check if filename exists anywhere (and size matches?). 
  Probably easiest to do that with db query, since if the image exists it is an attachment
  
 Handling images on update:
   as of 11/18/2015 images are not handled at all on update.  to-do:
     Check if image filename in feeed is different from that in post and replace if necessary
     check if 
 
 Q's for Casey, 11/24/2015 :
 
 Should we use instructor photos from feed as fallback?
 How often should we check the feed and update classes in the near future?
 How often do we need to update classes that are well in the future (say, 60 days)
 Will we ever need to change things on past class posts?
 Should we delete class posts (calendar items) that are well in the past?
 */

echo '<pre>';
log_it('********    '. date('Y-m-d H:i:s') .'    ***********');
require_once("includes/classService.php");
// sandbox credentials:
$sourcename = 'AccelerantStudiosLLC';
$password = "oMh1ajTlIwhtxZsHomLprIdxS9Q=";
//$siteID ="-99"; // Mindbody Sandbox
$siteID ="38100"; // Casey's ID, now that our keys work there

$creds = new SourceCredentials($sourcename, $password, array($siteID));
$classService = new MBClassService();
$classService->SetDefaultCredentials($creds);


$d1=new DateTime(); // defaults to now
$d2=new DateTime("2015-12-31"); // Make this 30, 60 or 365 days ahead, 
$result = $classService->GetClasses(array(), array(), array(), $d1, $d2, null, 1000, 0);
//print_r($result);

//The array of class data we want is $result->GetClassesResult->Classes->Class (why 3 deep? dunno. Big sprawling structure.  Not my code.  MSH)

// DEV: look at the structure
for($i = 0; $i <6 ; $i++ ){
  print_r($result->GetClassesResult->Classes->Class[$i]);
}

// load wordpress so we can get the existing event posts
require_once($_SERVER['DOCUMENT_ROOT']."/wp-load.php");
//for image processing
require_once(ABSPATH . "wp-admin" . '/includes/image.php');
require_once(ABSPATH . "wp-admin" . '/includes/file.php');
require_once(ABSPATH . "wp-admin" . '/includes/media.php');
require_once(ABSPATH . "wp-admin" . '/includes/image.php');

// GET ALL EXISTING EVENT POSTS WITH START DATES AFTER LAST MIDNIGHT(POSSIBLY INCLUDING SOME THAT ARE NOT FROM MINDBODY) 
// AND YES, THOSE NESTED ARRAYS ARE THE WAY get_posts() WANTS IT. 
$args = array('post_type'=>'tribe_events',
              'posts_per_page' => -1, // get them all
              'meta_query' => array(
                                array( 'key'     => '_EventStartDate',
                                       'value'   => date('Y-m-d').' 00:00:00', // starting today
                                       'compare' => '>'
                                     )
                                   )
              ); 

$event_posts = get_posts($args); // another sprawling structure

$existing_posts_mbids_arr = array();
$attachment_urls_arr = array(); // used in attaching images to new events, since a lot of the feed images are the same

// ADD THE METADATA AND IF THE POST HAS A MINDBODY ID ADD IT TO THE ARR AS A KEY, WITH THE POST ID AS THE VAL
// THAT GIVES US A SIMPLE LOOKUP TO SEE IF A MB EVENT ALREADY HAS A CORRESPONDING POST, AND THE POST ID TO UPDATE IF IT DOES.
foreach ($event_posts as $key => $post){
	$event_posts[$key]->meta = get_metadata ('post', $post->ID);
	if(isset($event_posts[$key]->meta['mind_body_ID'][0])){
		$existing_posts_mbids_arr[$event_posts[$key]->meta['mind_body_ID'][0]] = $post->ID;
	}
}
//dev
foreach($event_posts as $one_event ){
  if(isset($one_event->meta['_thumbnail_id'])){
    print_r($one_event->meta['_thumbnail_id']);
  }
}

$fetched_mbid_arr = array();
  // FOREACH EVENT (CLASS) IN MINDBODY FEED, 
foreach($result->GetClassesResult->Classes->Class as $mb_event){
  //  IF THERE IS NO POST WITH THAT MBID, CREATE ONE
	if(!isset($existing_posts_mbids_arr[$mb_event->ID])){
		insert_new_event($mb_event);
	}
	else{
    // else update the post.  See note 1
    $post_id = $existing_posts_mbids_arr[$mb_event->ID];
    update_event($mb_event, $post_id);
	}
	// store fetched mbid (as key so ) so we can see if any posts no longer have MB entries and have to be trashed.
  $fetched_mbid_arr[$mb_event->ID]=true; // store as key for quicker lookup.  Anything useful we can put in the val?  
}
   // print_r($existing_posts_mbids_arr); 
   // print_r($fetched_mbid_arr); 
    
  foreach ($existing_posts_mbids_arr as $mbid => $post_id){
  	// NOTE: THIS ONLY WORKS IF THE FEED AND THE POST SELECTIONS USE THE SAME DATE RANGE.  
    if(!isset($fetched_mbid_arr[$mbid])){
    	log_it("Deleted post $post_id because mbid $mbid is was not in feed");
      wp_trash_post($post_id);  // use trash instead of delete, which will actually delete if post type isn't page or post
    }	
  } 
     
        
function insert_new_event($event_obj){ 
  log_it("inserted event for mbid ".$event_obj->ID);
  $post_data = array(
    'ID'             => null, // not updating an existing post
    'post_content'   => $event_obj->ClassDescription->Description, // The full text 
    'post_name'      => null,  // slug.  wp will generate
    'post_title'     => $event_obj->ClassDescription->Name,  // The title of your post.
    'post_status'    => 'publish',  // Default 'draft'.
    'post_type'      => 'tribe_events', // Default 'post'.
    'post_author'    => '1', // The user ID number of the author. Default is the current user ID.
    'ping_status'    => 'closed',  // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
    'post_parent'    => null,  // Sets the parent of the new post, if any. Default 0.
    'menu_order'     => null,  // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
    'to_ping'        => null,  // Space or carriage return-separated list of URLs to ping. Default empty string.
    'pinged'         => null,  // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
    'post_password'  => null,  // Password for post, if any. Default empty string.
    'guid'           => null, // Skip this and let Wordpress handle it, usually.
    'post_content_filtered' => null,  // Skip this and let Wordpress handle it, usually.
    'post_excerpt'   => truncate_text($event_obj->ClassDescription->Description, 120), // For all your post excerpt needs.
    'post_date'      => date('Y-m-d H:i:s'), // The time post was made.
    'post_date_gmt'  => gmdate('Y-m-d H:i:s'),// The time post was made, in GMT.
    'comment_status' => 'closed', // Default is the option 'default_comment_status', or 'closed'.
    'post_category'  => null, // Default empty.
    'tags_input'     => null, // Default empty.
    'tax_input'      => null, // For custom taxonomies. Default empty.
    'page_template'  => null // Requires name of template file, eg template.php. Default empty.
  );  
  
  $new_post_id = wp_insert_post($post_data);
  
  if($new_post_id){
    $start_date = substr($event_obj->StartDateTime, 0 ,10 ).' '.substr($event_obj->StartDateTime, 11,8 ); // more efficient than str_replace!
    $end_date = substr($event_obj->EndDateTime, 0 ,10 ).' '.substr($event_obj->EndDateTime, 11,8 ); 
    $duration =  strtotime ($end_date) - strtotime ($start_date);
    $meta_arr =  array (
      '_EventStartDate' => $start_date,                     //GENERATE
      '_EventEndDate' => $end_date ,                        //GENERATE
      '_EventStartDateUTC' => $start_date,                  //GENERATE
      '_EventEndDateUTC' => $end_date,                      //GENERATE
      '_EventDuration' => $duration,                        //GENERATE
      'mind_body_ID' => $event_obj->ID,                     //COPY
      
      //'_thumbnail_id' => '2255',                            //GENERATE SEPARATELY
      'Instructor' => $event_obj->Staff->Name,
      
      // ALL THE REST ARE DEFAULTS FOUND IN EVENT POSTS GENERATED THROUGH UI.  MOST OF THEM PROBABLY ARE USELESS, BUT I READ SOMEWHERE THAT AVADA USES ALL THE pyre_* META'S FOR SOMETHING
      '_EventOrigin' => 'events-calendar',
      '_EventVenueID' => '0',
      '_EventShowMapLink' => '1',
      '_EventShowMap' => '',
      '_EventCost' => '',
      'slide_template' => 'default',
      'sbg_selected_sidebar' => 'a:1:{i:0;s:1:"0";}',
      'sbg_selected_sidebar_replacement' => 'a:1:{i:0;s:0:"";}',
      'sbg_selected_sidebar_2' => 'a:1:{i:0;s:1:"0";}',
      'sbg_selected_sidebar_2_replacement' => 'a:1:{i:0;s:0:"";}',
      'pyre_main_top_padding' => '',
      'pyre_main_bottom_padding' => '',
      'pyre_hundredp_padding' => '',
      'pyre_slider_position' => 'default',
      'pyre_slider_type' => 'no',
      'pyre_slider' => '0',
      'pyre_wooslider' => '0',
      'pyre_revslider' => '0',
      'pyre_elasticslider' => '0',
      'pyre_fallback' => '',
      'pyre_avada_rev_styles' => 'default',
      'pyre_display_header' => 'yes',
      'pyre_header_100_width' => 'default',
      'pyre_header_bg' => '',
      'pyre_header_bg_color' => '',
      'pyre_header_bg_opacity' => '',
      'pyre_header_bg_full' => 'no',
      'pyre_header_bg_repeat' => 'repeat',
      'pyre_displayed_menu' => 'default',
      'pyre_display_footer' => 'default',
      'pyre_display_copyright' => 'default',
      'pyre_footer_100_width' => 'default',
      'pyre_sidebar_position' => 'default',
      'pyre_sidebar_bg_color' => '',
      'pyre_page_bg_layout' => 'default',
      'pyre_page_bg' => '',
      'pyre_page_bg_color' => '',
      'pyre_page_bg_full' => 'no',
      'pyre_page_bg_repeat' => 'repeat',
      'pyre_wide_page_bg' => '',
      'pyre_wide_page_bg_color' => '',
      'pyre_wide_page_bg_full' => 'no',
      'pyre_wide_page_bg_repeat' => 'repeat',
      'pyre_page_title' => 'default',
      'pyre_page_title_text' => 'default',
      'pyre_page_title_text_alignment' => 'default',
      'pyre_page_title_100_width' => 'default',
      'pyre_page_title_custom_text' => '',
      'pyre_page_title_text_size' => '',
      'pyre_page_title_custom_subheader' => '',
      'pyre_page_title_custom_subheader_text_size' => '',
      'pyre_page_title_font_color' => '',
      'pyre_page_title_height' => '',
      'pyre_page_title_mobile_height' => '',
      'pyre_page_title_bar_bg' => '',
      'pyre_page_title_bar_bg_retina' => '',
      'pyre_page_title_bar_bg_color' => '',
      'pyre_page_title_bar_borders_color' => '',
      'pyre_page_title_bar_bg_full' => 'default',
      'pyre_page_title_bg_parallax' => 'default',
      'pyre_page_title_breadcrumbs_search_bar' => 'default',
    );
    foreach($meta_arr as $key => $val){
    	add_post_meta($new_post_id, $key, $val);
    }
    // attach an image
    $post_obj = get_post($new_post_id);
    $image_id = attach_image_to_event ($event_obj, $post_obj);
    
  }
}

function update_event($event_obj, $post_id){  // event_obj is one class from the mind-body feed
  log_it("updated event for mbid ".$event_obj->ID ." post_id is $post_id");

  $post_data = array(
    'ID'             => $post_id, // updating an existing post
    'post_content'   => $event_obj->ClassDescription->Description, 
    'post_title'     => $event_obj->ClassDescription->Name,  
    'post_excerpt'   => truncate_text($event_obj->ClassDescription->Description, 120), 
  );  
  
  wp_update_post( $post_data );

  $start_date = substr($event_obj->StartDateTime, 0 ,10 ).' '.substr($event_obj->StartDateTime, 11,8 ); // more efficient than str_replace!
  $end_date = substr($event_obj->EndDateTime, 0 ,10 ).' '.substr($event_obj->EndDateTime, 11,8 ); 
  $duration =  strtotime ($end_date) - strtotime ($start_date);
  
  
  $meta_arr =  array (
    '_EventStartDate' => $start_date,                     //GENERATE
    '_EventEndDate' => $end_date ,                        //GENERATE
    '_EventStartDateUTC' => $start_date,                  //GENERATE
    '_EventEndDateUTC' => $end_date,                      //GENERATE
    '_EventDuration' => $duration,                        //GENERATE
   // '_thumbnail_id' => '2255',                          //GENERATE SEPARATELY

  );
  
  foreach($meta_arr as $key => $val){
  	update_post_meta($post_id, $key, $val);
  }
}

	





function truncate_text($text,$length) {
	// truncates at last space before $length.
  $text=strip_tags($text);
  if (strlen($text) > $length) { 
    $text = substr($text, 0, $length); 
    $text = substr($text,0,strrpos($text," ")); 
    $etc = " ...";  
    $text = $text.$etc; 
  }
  return $text; 
}

function attach_image_to_event ($event_obj, $post_obj){
	$post_id = $post_obj->ID;
  // if there is an image in the feed for this class (no longer using staff photos) use that as the thumbnail
  if(isset($event_obj->ClassDescription->ImageURL)){
  	$url = $event_obj->ClassDescription->ImageURL;
  	// strip possible query strings after basename
  	preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $url, $matches);
    $image_filename = basename($matches[0]);
  	log_it('image filename from feed: '.$image_filename);
  	
    // if no image with that filename already exists as an attachment, "sideload" the image & create the attachment
    if(!$id = find_existing_attachment($image_filename)){
     	$tmp = download_url( $url );
    	if( is_wp_error( $tmp ) ){
    		// download failed, need to add error handling
    		return false;
    	}
    	$file_array = array();
    
    	// Set variables for storage
    	// fix filename for query strings
    	$file_array['name'] = $image_filename);
    	$file_array['tmp_name'] = $tmp;
    	
    	// If error storing temporarily, unlink
    	if ( is_wp_error( $tmp ) ) {
    		log_it('temp store error');
    		@unlink($file_array['tmp_name']);
    		$file_array['tmp_name'] = '';
    	}
    	
    	// do the validation and storage stuff
    	$id = media_handle_sideload( $file_array, $post_id, $desc);
    	// If error storing permanently, unlink
    	if ( is_wp_error($id) ) {
    		log_it('sideload error');
    		@unlink($file_array['tmp_name']);
    	}  	
    	$attachment_urls_arr[$id]=$url;
    	log_it("returned new id $id");
      
      // create the thumbnails
      $attach_data = wp_generate_attachment_metadata( $id, $file_array['tmp_name'] );
      wp_update_attachment_metadata( $id,  $attach_data );
    } 
  	// else we have an id
  } else {
  	// get the id of the fallback image for this category
  	
  }
  
	 	update_post_meta($post_id, '_thumbnail_id', $id );
}


function update_image($event_obj, $post_id){   
	// most of the time the image won't change, but check if feed and post match and update accordingly  
	
	/*
	if (there is an image in the feed object and not in the post){
	  if (an image with that filename is already an attachment){
	     get the attachment\'s id
	  } else {
	     sideload the image as an attachment and get the new id
	  }
	  make that id the meta _Thumbnail`
	  return
	}
	
	if(there is an image in the post but none in the feed object){
	  get fallback image filename for this class
	  get id of attachment with that filename
	  make that id the meta _Thumbnail
	  return
  }
	
	if(there is an image in the feed object and in the post){
	  look up the filename of the attached image
	  if (it\'s the same as  the filename in the feed oject) {
	    do nothing
	    return
	  } else {
	     if (an image with that filename is already an attachment){
	       get that attachment's id
	  } else {
	     sideload the image and get the id 
	  }
	  make that id the meta _Thumbnail
	  return
	  
	  }
  }
  */
}	
	
function find_existing_attachment($image_filename){
	// return id of attachment with the filename passed, or false 
	// note: for a second check against the existence of a random image of the same name, might want to see if the attachment is attached to some event
	
	
	
	
}
	

	
function get_fallback_image(){
	
	
}












	
function log_it($text, $log_file='fetch_log'){
  file_put_contents($log_file, "\n".$text, FILE_APPEND);
}
    /* Note 1 This is lazy and will result in a lot of db traffic, but the last_updated value in the MB feed seems to apply to the class description, not to the session itself.  
     * We could compare a bunch of values (start-times, etc) but that is hard to maintain if we end up using more values, so go ahead and do all those writes.  It's wasteful but it's bulletproof.
     * Not as wasteful as you might think, actually.  wp-update_post and update_post_meta read first and don't do a write if data is unchanged. 
     * Casey's MB feed only goes about 11 months ahead, so it could be about 600 updates of 11 reads and maybe a few writes.  A themed page view might involve 50 or more reads, so each run is like 10-20 page views.  Not too onerous.
    */
/* 	
   Here's the method sig:
 * public function GetClasses(array $classDescriptionIDs, 
                             array $classIDs, 
                             array $staffIDs, 
                             $startDate, //wants a DateTime object
                             $endDate,  //wants a DateTime object
                             $clientID = null, 
                             $PageSize = null, 
                             $CurrentPage = null, 
                             $XMLDetail = XMLDetail::Full, 
                             $Fields = NULL, 
                             SourceCredentials $credentials = null)*/

// We don't need to update things in the past, even if they've changes, which they probably haven't.  So start with todays date and end with something way in the future.




















?>