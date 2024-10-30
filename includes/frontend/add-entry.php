<?php
class cfas_add_entry{

    public static $form_id;
    public $posted_data;
    public $user_id;
    public $tmp_files    = array();
    public $upload_files = array();
    public $invalid_form = false;
    /**
     * The single instance of the class.
     *
     * @var cfas_add_entry
     */
    protected static $_instance = null;

    /**
     * Main cfas_add_entry Instance.
     *
     * Ensures only one instance of cfas_add_entry is loaded or can be loaded.
     *
     * @static
     * @return cfas_add_entry - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new cfas_add_entry();
        }
        return self::$_instance;
    }

    /**
     * Initial function
     *
     * Call necessary hooks (actions and filters).
     *
     */
    public function init() {
        add_filter( 'wpcf7_posted_data', array( $this, 'posted_data' ) );
        add_action( 'wpcf7_mail_sent', array( $this, 'add_entry' ), 11 );

        // Handle file upload entry
        add_filter( 'cfas_new_invalid_fields', array( $this, 'is_form_invalid' ), 10 );
        add_filter( 'wpcf7_posted_data', array( $this, 'add_files_name' ) );
        add_filter( 'wpcf7_skip_spam_check', array( $this, 'move_file_upload' ), 10, 2 );
        add_action( 'wpcf7_mail_failed', array( $this, 'remove_uploaded_files' ) );
    }

    public function is_form_invalid( $invalid_fields ) {
        $invalid_fields = count( $invalid_fields );
        
        if( $invalid_fields ) {
            $this->invalid_form = true;
        }
    }
    
    public function add_files_name( $data ) {
        $tmp_files = array();

        foreach ( $_FILES as $input=>$info ) {
            $tmp_files[$input] = $info['name'];
        }

        $this->tmp_files = $tmp_files;

        return $data;
    }

    public function move_file_upload( $spam, $elm ) {

        if ( $this->invalid_form ) {
           $this->tmp_files = array();
        } else {
            $tmp_files    = $this->tmp_files;
            $upload_files = $elm->uploaded_files();
            $new_upload   = array();
            $upload_dir   = $this->get_upload_directory();
            $upload_url   = $this->get_upload_url();

            wp_mkdir_p( $upload_dir );

            foreach ( $upload_files as $input=>$tmp_path ) {
                $file_name = $tmp_files[$input];
                $new_path  = $this->handle_upload_file( $file_name, $tmp_path, $upload_dir, $upload_url );

                $new_upload[$input]['dir'] = $new_path['dir'];
                $new_upload[$input]['url'] = $new_path['url'];
            }


            $this->upload_files = $new_upload;
        }
        

        return $spam;
    }

    public function remove_uploaded_files() {
        $uploaded_files = $this->upload_files;

        foreach ( $uploaded_files as $path ) {
            unlink( $path['dir'] );
        }
    }

    public function get_upload_directory() {
        return path_join( $this->get_upload_path( 'dir' ), 'cfas_upload' );
    }

    public function get_upload_url() {
        return path_join( $this->get_upload_path( 'url' ), 'cfas_upload' );
    }

    public function handle_upload_file( $file_name, $tmp_path, $upload_dir, $upload_url ) {
        $new_path = array( 'dir' => false, 'url' => false );
        $new_name = wp_unique_filename( $upload_dir, $file_name );
        $new_dir  = path_join( $upload_dir, $new_name );
        $new_url  = path_join( $upload_url, $new_name );

        if( rename( $tmp_path, $new_dir) ) {
            $new_path['dir'] = $new_dir;
            $new_path['url'] = $new_url;

            $this->set_permissions( $new_dir );
        }

        return $new_path;
    }

    public static function set_permissions( $path ) {
        @chmod( $path, 0644 );
    }

    /**
     * Process form posted data.
     *
     * @param array $posted_data Form posted data.
     *
     * @return array
     */
    public function posted_data( $posted_data ) {
        $this->set_current_user_id( $posted_data );

        $this->posted_data = $this->filter_posted_data( $posted_data );

        return $this->posted_data;
    }

    /**
     * Set id of user that submitted form.
     *
     * @param array $data Form posted data.
     */
    public function set_current_user_id( $data ) {
        $user_id = sanitize_key( $data['cfas_user'] );
        $this->user_id = $user_id > 0 ? $user_id : false;
    }

    public function get_upload_path( $type = false ) {
        $path = wp_get_upload_dir();

        if( 'dir' === $type ) {
            return $path['basedir'];
        } elseif ( 'url' === $type ) {
            return $path['baseurl'];
        }

        return $path;
    }

    /**
     * Filter form posted data.
     *
     * Remove hidden input data to prevent to save in form entry.
     *
     * @param array $cf7 Form posted data.
     *
     * @return array Filtered data.
     */
    public function filter_posted_data( $cf7 ) {
        $new_data = $cf7;

        unset( $new_data['cfas_user'] );
        unset( $new_data['cfas_clicked_btn'] );
        unset( $new_data['cfas_curr_page'] );
        unset( $new_data['cfas_target_page'] );
		unset( $new_data['cfas_submit_validate'] );

        return $new_data;
    }

    /**
     *
     * @param object $cf7 The current form object.
     */
    public function add_entry ( $cf7 ) {
        $form_id  = $cf7->id;
        $entry_id = $this->get_last_entry_id() + 1;
        $data     = $this->posted_data;
        $user_ip  = $this->get_client_ip();
		$user_ip  = sanitize_textarea_field( $user_ip );
        $user_id  = $this->user_id;
        $date     = date('Y/m/d');
        $time     = date('H:i');

        foreach ( $data as $name=>$value ) {
            if( ! empty( $value ) ) {
                $value = $this->get_value_string( $value );
                $input = $this->get_input_type( $cf7, $name );

                $this->insert_entry( $form_id, $entry_id, $name, $value, $input);
            }
        }

        // Add file uploads link
        $upload_files = $this->upload_files;

        foreach ( $upload_files as $input=>$path ) {
            if( $path['url'] ) {
                $this->insert_entry( $form_id, $entry_id, $input, $path['url'], 'file_upload');
            }
        }

        $this->set_entry_meta( $form_id, $entry_id, 'submit_date', $date );
        $this->set_entry_meta( $form_id, $entry_id, 'submit_time', $time );
        $this->set_entry_meta( $form_id, $entry_id, 'user_ip', $user_ip );

        if( $user_id ) {
            $this->set_entry_meta( $form_id, $entry_id, 'user_id', $user_id );
        }

    }

    public function get_last_entry_id() {
        global $wpdb;

        $sql = "SELECT entry_id
            FROM {$wpdb->prefix}cfas_entries
            ORDER BY id DESC
            LIMIT 1";

        return $wpdb->get_var( $sql );
    }

    public function get_value_string( $value ) {
        $string = null;
        if ( is_array( $value ) ) {
            $value  = cfas_sanitize_array( $value );
            $string = json_encode( $value );
        } else {
            $string = sanitize_textarea_field( $value );
        }

        return $string;
    }

    public function get_input_type( $form, $name ) {
        $tags = $form->scan_form_tags();

        foreach ( $tags as $tag ) {
            if( $name === $tag->name ) {
                return sanitize_textarea_field( $tag->basetype );
            }
        }

        return '';
    }

    public function insert_entry( $form_id, $entry_id, $name, $value, $input ){
        global $wpdb;
		$allow_insert = true;
		
		$allow_insert = apply_filters( 'cfas_insert_entry', $allow_insert, $form_id );
		
		if( ! $allow_insert ) {
			return '';
		}

        $sql = "INSERT INTO {$wpdb->prefix}cfas_entries
            (form_id,entry_id, entry_key,value,type,input) VALUES ($form_id,$entry_id,'$name','$value','entry','$input')";

        return $wpdb->query( $sql );
    }

    public function get_client_ip() {
        return $_SERVER['HTTP_CLIENT_IP'] ? : ($_SERVER['HTTP_X_FORWARDED_FOR'] ? : $_SERVER['REMOTE_ADDR']);
    }

    public function set_entry_meta( $form_id, $entry_id, $meta, $value ) {
        global $wpdb;
        $sql = "INSERT INTO {$wpdb->prefix}cfas_meta
            (form_id,entry_id,meta_key,meta_value) VALUES ($form_id,$entry_id,'$meta','$value')";

        return $wpdb->query( $sql );
    }

}

$cfas_add_entry = cfas_add_entry::instance();
$cfas_add_entry->init();
