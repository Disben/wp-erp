<?php
namespace WeDevs\ERP;


/**
* ERP Log reporting class API
*
* @since 0.1 
*
* @package wp-erp
*/
class Log {
	
	/**
     * Initializes the Log class
     *
     * Checks for an existing Log instance
     * and if it doesn't find one, creates it.
     */
    public static function instance() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Add a new log
     *
     * @since 0.1 
     * 
     * @param array $data 
     *
     * @return inserted_id
     */
	public function add( $data ) {
		return $this->insert_log( $data );
	}

	/**
	 * Get logs base on criteria
	 *
	 * @since 0.1 
	 * 
	 * @param  array $data
	 * 
	 * @return object [collection of log]
	 */
	public function get( $args = array() ) {

	    $defaults = array(
	        'number'     => 20,
	        'offset'     => 0,
	        'no_object'  => false
	    );

	    $args  = wp_parse_args( $args, $defaults );
	    $where = $results = [];

	    $audit_log = new \WeDevs\ERP\Admin\Models\Audit_Log();
	    
	    if ( isset( $args['component'] ) && ! empty( $args['component'] ) ) {
	        $audit_log = $audit_log->where( 'component', $args['component'] );
	    }

	    if ( isset( $args['sub_component'] ) && ! empty( $args['sub_component'] ) ) {
	        $audit_log = $audit_log->where( 'sub_component', $args['sub_component'] );
	    }

	    if ( isset( $args['old_value'] ) && ! empty( $args['old_value'] ) ) {
	        $audit_log = $audit_log->where( 'old_value', $args['old_value'] );
	    }

	    if ( isset( $args['new_value'] ) && ! empty( $args['new_value'] ) ) {
	        $audit_log = $audit_log->where( 'new_value', $args['new_value'] );
	    }
		
		if ( isset( $args['changetype'] ) && ! empty( $args['changetype'] ) ) {
	        $audit_log = $audit_log->where( 'changetype', $args['changetype'] );
	    }

		if ( isset( $args['created_by'] ) && ! empty( $args['created_by'] ) ) {
	        $audit_log = $audit_log->where( 'created_by', (int)$args['created_by'] );
	    }

	    $cache_key = 'erp-get-audit-log' . md5( serialize( $args ) );
	    $results   = wp_cache_get( $cache_key, 'wp-erp' );
	    $users     = array();

	    if ( false === $results ) {
	        $results = $audit_log->skip( $args['offset'] )
	                    ->take( $args['number'] )
	                    ->get()
	                    ->toArray();

	        $results = erp_array_to_object( $results );
	        wp_cache_set( $cache_key, $results, 'wp-erp', HOUR_IN_SECONDS );
	    }

	    return $results;
	}

	/**
	 * Insert a new log record
	 *
	 * @since 0.1 
	 * 
	 * @param  array $args
	 * 
	 * @return integer [inserted id]
	 */
	public function insert_log( $args ) {
		
		$defaults = array(
			'component'     => 'HRM',
			'sub_component' => '',
			'old_value'     => '',
			'new_value'     => '',
			'message'       => '',
			'changetype'    => 'add',
			'created_by'    => ''
	    );

	    $fields = wp_parse_args( $args, $defaults );
	    
	    do_action( 'erp_after_before_audit_log', $fields );

	    $inserted = \WeDevs\ERP\Admin\Models\Audit_Log::create( $fields );

	    do_action( 'erp_after_insert_audit_log', $inserted, $fields );
	    
	    return $inserted->id;
	}

}