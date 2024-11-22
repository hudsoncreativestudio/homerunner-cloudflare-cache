<?php
/**
 * Abstract Background Process class.
 *
 * @package HomerunnerCfCache
 */
namespace HomerunnerCfCache\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract WP_Background_Process class.
 * 
 * @abstract
 * @extends AsyncRequest
 */
abstract class BackgroundProcess extends AsyncRequest {
	/**
	 * Action
	 *
	 * (default value: 'background_process')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_process';

	/**
	 * Start time of current process.
	 *
	 * (default value: 0)
	 *
	 * @var int
	 * @access protected
	 */
	protected $start_time = 0;

	/**
	 * Cron_hook_identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_hook_identifier;

	/**
	 * Cron_interval_identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_interval_identifier;

	/**
	 * Set true to complete single task in each process.
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $single_task = false;

	/**
	 * Set true to save batch data after each task run.
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $save_after_task = false;

	/**
	 * Initiate new background process
	 */
	public function __construct() {
		$this->prefix = 'wp_' . get_current_blog_id();

		parent::__construct();

		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';

		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );
		add_action( 'admin_init', array( $this, 'resume_queued_process' ) );
	}

	/**
	 * Schedule cronjob if queued not empty, but no process is locked.
	 *
	 * @access public
	 * @return void
	 */
	public function resume_queued_process() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( get_site_transient( $this->identifier . '_resume_lock' ) ) {
			return;
		}

		if ( ! $this->is_queue_empty() && ! $this->is_process_locked() ) {
			$this->maybe_schedule_event();

			// Avoid running this to often.
			set_site_transient( $this->identifier . '_resume_lock', time(), 30 );
		}
	}

	/**
	 * Send new loopback request to resume process.
	 *
	 * @access public
	 * @return void
	 */
	public function dispatch() {
		// Do not dispath when background process already running.
		if ( $this->is_process_locked() ) {
			return;
		}

		// Schedule the cron healthcheck.
		$this->maybe_schedule_event();

		if ( $this->is_paused() ) {
			return;
		}

		// Perform remote post.
		parent::dispatch();
	}

	/**
	 * Push to queue
	 *
	 * @param mixed $data Data.
	 *
	 * @return $this
	 */
	public function push_to_queue( $data ) {
		$this->data[] = $data;

		return $this;
	}

	/**
	 * Save queue
	 *
	 * @return $this
	 */
	public function save() {
		$key = $this->generate_key();

		if ( ! empty( $this->data ) ) {
			update_site_option( $key, $this->data );
		}

		return $this;
	}

	/**
	 * Update queue
	 *
	 * @param string $key Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
	public function update( $key, $data ) {
		update_site_option( $key, $data );

		return $this;
	}

	/**
	 * Delete queue
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		delete_site_option( $key );

		return $this;
	}

	/**
	 * Generate key
	 *
	 * Generates a unique key based on microtime. Queue items are
	 * given a unique key so that they can be merged upon save.
	 *
	 * @param int $length Length.
	 *
	 * @return string
	 */
	protected function generate_key( $length = 64 ) {
		$unique  = md5( microtime() . rand() );
		$prepend = $this->identifier . '_batch_';

		return substr( $prepend . $unique, 0, $length );
	}

	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing
		session_write_close();

		if ( $this->is_process_locked() ) {
			// Background process already running.
			wp_die();
		}

		if ( $this->is_paused() ) {
			// Background process paused.
			wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			wp_die();
		}

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->handle();

		wp_die();
	}

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	protected function is_queue_empty() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $this->identifier . '_batch_%';

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );

		return ! ( $count > 0 );
	}

	/**
	 * Is job running?
	 *
	 * @return boolean
	 */
	public function is_running() {
		return ! $this->is_queue_empty() && ! $this->is_cancelled() && ! $this->is_paused();
	}

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 */
	public function is_process_locked() {
		if ( get_site_transient( $this->identifier . '_process_lock' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 */
	protected function lock_process() {
		$this->start_time = time(); // Set start time of current process.

		$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60; // 1 minute
		$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', $lock_duration );

		set_site_transient( $this->identifier . '_process_lock', microtime(), $lock_duration );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return void
	 */
	protected function unlock_process() {
		delete_site_transient( $this->identifier . '_process_lock' );
	}

	/**
	 * Check if the process is cancelled.
	 */
	public function is_cancelled() {
		if ( get_site_transient( $this->identifier . '_cancel_lock' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Lock cancellation
	 */
	protected function lock_cancellation() {
		set_site_transient( $this->identifier . '_cancel_lock', microtime(), 60 );
	}

	/**
	 * Unlock cancellation
	 */
	protected function unlock_cancellation() {
		delete_site_transient( $this->identifier . '_cancel_lock' );
	}

	/**
	 * Check if the process is paused.
	 */
	public function is_paused() {
		if ( get_site_transient( $this->identifier . '_pause_lock' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Create pause lock
	 */
	protected function lock_pause( $for = DAY_IN_SECONDS ) {
		set_site_transient( $this->identifier . '_pause_lock', microtime(), $for );
	}

	/**
	 * Unlock pause
	 */
	protected function unlock_pause() {
		delete_site_transient( $this->identifier . '_pause_lock' );
	}

	/**
	 * Get batch
	 *
	 * @return \stdClass Return the first batch from the queue
	 */
	protected function get_batch() {
		global $wpdb;

		$table        = $wpdb->options;
		$column       = 'option_name';
		$key_column   = 'option_id';
		$value_column = 'option_value';

		if ( is_multisite() ) {
			$table        = $wpdb->sitemeta;
			$column       = 'meta_key';
			$key_column   = 'meta_id';
			$value_column = 'meta_value';
		}

		$key = $this->identifier . '_batch_%';

		$query = $wpdb->get_row( $wpdb->prepare( "
			SELECT *
			FROM {$table}
			WHERE {$column} LIKE %s
			ORDER BY {$key_column} ASC
			LIMIT 1
		", $key ) );

		$batch       = new \stdClass();
		$batch->key  = isset( $query->$column ) ? $query->$column : '';
		$batch->data = isset( $query->$value_column ) ? maybe_unserialize( $query->$value_column ) : [];

		return $batch;
	}

	/**
	 * Handle
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 */
	public function handle() {
		$this->lock_process();

		if ( $this->is_cancelled() ) {
			// homelocal_log_debug( 'is_cancelled, running cancel.' );

			$this->cancel();

			$this->unlock_process();

			wp_die();
		}

		do {
			$batch = $this->get_batch();

			// homelocal_log_debug( 'Batch: data for key #' . $batch->key, $batch->data );

			foreach ( $batch->data as $key => $value ) {
				$saved = false;

				$task = $this->task( $value );

				// Cancel batch.
				if ( -1 === $task ) {
					$batch->data = [];
					// homelocal_log_debug( 'Batch: task -1' );
					break;
				}

				if ( false !== $task ) {
					// homelocal_log_debug( 'Batch: task not false ' . $key, [ $value ] );

					$batch->data[ $key ] = $task;

					// do not proceed to next task if current task is not false.
					break;

				} else {
					unset( $batch->data[ $key ] );
				}

				if ( $this->save_after_task ) {
					// Update or delete current batch.
					if ( ! empty( $batch->data ) ) {
						$this->update( $batch->key, $batch->data );
					} else {
						$this->delete( $batch->key );
					}

					$saved = true;
				}

				if ( $this->time_exceeded() || $this->memory_exceeded() || $this->single_task ) {
					// if ( $this->time_exceeded() ) {
					// 	homelocal_log_debug( 'Batch: time exceeded' );
					// } elseif ( $this->memory_exceeded() ) {
					// 	homelocal_log_debug( 'Batch: memory exceeded' );
					// } elseif ( $this->single_task ) {
					// 	homelocal_log_debug( 'Batch: single task' );
					// }

					// Batch limits reached.
					break;
				}

				if ( $this->is_cancelled() || $this->is_paused() ) {
					// homelocal_log_debug( 'Batch: cancelled or paused.' );
					break;
				}
			}

			if ( ! $saved ) {
				// Update or delete current batch.
				if ( ! empty( $batch->data ) ) {
					$this->update( $batch->key, $batch->data );
				} else {
					$this->delete( $batch->key );
				}
			}

		} while (
			! $this->time_exceeded()
			&& ! $this->memory_exceeded()
			&& ! $this->is_queue_empty()
			&& ! $this->single_task
			&& ! $this->is_cancelled()
			&& ! $this->is_paused()
		);

		$this->unlock_process();

		// Start next batch or complete process.
		if ( $this->is_cancelled() ) {
			// homelocal_log_debug( 'is_cancelled, running cancel.' );
			$this->cancel();

		} else if ( $this->is_paused() ) {
			$this->maybe_schedule_event();

		} else if ( ! $this->is_queue_empty() ) {
			$this->dispatch();

		} else {
			$this->complete();
		}

		wp_die();
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return intval( $memory_limit ) * 1024 * 1024;
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$time_limit = property_exists( $this, 'time_limit' ) ? $this->time_limit : 20;

		$finish = $this->start_time + apply_filters( $this->identifier . '_default_time_limit', $time_limit );

		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_time_exceeded', $return );
	}

	/**
	 * cancel.
	 */
	protected function cancel() {
		// Delete all batches.
		$this->delete_batches();

		// Unlock cancellation.
		$this->unlock_cancellation();

		// Unlock pause.
		$this->unlock_pause();

		// Unschedule the cron healthcheck.
		$this->clear_scheduled_event();
	}

	/**
	 * Complete.
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		// Incase it remains.
		$this->unlock_cancellation();

		// Unlock pause.
		$this->unlock_pause();

		// Unschedule the cron healthcheck.
		$this->clear_scheduled_event();
	}

	/**
	 * Schedule cron healthcheck
	 *
	 * @access public
	 * @param mixed $schedules Schedules.
	 * @return mixed
	 */
	public function schedule_cron_healthcheck( $schedules ) {
		$interval = apply_filters( $this->cron_interval_identifier, 5 );

		if ( property_exists( $this, 'cron_interval' ) ) {
			$interval = apply_filters( $this->cron_interval_identifier, $this->cron_interval );
		}

		// Adds every 5 minutes to the existing schedules.
		$schedules[ $this->cron_interval_identifier ] = array(
			'interval' => MINUTE_IN_SECONDS * $interval,
			/* translators: %d: interval */
			'display'  => sprintf( __( 'Every %d minutes', 'homelocal' ), $interval ),
		);

		return $schedules;
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		if ( $this->is_process_locked() ) {
			// Background process already running.
			exit;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			$this->clear_scheduled_event();
			exit;
		}

		$this->handle();

		exit;
	}

	/**
	 * Schedule event
	 */
	protected function maybe_schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time(), $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Clear scheduled event
	 */
	protected function clear_scheduled_event() {
		$timestamp = wp_next_scheduled( $this->cron_hook_identifier );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->cron_hook_identifier );
		}
	}

	/**
	 * Delete all batches.
	 *
	 * @return $this
	 */
	protected function delete_batches() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $this->get_batches_search_key();

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) ); // @codingStandardsIgnoreLine.

		return $this;
	}

	public function get_batches() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $this->get_batches_search_key();

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$table} WHERE {$column} LIKE %s", $key ) ); // @codingStandardsIgnoreLine.

		$data = [];
		foreach ( $rows as $row ) {
			$data[ $row->option_name ] = maybe_unserialize( $row->option_value );
		}

		return $data;
	}

	/**
	 * Number of batch process remaining.
	 */
	public function get_batches_count() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $this->get_batches_search_key();

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );

		return (int) $count;
	}

	/**
	 * Get batches search key.
	 */
	protected function get_batches_search_key() {
		global $wpdb;

		return $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';
	}

	/**
	 * Cancel Process
	 *
	 * Stop processing queue items, clear cronjob and delete batch.
	 */
	public function request_cancellation() {
		$this->lock_cancellation();

		$this->unlock_pause();

		// Restart the process if not already running somehow.
		if ( ! $this->is_process_locked() ) {
			$this->cancel();
		}
	}

	public function request_pause( $for = 3600 ) {
		$this->lock_pause( $for );

		$this->unlock_cancellation();
	}

	public function resume_process() {
		$this->unlock_pause();

		// Schedule cron healthcheck.
		$this->maybe_schedule_event();

		// Restart the process if not already running somehow.
		if ( ! $this->is_process_locked() ) {
			$this->dispatch();
		}
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	abstract protected function task( $item );
}