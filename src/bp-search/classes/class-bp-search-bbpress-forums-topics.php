<?php
/**
 * @todo add description
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Bp_Search_bbPress_Topics' ) ) :

	/**
	 * BuddyPress Global Search  - search bbpress forums topics class
	 */
	class Bp_Search_bbPress_Topics extends Bp_Search_bbPress {
		public $type = 'topic';

		function sql( $search_term, $only_totalrow_count = false ) {
			global $wpdb;

			$bp_prefix = bp_core_get_table_prefix();

			$query_placeholder = array();

			if ( $only_totalrow_count ) {
				$columns = ' COUNT( DISTINCT p.id ) ';
			} else {
				$columns             = " DISTINCT p.id , '{$this->type}' as type, p.post_title LIKE %s AS relevance, p.post_date as entry_date  ";
				$query_placeholder[] = '%' . $search_term . '%';
			}

			$from = "{$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_bbp_forum_id'";

			/**
			 * Filter the MySQL JOIN clause for the topic Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param string $join_sql JOIN clause.
			 */
			$from = apply_filters( 'bp_forum_topic_search_join_sql', $from );

			$tax = array();

			if ( bp_is_search_post_type_taxonomy_enable( bbpress()->topic_tag_tax_id, $this->type ) ) {
				$tax[] = bbpress()->topic_tag_tax_id;
			}

			$where_clause = ' WHERE ';

			$tax_sql = '';
			// Tax query left join.
			if ( ! empty( $tax ) ) {
				$tax_sql = " LEFT JOIN {$wpdb->term_relationships} r ON p.ID = r.object_id ";
			}

			// Tax query.
			if ( ! empty( $tax ) ) {

				$tax_in_arr = array_map(
					function ( $t_name ) {
						return "'" . $t_name . "'";
					},
					$tax
				);

				$tax_in = implode( ', ', $tax_in_arr );

				$tax_sql            .= " WHERE r.term_taxonomy_id IN (SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON 
					  t.term_id = tt.term_id WHERE ( t.slug LIKE %s OR t.name LIKE %s ) AND  tt.taxonomy IN ({$tax_in}) )";
				$query_placeholder[] = '%' . $search_term . '%';
				$query_placeholder[] = '%' . $search_term . '%';
				$where_clause        = ' OR ';
			}

			$group_memberships = '';
			if ( bp_is_active( 'groups' ) ) {
				$group_memberships = bp_get_user_groups(
					get_current_user_id(),
					array(
						'is_admin' => null,
						'is_mod'   => null,
					)
				);

				$group_memberships = wp_list_pluck( $group_memberships, 'group_id' );

				$restricted_groups = groups_get_groups(
					array(
						'fields'      => 'ids',
						'status'      => array( 'private', 'hidden' ),
						'show_hidden' => true,
						'per_page'    => - 1,
					)
				);

				if ( ! empty( $restricted_groups ) && ! empty( $restricted_groups['groups'] ) ) {
					$restricted_groups = $restricted_groups['groups'];
				} else {
					$restricted_groups = array();
				}

				$group_memberships = array_diff( $restricted_groups, $group_memberships );
			}

			if ( current_user_can( 'read_hidden_forums' ) ) {
				$post_status = array( "'publish'", "'private'", "'hidden'" );
			} elseif ( current_user_can( 'read_private_forums' ) ) {
				$post_status = array( "'publish'", "'private'" );
			} else {
				$post_status = array( "'publish'" );
			}
			
			$post_status     = join( ',', $post_status );
			$group_forum_ids = array();

			if ( ! empty( $group_memberships ) ) {
				$in = array_map(
					function ( $group_id ) {
						return ',\'' . maybe_serialize( array( $group_id ) ) . '\'';
					},
					$group_memberships
				);

				$in = implode( '', $in );
				$in = trim( $in, ',' );

				// Get only parent forum id that are associate with group.
				$group_forum_query = "SELECT DISTINCT p.ID as forum_id 
					FROM $wpdb->posts p 
						LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID
					WHERE 1=1
						AND p.post_type = %s
						AND p.post_parent = '0'
						AND p.post_status IN ( {$post_status} ) 
						AND pm.meta_key = '_bbp_group_ids' 
						AND pm.meta_value IN( $in )";

				$group_forum_ids = $wpdb->get_col( $wpdb->prepare( $group_forum_query, bbp_get_forum_post_type() ) );
			}

			$group_forum_id_in = empty( $group_forum_ids ) ? '-1' : implode( ',', $group_forum_ids );
			
			// Get only parent forum id that are not associate any group
			$forum_query = "SELECT DISTINCT p.ID as forum_id 
				FROM $wpdb->posts p 
				WHERE 1=1
					AND p.post_type = %s
					AND p.post_parent = '0'
					AND p.post_status IN ( {$post_status} )
					AND p.ID NOT IN ( {$group_forum_id_in} )";

			$forum_ids       = $wpdb->get_col( $wpdb->prepare( $forum_query, bbp_get_forum_post_type() ) );
			$forum_child_ids = array();

			// Get all nested child forum id according parent forum.
			foreach ( $forum_ids as $forum_id ) {
				$single_forum_child_ids = $this->nested_child_forum_ids( $forum_id );
				$forum_child_ids        = array_merge( $forum_child_ids, $single_forum_child_ids );
			}

			// Merge all forum and all nested child forum ids. This ids are also filter with group.
			$all_forum_ids = array_merge( $forum_ids, $forum_child_ids );
			$forum_id_in   = implode( ',', $all_forum_ids );

			$where   = array();
			$where[] = '1=1';
			$where[] = "(post_title LIKE %s OR ExtractValue(post_content, '//text()') LIKE %s)";
			$where[] = "post_type = '{$this->type}'";

			$where[] = " pm.meta_value IN ( $forum_id_in ) ";
			$query_placeholder[] = '%' . $search_term . '%';
			$query_placeholder[] = '%' . $search_term . '%';

			/**
			 * Filters the MySQL WHERE conditions for the forum topic Search query.
			 *
             * @since BuddyBoss 1.5.6
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 */
			$where = apply_filters( 'bp_forum_topic_search_where_sql', $where );

			$sql = 'SELECT ' . $columns . ' FROM ' . $from . $tax_sql . $where_clause . implode( ' AND ', $where );

			$query = $wpdb->prepare( $sql, $query_placeholder ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			return apply_filters(
				'Bp_Search_Forums_sql',
				$query,
				array(
					'search_term'         => $search_term,
					'only_totalrow_count' => $only_totalrow_count,
				)
			);
		}

		/**
		 * Get all nested child forum ids.
		 * 
		 * @since BuddyBoss 1.6.2
		 * 
		 * @uses bbp_get_forum_post_type() Get forum post type.
		 * 
		 * @param int $forum_id
		 * 
		 * @return array
		 */
		public function nested_child_forum_ids( $forum_id ) {
			global $wpdb;

			// SQL query for getting all nested child forum id from parent forum id.
			$sql = "SELECT ID
				FROM  ( SELECT * FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ( 'publish', 'private', 'hidden' ) ) forum_sorted,
					  ( SELECT @pv := %d ) initialisation
				WHERE FIND_IN_SET( post_parent, @pv )
				AND   LENGTH( @pv := CONCAT(@pv, ',', ID ) )";

			$child_forum_ids = $wpdb->get_col( $wpdb->prepare( $sql, bbp_get_forum_post_type(), $forum_id ) );

			return $child_forum_ids;
		}

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @return object Bp_Search_Forums
		 * @since BuddyBoss 1.0.0
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if ( null === $instance ) {
				$instance = new Bp_Search_bbPress_Topics();
			}

			// Always return the instance
			return $instance;
		}

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		private function __construct() {
			/* Do nothing here */
		}

	}

	// End class Bp_Search_Posts

endif;

