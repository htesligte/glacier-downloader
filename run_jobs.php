<?php
/**
 * Created by PhpStorm.
 * User: henk
 * Date: 6-5-15
 * Time: 21:04
 */
/*
 * Dit script wacht telkens een uur, start zo nodig een retrieval job,
 */
 
chdir( __DIR__ );
define( "XML_FILE", __DIR__ . "/100m.xml" );
define( "MAX_SIZE_HOUR", (32*1024*1024) ); // max 32mb per uur
require 'vendor/autoload.php';
require 'functions.php';
require 'Job.php';

use Aws\Glacier\GlacierClient;
define( "VAULT", "Backups_Server" );



$start_time = new \DateTime();
parse_xml( XML_FILE );
$running_jobs = get_running_jobs();
$most_recent_job = null;

// download running jobs
foreach( $running_jobs as $job )
{
    $oResult = client()->describeJob( array(
        'accountId' => '-',
        'vaultName' => VAULT,
        'jobId' => $job->get( "jobId" ),
    ));
    if( $oResult->get('StatusCode') == "Succeeded" )
        downloadJob( $job );
    elseif( $oResult->get('StatusCode') == "Failed" )
        $job->set( 'running', 0 );
}

// controleer of er een job gestart is in het afgelopen uur
foreach( parts() as $job )
{
    if( !$job->get('start_time' ) instanceof \DateTime )
        continue;

    if( is_null( $most_recent_job ) ) {
        $most_recent_job = $job;
        continue;
    }

    if( $job->get( 'start_time' ) > $most_recent_job->get('start_time') )
        $most_recent_job = $job;
}

sleep(5); // om te voorkomen dat de laatste job exact een uur geleden is en we dus nog een uur gaan wachten..
if( $most_recent_job === null || ( $most_recent_job->get( 'start_time' ) < new \DateTime( "now -10 minutes" ) ) )
{
    $next_jobs = collect_next_jobs();
    if( count( $next_jobs ) <= 0 )
        echo "We are done?!?!?!?!";
    else
    {
        foreach( $next_jobs as $job )
            start_retrieval_job( $job, $start_time );
    }

    // and serialize data
    serialize_xml( XML_FILE );
}
