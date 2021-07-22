<?php
/**
 * Main Cloud Messaging Class.
 *
 * Code will be organized on the last phase.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Powered by BuddyBoss Cloud Messaging.
 * Handles all live sockets messages requests in BuddyBoss.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Messaging_Cloud {

	/**
	 * Return the instance of BP_Messaging_Cloud class.
	 * @return BP_Messaging_Cloud|null
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been run previously
		if ( null === $instance ) {
			$instance = new BP_Messaging_Cloud();
			$instance->setup_actions();
		}

		// Always return the instance
		return $instance;

	}

	/**
	 * Contains all the WP hooks & filters.
	 */
	public function setup_actions() {

		if ( $this->is_activated() ) {

			add_action( 'wp_head', array( $this, 'messaging_script' ) );
//			add_action( 'admin_head', array( $this, 'messaging_script' ) ); // don't need it on admin end for now.

		}

	}

	/**
	 * Output's the cloud messaging script.
	 *
	 * @todo : Organize the code correctly.
	 */
	public function messaging_script() {

		?>

		<script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.0.1/socket.io.js"></script>

		<script>

            window.bp_messaging_cloud_data = <?php echo json_encode( array(
				'user_id'   => get_current_user_id(),
				'logged_in' => is_user_logged_in()
			) );
			?>;

            window.bp_messaging_cloud = {
                server: 'http://34.203.222.140:6969',
                socket: false,
                on_connected_cb: [],
                start: function () {
                    if (!this.socket) {
                        this.socket = io.connect(this.server);
                        this.handle_connect_err();
                        this.handle_disconnect();
                        this.handle_connect();
                    }
                },
                handle_connect_err: function () {
                    this.socket.on("connect_error", () => {
                        console.log('Error while connecting to BuddyBoss Cloud.');
                    });
                },
                handle_connect: function () {
                    var that = this;

                    this.socket.on("connect", function () {
                        that.on_connected_cb.forEach(function (cb) {
                            cb();
                        });
                    });

                    this.on_connect(function () {
                        console.log('Connected BuddyBoss Cloud.');
                        if (window.bp_messaging_cloud_data.logged_in) {
                            that.register_user();
                        }
                    });
                },
                handle_disconnect: function () {
                    this.on_disconnect(function () {
                        console.log('Reconnecting BuddyBoss Cloud.');
                        // this.socket.connect();
                    });
                },
                send: function (event_name, data) {
                    if (this.socket && this.socket.connected) {
                        this.socket.emit(event_name, data);
                    } else {
                        console.log('Cannot emit as socket is not connected.');
                    }
                },
                listen: function (ename, cb) {
                    if (this.socket && this.socket.connected) {
                        this.socket.on(ename, (data) => {
                            cb(data);
                        });
                    }
                },
                on_connect: function (cb) {
                    this.on_connected_cb.push(cb);
                },
                on_disconnect: function (cb) {
                    this.socket.on("disconnect", function (reason) {
                        // reconnect when servers disconnect.
                        if (reason === "io server disconnect") {
                            socket.connect();
                        }
                        cb(reason);
                    });
                },
                register_user: function () {
                    this.send('new user', window.bp_messaging_cloud_data.user_id);
                }
            };


            /**
             * BP Messaging Cloud for BP Messages Component.
             */
            window.bp_messaging_cloud_message = {
                users_typing: [], // helper to avoid multiple event being sent.
                start: function () {
                    var that = this;
                    window.bp_messaging_cloud.listen('new message', function (data) {
                        that.listen_messages(data.message, data.from, data.to, data.thread);
                    });
                },
                listen_messages: function (message, from_id, to_id, thread_id) {
                    console.log(message);
                },
                send_message: function (message_content, from_id, to_id, thread_id, message) {
                    window.bp_messaging_cloud.send('new message', {
                        'to': to_id,
                        'from': from_id,
                        'thread': thread_id,
                        'message_content': message_content,
                        'message': message
                    });
                },
                start_typing: function (to_id, thread_id) {
                    var that = this;
                    if (typeof that.users_typing[thread_id] == "undefined") {
                        that.users_typing[thread_id] = false;
                    }
                    if (!that.users_typing[thread_id]) {
                        that.users_typing[thread_id] = true;
                        window.bp_messaging_cloud.send('typing', {
                            'to': to_id,
                            'from': window.bp_messaging_cloud_data.user_id,
                            'thread': thread_id
                        });
                        // don't accept typing for next 2 sec.
                        setTimeout(function () {
                            that.users_typing[thread_id] = false;
                        }, 2000);
                        console.log('sent typing!');
                    }
                }
            };

            jQuery(document).ready(function () {
                window.bp_messaging_cloud.start();
                window.bp_messaging_cloud_message.start();
            })

		</script>

		<?php

	}

	/**
	 * Return this this feature is activated or not.
	 * @return bool
	 * @todo: Decide if we need this function or not.
	 */
	public function is_activated() {
		return true;
	}

}
