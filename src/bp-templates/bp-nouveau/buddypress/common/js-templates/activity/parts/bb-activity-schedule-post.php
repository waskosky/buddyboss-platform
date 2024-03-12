<?php
/**
 * The template for displaying activity post form buttons
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-schedule-post.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-schedule-post">
	<div class="bb-schedule-post_dropdown_section">
		<a href="#" class="bb-schedule-post_dropdown_button">
			<i class="bb-icon-f bb-icon-clock"></i>
			<i class="bb-icon-f bb-icon-caret-down"></i>
		</a>

		<div class="bb-schedule-post_dropdown_list">
			<ul>
				<li><a href="#" class="bb-schedule-post_action"><i class="bb-icon-l bb-icon-calendar"></i>Schedule Post</a></li>
				<li><a href="#"><i class="bb-icon-l bb-icon-pencil"></i>View Schedule Posts</a></li>
			</ul>
		</div>

		<div class="bb-schedule-post_modal">
			<div class="bb-action-popup" id="bb-schedule-post_form_modal" style="display: none">
				<transition name="modal">
					<div class="modal-mask bb-white bbm-model-wrap">
						<div class="modal-wrapper">
							<div class="modal-container">
								<header class="bb-model-header">
									<h4><span class="target_name"><?php echo esc_html__( 'Schedule post', 'buddyboss' ); ?></span></h4>
									<a class="bb-close-action-popup bb-model-close-button" href="#">
										<span class="bb-icon-l bb-icon-times"></span>
									</a>
								</header>
								<div class="bb-action-popup-content">
									<p class="schedule-date">July 12, 2023 at 3:27 pm</p>

									<label>Date</label>
									<div class="input-field">
										<input type="text" name="" id="" class="bb-schedule-activity-date-field">
										<i class="bb-icon-f bb-icon-calendar"></i>
									</div>

									<label>Time</label>
									<div class="input-field">
										<input type="text" name="" id="" class="bb-schedule-activity-time-field">
										<i class="bb-icon-f bb-icon-clock"></i>
									</div>

									<p><a href="#">View all scheduled posts <i class="bb-icon-f bb-icon-arrow-right"></i></a></p>
								</div>

								<footer class="bb-model-footer">
									<a href="#" class="button button-outline bb-schedule-activity-cancel">Back</a>
									<a class="button bb-schedule-activity" href="#">Next</a>
								</footer>
							</div>
						</div>
					</div>
				</transition>
			</div> <!-- .bb-action-popup -->
		</div>
	</div>
</script>
