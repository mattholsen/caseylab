<?php

/**
 *
 * Please see single-event.php in this directory for detailed instructions on how to use and modify these templates.
 *
 */

$full_date_arr = get_post_meta( get_the_ID(), '_EventStartDate' ); 
$start_date = date('n/j/Y',strtotime ($full_date_arr[0]));

?>

<script type="text/html" id="tribe_tmpl_tooltip">
	<div id="tribe-events-tooltip-[[=eventId]]" class="tribe-events-tooltip">
		<h4 class="entry-title summary">[[=title]]</h4>

		<div class="tribe-events-event-body">
			<div class="duration" style="padding:0">
				<abbr class="tribe-events-abbr updated published dtstart">[[=dateDisplay]] </abbr>
			</div>
			[[ if(imageTooltipSrc.length) { ]]
			<div class="tribe-events-event-thumb">
				<img src="[[=imageTooltipSrc]]" alt="[[=title]]" />
			</div>
			[[ } ]]
			[[ if(excerpt.length) { ]]
			<p class="entry-summary description">[[=raw excerpt]]</p>
			[[ } ]]
			
    <div style="clear:both;"><a href="https://clients.mindbodyonline.com/classic/home?studioid=38100&classDate=<?php print_r($start_date); ?>&view=day" target="_blank"> <input type="button" id="sign_up" value="Sign Up" style=" background-color:#C6CED9; font-size:1.3em; padding:2px 20px; margin: -12px 0 4px 0"></a></div>
			<span class="tribe-events-arrow"></span>
		</div>
	</div>
</script>
