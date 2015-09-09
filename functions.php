<?php
/**
 * Created by PhpStorm.
 * User: henk
 * Date: 8-5-15
 * Time: 19:48
 */
use Aws\Glacier\GlacierClient;
function client()
{
    static $client;
    if( is_null( $client ) )
    {
        $client = GlacierClient::factory( array(
            'profile' => 'default',
            'region' => 'us-west-2',
        ));
    }
    return $client;
}

/**
 * @param null $p
 * @return Job[]
 */
function parts( $p = null )
{
    static $parts;
    if( !is_null( $p ) )
        $parts = $p;
    else
        return $parts;
}


function archive_id( $archive_id = null )
{
	static $id;
	if( !is_null( $archive_id ) )
		$id = $archive_id;
	
	return $id;
}


function parse_xml( $xml_name )
{
    $data = simplexml_load_file( $xml_name );
    $archive_id = (string)$data->file;
	archive_id( $archive_id );
    $parts = array();
    foreach( $data->part as $part )
    {
        $job = new Job;
        $job->set( 'start' , (int)$part->start );
        $job->set( 'end' , (int)$part->end );
        $job->set( 'schedule_time' , new \DateTime( (string)$part->schedule_time ) );
        $job->set( 'jobId' , (string)$part->jobId );
        $job->set( 'hash' , (string)$part->hash );
        $job->set( 'start_time' , empty( $part->start_time ) ? '' : new \DateTime( (string)$part->start_time ) );
        $job->set( 'archive_id', $archive_id );
        $job->set( 'running', (int)$part->running );
        $job->set( 'finished', (int)$part->finished );
        $parts[] = $job;
    }
    parts( $parts );
}


function serialize_xml( $xml_name )
{
    $doc = new DOMDocument('1.0');
    $doc->formatOutput = true;
    $root = $doc->createElement( 'root' );
    $root = $doc->appendChild( $root );
    $em = $doc->createElement( 'file' );
    $text = $doc->createTextNode( archive_id() );
    $em->appendChild( $text );
    $root->appendChild( $em );


    foreach( parts() as $part )
    {
        $part = $part->toArray();
        $em = $doc->createElement('part' );
        foreach( $part as $key=>$value )
        {
            $child = $doc->createElement( $key );
            $text_node = $doc->createTextNode( $value );
            $child->appendChild( $text_node );
            $em->appendChild( $child );
        }
        $root->appendChild( $em );
    }
    $doc->save( XML_FILE );
}


/**
 * @return Job[]
 */
function get_running_jobs()
{
    $running_jobs = array();
    foreach( parts() as $job )
    {
        if( $job->isRunning() )
            $running_jobs[] = $job;
    }
    return $running_jobs;
}

function get_first_available_job()
{
    foreach( parts() as $part )
    {
        if( $part->isFinished() )
            continue;

        if( $part->isRunning() )
            continue;

        return $part;
    }
}

function collect_next_jobs()
{
    $cur_size = 0;
    $jobs = array();
    foreach( parts() as $job )
    {
        if( $job->isFinished() || $job->isRunning() )
            continue;

        if( ( $cur_size + $job->getSize() ) > MAX_SIZE_HOUR )
            break;

        $jobs[] = $job;
        $cur_size += $job->getSize();
    }
    return $jobs;
}

function downloadJob( Job $job )
{
	$filename = "E:/glacier/storage/" . $job->get('start') . "-" . $job->get( 'end' );    	
    $oResult = client()->getJobOutput(array(
        'accountId' => '-',
        'vaultName' => VAULT,
        'jobId' => $job->get( 'jobId'),
        'saveAs' => $filename,
    ));
	
	$contents = file_get_contents( $filename );
	
	$checksum = $oResult->get('checksum' ); 
	$equal = \Aws\Common\Hash\TreeHash::validateChecksum( $contents1, $checksum );
		
	if( !$equal )
	{
		echo "Restarting job due to incorrect checksum: $resulting_checksum is not equal to $checksum";		
		$job->set('finished', 0 );
    	$job->set( 'running', 0 );
	}
	else
	{
		echo "downloaded job: " . $job->get('jobId') . PHP_EOL;			
		$job->set('hash', $oResult->get('checksum') );
    	$job->set('finished', 1 );
    	$job->set( 'running', 0 );
	}
}

function start_retrieval_job( Job $job, DateTime $start_time )
{
    $oResult = client()->initiateJob( array(
        'accountId' => '-',
        'vaultName' => VAULT,
        'ArchiveId' => $job->get( 'archive_id' ),
        'RetrievalByteRange' => $job->get('start') . "-" . $job->get('end'),
        'Type' => 'archive-retrieval'
    ) );

    $job->set( 'start_time', $start_time );
    $job->set( 'running', 1 );
    $job->set( 'jobId', $oResult->get('jobId') );
}