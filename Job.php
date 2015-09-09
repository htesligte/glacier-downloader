<?php
/**
 * Created by PhpStorm.
 * User: henk
 * Date: 8-5-15
 * Time: 19:49
 */
use Aws\Glacier\GlacierClient;
class Job
{
    protected $start;
    protected $end;
    protected $schedule_time;
    protected $jobId;
    protected $start_time;
    protected $hash;
    protected $archive_id;
    protected $running;
    protected $finished;

    public function get( $key )
    {
        if( property_exists( $this, $key ) )
            return $this->$key;
        else
            return null;
    }

    public function set( $key, $value )
    {
        if( property_exists( $this, $key ) )
            $this->$key = $value;
    }

    public function toArray()
    {
        return array(
            'start' => $this->start,
            'end' => $this->end,
            'schedule_time' => $this->schedule_time instanceof DateTime ? $this->schedule_time->format('c') : null,
            'jobId' => $this->jobId,
            'start_time' => $this->start_time instanceof DateTime ? $this->start_time->format('c') : null,
            'hash' => $this->hash,
            'archive_id' => $this->archive_id,
            'running' => $this->running,
            'finished' => $this->finished,
        );
    }

    public function isRunning()
    {
        return (bool)$this->running;
    }

    public function isFinished()
    {
        return (bool)$this->finished;
    }

    public function getSize()
    {
        return (int)$this->end - (int)$this->start;
    }
}