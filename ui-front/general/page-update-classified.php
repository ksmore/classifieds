<?php
/**
* The template for displaying the Add/edit classified page.
* You can override this file in your active theme.
*
* @license GNU General Public License (Version 2 - GPLv2) {@link http://www.gnu.org/licenses/gpl-2.0.html}
*/
if (!defined('ABSPATH')) die('No direct access allowed!');

global $wp_query, $post_ID, $wp_taxonomies;

$classified_data   = '';
$selected_cats  = '';
$error = get_query_var('cf_error');

//Are we adding a Classified?
if ( is_page($this->add_classified_page_id) ) {
	//Make an auto-draft so we have a post id to connect attachments to. Set global $post_ID so media editor can hook up.
	$post_ID = wp_insert_post( array( 'post_title' => '', 'post_type' => 'classifieds', 'post_status' => 'auto-draft' ) );
	$classified_data = get_object_vars(get_post( $post_ID ));
}

//Or are we editing a Classified?
if ( is_page($this->edit_classified_page_id) ){
	$classified_data = get_post(  $_REQUEST['post_id'], ARRAY_A );
	$post_ID = $classified_data['ID'];
}

if ( isset( $_POST['classified_data'] ) ) $classified_data = $_POST['classified_data'];

require_once(ABSPATH . 'wp-admin/includes/template.php');

$editor_settings =   array(
'wpautop' => true, // use wpautop?
'media_buttons' => true, // show insert/upload button(s)
'textarea_name' => 'classified_data[post_content]', // set the textarea name to something different, square brackets [] can be used here
'textarea_rows' => 10, //get_option('default_post_edit_rows', 10), // rows="..."
'tabindex' => '',
'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
'editor_class' => '', // add extra class(es) to the editor textarea
'teeny' => false, // output the minimal editor config used in Press This
'dfw' => false, // replace the default fullscreen with DFW (needs specific css)
'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
);

$classified_content = (isset( $classified_data['post_content'] ) ) ? $classified_data['post_content'] : '';

?>

<!-- Begin Update Classifieds -->
<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/jquery.tagsinput.min.js?ver=2'; ?>" ></script>
<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/media-post.js'; ?>" ></script>

<?php if ( !empty( $error ) ): ?>
<br /><div class="error"><?php echo $error . '<br />'; ?></div>
<?php endif; ?>


<div class="cf_update_form">

	<?php if ( isset( $msg ) ): ?>
	<div class="<?php echo $class; ?>" id="message">
		<p><?php echo $msg; ?></p>
	</div>
	<?php endif; ?>

	<form class="standard-form base" method="post" action="#" enctype="multipart/form-data" id="cf_update_form" >
		<input type="hidden" id="post_id" name="classified_data[ID]" value="<?php echo ( isset( $classified_data['ID'] ) ) ? $classified_data['ID'] : ''; ?>" />
		<input type="hidden" name="post_id" value="<?php echo ( isset( $classified_data['ID'] ) ) ? $classified_data['ID'] : ''; ?>" />

		<div class="editfield">
			<label for="title"><?php _e( 'Title', $this->text_domain ); ?></label><br />
			<input class="required" type="text" id="title" name="classified_data[post_title]" value="<?php echo ( isset( $classified_data['post_title'] ) ) ? $classified_data['post_title'] : ''; ?>" />
			<p class="description"><?php _e( 'Enter title here.', $this->text_domain ); ?></p>
		</div>

		<div class="editfield">

			<?php
			echo $this->get_post_image_link($post_ID);
			?>
		</div>

		<div class="editfield alt">
			<label for="classifiedcontent"><?php _e( 'Content', $this->text_domain ); ?></label>

			<?php if(intval(get_bloginfo('version') >= 3.3)): ?>

			<?php wp_editor( $classified_content, 'classifiedcontent', $editor_settings); ?>

			<?php else: ?>

			<textarea id="classifiedcontent" name="classified_data[post_content]" cols="40" rows="5"><?php echo esc_textarea($classified_content); ?></textarea>

			<?php endif; ?>

			<p class="description"><?php _e( 'The content of your classified.', $this->text_domain ); ?></p>
		</div>

		<div class="editfield alt">
			<label for="excerpt"><?php _e( 'Excerpt', $this->text_domain ); ?></label>
			<textarea id="excerpt" name="classified_data[post_excerpt]" rows="2" ><?php echo (isset( $classified_data['post_excerpt'] ) ) ? esc_textarea($classified_data['post_excerpt']) : ''; ?></textarea>
			<p class="description"><?php _e( 'A short excerpt of your classified.', $this->text_domain ); ?></p>
		</div>

		<?php
		//get related hierarchical taxonomies
		$taxonomies = get_taxonomies(array( 'public' => true, 'hierarchical' => true ), 'objects');
		//Loop through the taxonomies that apply
		foreach($taxonomies as $taxonomy):

		$tax_name = $taxonomy->name;
		$labels = $taxonomy->labels;
		//Get this Taxonomies terms
		$selected_cats = array_values( wp_get_post_terms($classified_data['ID'], $tax_name, array('fields' => 'ids') ) );

		if( ! cf_supports_taxonomy($tax_name)) continue;

		?>

		<div id="taxonomy-<?php echo $tax_name; ?>" class="taxonomydiv">
			<label><?php echo $labels->all_items; ?></label>

			<div id="<?php echo $tax_name; ?>_all" class="tax_panel">
				<?php
				$name = ( $tax_name == 'category' ) ? 'post_category' : 'tax_input[' . $tax_name . ']';
				echo "<input type='hidden' name='{$name}[]' value='0' />"; 		// Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
				?>
				<ul id="<?php echo $tax_name; ?>_checklist" class="list:<?php echo $labels->name; ?> categorychecklist form-no-clear">
					<?php wp_terms_checklist( 0, array( 'taxonomy' => $tax_name, 'selected_cats' => $selected_cats, 'checked_ontop' => false ) ) ?>
				</ul>
			</div>
			<br />
		</div>
		<?php endforeach; ?>

		<div class="clear"></div>

		<?php
		//get related non-hierarchical taxonomies
		$tags = get_taxonomies(array( 'public' => true, 'hierarchical' => false ), 'objects');

		//Loop through the taxonomies that apply
		foreach($tags as $tag):

		$tag_name = $tag->name;
		$labels = $tag->labels;

		//Get this Taxonomies terms
		$tag_list = strip_tags(get_the_term_list( $classified_data['ID'], $tag_name, '', ',', '' ));

		if( ! cf_supports_taxonomy($tag_name)) continue;
		?>

		<div class="editfield">
			<div id="<?php echo $tag_name; ?>-checklist" class="tagchecklist">
				<label><?php echo $labels->name . ': ' . $labels->add_or_remove_items; ?>
					<input id="tag_<?php echo $tag_name; ?>" name="tag_input[<?php echo $tag_name; ?>]" type="text" value="<?php echo $tag_list?>" />
				</label>
			</div>
			<br />
		</div>
		<script type="text/javascript" > jQuery('#tag_<?php echo $tag_name; ?>').tagsInput({width:'auto'}); </script>
		<?php endforeach; ?>


		<div class="editfield" >
			<label for="title"><?php _e( 'Status', $this->text_domain ); ?></label>
			<div id="status-box">
				<select name="classified_data[post_status]" id="classified_data[post_status]">
					<option value="publish" <?php selected( isset( $classified_data['post_status'] ) && 'publish' == $classified_data['post_status'] ); ?>><?php _e( 'Published', $this->text_domain ); ?></option>
					<option value="draft" <?php selected( isset( $classified_data['post_status'] ) && 'draft' == $classified_data['post_status'] ); ?>><?php _e( 'Draft', $this->text_domain ); ?></option>
				</select>
			</div>
			<p class="description"><?php _e( 'Select a status for your classified.', $this->text_domain ); ?></p>
		</div>

		<?php if ( class_exists( 'CustomPress_Core' ) ) : ?>
		<div class="editfield">
			<?php
			global $post, $CustomPress_Core;
			$post->post_type    = 'classifieds';
			$post->ID           = $classified_data['ID'];
			$CustomPress_Core->display_custom_fields();
			?>
		</div>
		<?php endif; ?>

		<div class="submit">
			<?php wp_nonce_field( 'verify' ); ?>
			<input type="submit" value="<?php _e( 'Save Changes', $this->text_domain ); ?>" name="update_classified">

			<input type="button" value="<?php _e( 'Cancel', $this->text_domain ); ?>" onclick="location.href='<?php echo get_permalink($this->my_classifieds_page_id); ?>'">
		</div>
	</form>

	<script type="text/javascript">jQuery('#cf_update_form').validate();</script>
</div><!-- .cf_update_form -->
<!-- End Update Classifieds -->