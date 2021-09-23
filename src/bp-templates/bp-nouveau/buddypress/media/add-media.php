<?php
/**
 * BuddyBoss - Add Media
 *
 * @since BuddyBoss 1.0.0
 */

if ( ( ( bp_is_my_profile() && bb_user_can_create_media() ) || ( bp_is_group() && is_user_logged_in() && groups_can_user_manage_media( bp_loggedin_user_id(), bp_get_current_group_id() ) ) ) ) { ?>

	<div class="bb-media-actions-wrap has-search-form">
		<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss' ); ?></h2>
		<div class="bb-media-actions">
			<div id="search-media-form" class="media-search-form" data-bp-search="media">
				<form action="" method="get" class="bp-dir-search-form" id="media-search-form" autocomplete="off">
					<button type="submit" id="group-media-search-submit" class="nouveau-search-submit" name="media_search_submit">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
					</button>
					<label for="group-media-search" class="bp-screen-reader-text"><?php esc_html_e( 'Search Photos…', 'buddyboss' ); ?></label>
					<input id="group-media-search" name="media_search" type="search" placeholder="<?php esc_attr_e( 'Search Photos…', 'buddyboss' ); ?>">
				</form>
			</div> <!-- .media-search-form -->
			<a href="#" id="bp-add-media" class="bb-add-media button small outline"><?php esc_html_e( 'Add Photos', 'buddyboss' ); ?></a>
		</div>
	</div>

	<?php
	bp_get_template_part( 'media/uploader' );

} else {
	?>
	<div class="bb-media-actions-wrap">
		<h2 class="bb-title"><?php esc_html_e( 'Photos', 'buddyboss' ); ?></h2>
	</div>
	<?php
}
